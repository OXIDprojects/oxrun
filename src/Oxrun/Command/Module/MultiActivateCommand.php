<?php
/**
 * Created for oxrun
 * Author: Stefan Moises <moises@shoptimax.de>
 * Date: 07.03.18
 * Time: 08:46
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
 */
class MultiActivateCommand extends Command implements \Oxrun\Command\EnableInterface
{
    use NeedDatabase;

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
            ->addArgument('module', InputArgument::REQUIRED, 'YAML module list filename or YAML string');

        $help = <<<HELP
<info>usage:</info>
<comment>oxrun module:multiactivate configs/modules.yml</comment>
- to activate all modules defined in the file "configs/modules.yml" based
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
```

Supports either a __"whitelist"__ or a __"blacklist"__ entry with multiple shop ids and the desired module ids to activate (whitelist) or to exclude from activation (blacklist).

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
        /* @var \Oxrun\Application $app */
        $app = $this->getApplication();
        $skipDeactivation = $input->getOption('skipDeactivation');
        $skipClear = $input->getOption('skipClear');

        // now try to read YAML
        $moduleYml = $app->getYaml($input->getArgument('module'));
        $moduleValues = Yaml::parse($moduleYml);
        if ($moduleValues && is_array($moduleValues)) {
            // use whitelist
            if (isset($moduleValues['whitelist'])) {
                foreach ($moduleValues['whitelist'] as $shopId => $moduleIds) {
                    if ($activateShopId && $activateShopId != $shopId) {
                        $output->writeLn("<comment>Skipping shop '$shopId'!</comment>");
                        continue;
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
                /* @var \OxidEsales\Eshop\Core\Module\ModuleList $oxModuleList  */
                $oxModuleList = oxNew(\OxidEsales\Eshop\Core\Module\ModuleList::class);
                $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
                $aModules = $oxModuleList->getModulesFromDir($oConfig->getModulesDir());
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
}
