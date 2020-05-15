<?php
/**
 * Created for oxrun
 * Author: Stefan Moises <beffy@proudcommerce.com>
 */

namespace Oxrun\Command\Module;

use Oxrun\Traits\NeedDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MultiActivateCommand
 *
 * @package Oxrun\Command\Module
 *
 * This command can be used to activate multiple modules for multiple shops.
 * You need to pass it a valid yaml file as argument, relative to the shop root dir,
 * containing either a "whitelist" or a
 * "blacklist" with shop ids and module ids, e.g.
 * whitelist:
 *   1:
 *     - oepaypal
 *     - oxpspaymorrow
 *   2:
 *     - oepaypal
 *     - oxpspaymorrow
 *
 * @see example/modules.yml.dist
 */
class MultiActivateCommand extends Command implements \Oxrun\Command\EnableInterface
{
    use NeedDatabase;

    private $aPriorities = [];
    private $currShopId = null;
    private $aYamlShopIds = [];

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:multiactivate')
            ->setDescription('Activates multiple modules, based on a YAML file')
            ->addOption('skipDeactivation', 's', InputOption::VALUE_NONE, "Skip deactivation of modules, only activate.")
            ->addOption('skipClear', 'c', InputOption::VALUE_NONE, "Skip cache clearing.")
            ->addOption('clearModuleData', 'd', InputOption::VALUE_NONE, "Clear module data in oxconfig.")
            ->addArgument('module', InputArgument::REQUIRED, 'YAML module list filename or YAML string. The file path is relative to the shop installation_root_path/oxrun_config/');

        $help = <<<HELP
<info>usage:</info>
<comment>oxrun module:multiactivate configs/modules.yml</comment>
- to activate all modules defined in the YAML file. [Example: modules.yml.dist](example/modules.yml.dist) based
on a white- or blacklist

Example:

```yaml
whitelist:
1:
    - ocb_cleartmp
    - moduleinternals
    #- ddoevisualcms
    #- ddoewysiwyg
2:
    - ocb_cleartmp
priorities:
1:
    moduleinternals:
        1200
    ocb_cleartmp:
        950
```

Supports either a __"whitelist"__ or a __"blacklist"__ entry with multiple shop ids and the desired module ids to activate (whitelist) or to exclude from activation (blacklist).

With "priorities", you can define the order (per subshop) in which the modules will be activated.

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oxrun module:multiactivate $'whitelist:\n  1:\n    - oepaypal\n' --shopId=1
```
HELP;
        $this->setHelp($help);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $activateShopId = $input->getOption('shopId');
        $this->currShopId = $activateShopId;
        $clearModuleData = $input->getOption('clearModuleData');
        if ($clearModuleData) {
            $output->writeLn("<comment>Clearing module data in DB!</comment>");
            $this->clearModuleData($activateShopId);
        }

        /* @var \Oxrun\Application $app */
        $app = $this->getApplication();
        $skipDeactivation = $input->getOption('skipDeactivation');
        $skipClear = $input->getOption('skipClear');

        // now try to read YAML
        $moduleYml = $app->getYaml($input->getArgument('module'));
        $moduleValues = Yaml::parse($moduleYml);
        if ($moduleValues && is_array($moduleValues)) {
            $this->aPriorities = $this->getPriorities($moduleValues, $input, $output);
            // use whitelist
            if (isset($moduleValues['whitelist'])) {
                $this->aYamlShopIds = array_keys($moduleValues['whitelist']);
                foreach ($moduleValues['whitelist'] as $shopId => $moduleIds) {
                    if ($activateShopId && $activateShopId != $shopId) {
                        $output->writeLn("<comment>Skipping shop '$shopId'!</comment>");
                        continue;
                    }

                    if (count($this->aPriorities)) {
                        $output->writeLn("<comment>Orig module order:</comment>" . print_r($moduleIds, true));
                        uasort($moduleIds, array($this, "sortModules"));
                        $output->writeLn("<comment>Sorted module order:</comment>" . print_r($moduleIds, true));
                    }

                    foreach ($moduleIds as $moduleId) {
                        if (!$skipDeactivation) {
                            $arguments = array(
                                'command' => 'module:deactivate',
                                'module'    => $moduleId,
                                '--shopId'  => $shopId,
                            );
                            $deactivateInput = new ArrayInput($arguments);
                            $app->find('module:deactivate')->run($deactivateInput, $output);
                            if (!$skipClear) {
                                $app->find('cache:clear')->run(new ArgvInput([]), $output);
                            }
                        }
                        $arguments = array(
                            'command' => 'module:activate',
                            'module'    => $moduleId,
                            '--shopId'  => $shopId,
                        );
                        $activateInput = new ArrayInput($arguments);
                        $app->find('module:activate')->run($activateInput, $output);
                    }
                }
            } elseif (isset($moduleValues['blacklist'])) {
                // use blacklist
                $this->aYamlShopIds = array_keys($moduleValues['blacklist']);

                /* @var \OxidEsales\Eshop\Core\Module\ModuleList $oxModuleList  */
                $oxModuleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
                $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
                $aModules = $oxModuleList->getModulesFromDir($oConfig->getModulesDir());

                if (count($this->aPriorities)) {
                    $output->writeLn("<comment>Orig module order:</comment>" . print_r(array_keys($aModules), true));
                    uasort($aModules, array($this, "sortModules"));
                    $output->writeLn("<comment>Sorted module order:</comment>" . print_r(array_keys($aModules), true));
                }

                foreach ($aModules as $moduleId => $aModuleData) {
                    foreach ($moduleValues['blacklist'] as $shopId => $moduleIds) {
                        if ($activateShopId && $activateShopId != $shopId) {
                            $output->writeLn("<comment>Skipping shop '$shopId'!</comment>");
                            continue;
                        }
                        
                        if (in_array($moduleId, $moduleIds)) {
                            $output->writeLn("<comment>Module blacklisted: '$moduleId' - skipping!</comment>");
                            continue 2;
                        }
                        // activate
                        if (!$skipDeactivation) {
                            $arguments = array(
                                'command' => 'module:deactivate',
                                'module'    => $moduleId,
                                '--shopId'  => $shopId,
                            );
                            $deactivateInput = new ArrayInput($arguments);
                            $app->find('module:deactivate')->run($deactivateInput, $output);
                            if (!$skipClear) {
                                $app->find('cache:clear')->run(new ArgvInput([]), $output);
                            }
                        }
                        $arguments = array(
                            'command' => 'module:activate',
                            'module'    => $moduleId,
                            '--shopId'  => $shopId,
                        );
                        $activateInput = new ArrayInput($arguments);
                        $app->find('module:activate')->run($activateInput, $output);
                    }
                }
            } else {
                $output->writeLn("<comment>No modules to activate for subshop '$shopId'!</comment>");
            }
        } else {
            $output->writeLn("<comment>No valid YAML data found!</comment>");
        }
    }

    /**
     * Sort modules by priority descending per subshop
     *
     * @param Module $a
     * @param Module $b
     * @return int
     */
    public function sortModules($a, $b)
    {
        $aP = $bP = 0;
        // we may have module ids in whitelist
        if (is_string($a) && is_string($b)) {
            $aID = $a;
            $bID = $b;
        } else {
            // or Module objects if using blacklist
            $aID = $a->getId();
            $bID = $b->getId();
        }
        foreach ($this->aYamlShopIds as $shopId) {
            // check if subshop priorities defined
            if (isset($this->aPriorities[$shopId])) {
                if (isset($this->aPriorities[$shopId][$aID])) {
                    $aP = $this->aPriorities[$shopId][$aID];
                }
                if (isset($this->aPriorities[$shopId][$bID])) {
                    $bP = $this->aPriorities[$shopId][$bID];
                }
            }
        }
        //die($aID . ' - ' . $bID . ' - ' . $aP . ' - ' . $bP);
        if ($aP == $bP) {
            return 0;
        }
        return ($aP > $bP) ? -1 : 1;
    }

    /**
     * Get module priorities, if any
     * @param array $moduleValues Yaml entries as array
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return array
     */
    protected function getPriorities($moduleValues, $input, $output)
    {
        $aPriorities = [];
        $activateShopId = $input->getOption('shopId');
        if (isset($moduleValues['priorities'])) {
            foreach ($moduleValues['priorities'] as $shopId => $modulePrios) {
                if ($activateShopId && $activateShopId != $shopId) {
                    continue;
                }
                $aPriorities[$shopId] = $modulePrios;
            }
        }
        if (count($aPriorities)) {
            $output->writeLn("<comment>Module Priorities:</comment>");
            $output->writeLn(print_r($aPriorities, true));
        }
        return $aPriorities;
    }

    /**
     * Delete module entries from oxconfig table
     *
     * @param int $shopId
     * @return void
     */
    private function clearModuleData($shopId = false)
    {
        $sSql = "delete from oxconfig where oxvarname in (
            'aDisabledModules',
            'aLegacyModules',
            'aModuleFiles',
            'aModulePaths',
            'aModules',
            'aModuleTemplates'
        )";
        if ($shopId) {
            $sSql .= " and oxshopid = '{$shopId}'";
        }
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $database->execute($sSql);
    }
}
