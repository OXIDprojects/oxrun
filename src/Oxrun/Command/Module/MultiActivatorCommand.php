<?php
/**
 * Autor: ProudCommerce
 */

namespace Oxrun\Command\Module;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\ArrayInput;

class MultiActivatorCommand extends Command
{

    /**
     * Module activation priorities
     *
     * @var array
     */
    private $aPriorities = [];

    /**
     * Shop ids in YAML
     *
     * @var array
     */
    private $aYamlShopIds = [];

    /**
     * @var ShopConfigurationDaoInterface
     */
    private $shopConfigurationDao;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ModuleActivationServiceInterface
     */
    private $moduleActivationService;

    /**
     * @var ModuleStateServiceInterface
     */
    private $stateService;

    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var ModuleConfigurationInstallerInterface
     */
    private $moduleConfigurationInstaller;

    /**
     * @var OxrunContext
     */
    private $oxrunContext;

    /**
     * @var ModuleConfigurationDaoInterface
     */
    private $moduleConfigurationDao;

    /**
     * @var array
     */
    private $moduleIds = null;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param ShopConfigurationDaoInterface $shopConfigurationDao
     * @param ContextInterface $context
     * @param ModuleActivationServiceInterface $moduleActivationService
     * @param ModuleStateServiceInterface $stateService
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
     */
    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ContextInterface $context,
        ModuleActivationServiceInterface $moduleActivationService,
        ModuleStateServiceInterface $stateService,
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ModuleConfigurationInstallerInterface $moduleConfigurationInstaller,
        OxrunContext $oxrunContext,
        ModuleConfigurationDaoInterface $ModuleConfigurationDao
    )
    {
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->context = $context;
        $this->moduleActivationService = $moduleActivationService;
        $this->stateService = $stateService;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->moduleConfigurationInstaller = $moduleConfigurationInstaller;
        $this->oxrunContext = $oxrunContext;
        $this->moduleConfigurationDao = $ModuleConfigurationDao;
        parent::__construct(null);
    }

    /**
     * Command configuration
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('module:multiactivator')
            ->setDescription('Activates multiple modules, based on a YAML file')
            ->addOption('skipDeactivation', 's', InputOption::VALUE_NONE, "Skip deactivation of modules, only activate.")
            ->addOption('clearModuleData', 'd', InputOption::VALUE_NONE, "Clear module data in oxconfig.")
            ->addArgument('yaml', InputArgument::REQUIRED, 'YAML module list filename or YAML string. The file path is relative to ' . $this->oxrunContext->getOxrunConfigPath());

        $help = <<<HELP
<info>usage:</info>
<comment>oe-console module:multiactivator modules.yml</comment>
- to activate all modules defined in the YAML file based
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
oe-console module:multiactivator $'whitelist:\n  1:\n    - oepaypal\n' --shop-id=1
```
HELP;
        $this->setHelp($help);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $activateShopId = $this->context->getCurrentShopId();
        $this->output->writeLn("<info>START module activator shop " . $activateShopId . "</info>");
        $clearModuleData = $this->input->getOption('clearModuleData');
        if ($clearModuleData) {
            $this->output->writeLn("<info>Clearing module data in DB!</info>");
            $this->clearModuleData($activateShopId);
        }
        $skipDeactivation = $this->input->getOption('skipDeactivation');
        $shopConfiguration = $this->shopConfigurationDao->get(
            $this->context->getCurrentShopId()
        );

        // now try to read YAML
        $moduleYml = $this->oxrunContext->getConfigYaml($this->input->getArgument('yaml'));

        $moduleValues = Yaml::parse($moduleYml);
        if ($moduleValues && is_array($moduleValues)) {
            $this->aPriorities = $this->getPriorities($moduleValues);
            // use whitelist
            if (isset($moduleValues['whitelist'])) {
                $this->aYamlShopIds = array_keys($moduleValues['whitelist']);
                foreach ($moduleValues['whitelist'] as $shopId => $moduleIds) {
                    if ($activateShopId && $activateShopId != $shopId) {
                        $this->output->writeLn("<comment>Skipping shop '$shopId'!</comment>");
                        continue;
                    }

                    if (count($this->aPriorities)) {
                        $this->output->writeLn("<comment>Orig module order:</comment>" . print_r($moduleIds, true));
                        uasort($moduleIds, [$this, "sortModules"]);
                        $this->output->writeLn("<comment>Sorted module order:</comment>" . print_r($moduleIds, true));
                    }

                    //Check is Module installed into Module Configuration
                    foreach ($moduleIds as $moduleId) {
                        if (!$this->isInstalled($moduleId)) {
                            try {
                                $this->installModuleConfiguration($moduleId);
                            } catch (StandardException $e) {
                                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                                unset($moduleIds[$moduleId]);
                                continue;
                            }
                        }
                    }

                    //Activate Module
                    foreach ($moduleIds as $moduleId) {
                        // activate
                        if (!$skipDeactivation) {
                            if ($this->stateService->isActive($moduleId, $shopId) === true) {
                                $this->moduleActivationService->deactivate($moduleId, $shopId);
                                $this->output->writeLn("<info>Module '$moduleId' deactivated</info>");
                            } else {
                                $this->output->writeLn("<comment>Module '$moduleId' not active</comment>");
                            }
                        }
                        if ($this->stateService->isActive($moduleId, $shopId) === false) {
                            $this->moduleActivationService->activate($moduleId, $shopId);
                            $this->output->writeLn("<info>Module '$moduleId' activated</info>");
                        } else {
                            $this->output->writeLn("<comment>Module '$moduleId' already active</comment>");
                        }
                    }
                }
            } elseif (isset($moduleValues['blacklist'])) {
                // use blacklist
                $this->aYamlShopIds = array_keys($moduleValues['blacklist']);
                $aModules = $shopConfiguration->getModuleConfigurations();

                if (count($this->aPriorities)) {
                    $this->output->writeLn("<comment>Orig module order:</comment>" . print_r(array_keys($aModules), true));
                    uasort($aModules, [$this, "sortModules"]);
                    $this->output->writeLn("<comment>Sorted module order:</comment>" . print_r(array_keys($aModules), true));
                }

                foreach ($aModules as $moduleConfiguration) {
                    $moduleId = $moduleConfiguration->getId();
                    foreach ($moduleValues['blacklist'] as $shopId => $moduleIds) {
                        if ($activateShopId && $activateShopId != $shopId) {
                            $this->output->writeLn("<comment>Skipping shop '$shopId'!</comment>");
                            continue;
                        }

                        if (in_array($moduleId, $moduleIds)) {
                            $this->output->writeLn("<comment>Module blacklisted: '$moduleId' - skipping!</comment>");
                            continue 2;
                        }
                        if (!$this->isInstalled($moduleId)) {
                            $this->output->writeLn('<error>Module not found: ' . $moduleId . '</error>');
                            continue 2;
                        }
                        // activate
                        if (!$skipDeactivation) {
                            if ($this->stateService->isActive($moduleId, $shopId) === true) {
                                $this->moduleActivationService->deactivate($moduleId, $shopId);
                                $this->output->writeLn("<info>Module '$moduleId' deactivated</info>");
                            } else {
                                $this->output->writeLn("<comment>Module '$moduleId' not active</comment>");
                            }
                        }
                        if ($this->stateService->isActive($moduleId, $shopId) === false) {
                            $this->moduleActivationService->activate($moduleId, $shopId);
                            $this->output->writeLn("<info>Module '$moduleId' activated</info>");
                        } else {
                            $this->output->writeLn("<comment>Module '$moduleId' already active</comment>");
                        }
                    }
                }
            } else {
                $this->output->writeLn("<comment>No modules to activate for subshop " . $activateShopId . "!</comment>");
            }
        } else {
            $this->output->writeLn("<error>No valid YAML data found!</error>");
        }
        $this->output->writeLn("<info>END module activator shop " . $activateShopId . "</info>");
    }

    /**
     * @param string $moduleId
     *
     * @return bool
     */
    private function isInstalled(string $moduleId): bool
    {
        $shopConfiguration = $this->shopConfigurationDao->get(
            $this->context->getCurrentShopId()
        );

        return $shopConfiguration->hasModuleConfiguration($moduleId);
    }

    /**
     * Sort modules by priority descending per subshop
     *
     * @param Module $a
     * @param Module $b
     *
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
     *
     * @param array $moduleValues Yaml entries as array
     *
     * @return array
     */
    private function getPriorities($moduleValues)
    {
        $aPriorities = [];
        $activateShopId = $this->context->getCurrentShopId();
        if (isset($moduleValues['priorities'])) {
            foreach ($moduleValues['priorities'] as $shopId => $modulePrios) {
                if ($activateShopId && $activateShopId != $shopId) {
                    continue;
                }
                $aPriorities[$shopId] = $modulePrios;
            }
        }
        if (count($aPriorities)) {
            $this->output->writeLn("<comment>Module Priorities:</comment>");
            $this->output->writeLn(print_r($aPriorities, true));
        }

        return $aPriorities;
    }

    /**
     * Get module installations, if any, and install them
     *
     * @param array $moduleValues Yaml entries as array
     */
    private function installModuleConfiguration($moduleId)
    {
        /* @var \Symfony\Component\Console\Application $app */
        $app = $this->getApplication();

        $sourceDir = $this->findModuleSourceDirById($moduleId);

        $this->output->write("<info>Checking</info> is module <comment>'{$moduleId}'</comment> installed ...");
        if (!$this->moduleConfigurationInstaller->isInstalled($sourceDir)) {
            $arguments = new ArrayInput([
                'command' => 'oe:module:install-configuration',
                '--shop-id' => $this->input->getOption('shop-id'),
                'module-source-path' => $sourceDir,
            ]);
            $this->output->writeLn("<comment> No.</comment> Install 'oe:module:install-configuration' {$sourceDir}.");
            $app->find('oe:module:install-configuration')->run($arguments, $this->output);

        } else {
            $this->output->writeLn("<info> is already installed.</info>");
        }
    }

    /**
     * Delete module entries from oxconfig table
     *
     * @param int $shopId
     *
     * @return void
     */
    private function clearModuleData($shopId = false)
    {
        $aVarnames = [
            'aDisabledModules', // aus 6.1
            'aLegacyModules', // aus 4.x
            'activeModules',
            'aModuleControllers',
            'aModuleEvents',
            'aModuleExtensions',
            'aModuleFiles',
            'aModulePaths',
            'aModules',
            'aModuleTemplates',
            'aModuleVersions',
            'moduleSmartyPluginDirectories',
        ];
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxconfig')
            ->where("oxvarname IN('" . implode("','", array_values($aVarnames)) . "')")
            ->andWhere("oxshopid = " . $shopId)
            ->execute();
    }

    /**
     * @param $moduleId
     * @return string
     */
    private function findModuleSourceDirById($moduleId): string
    {
        if ($this->moduleIds === null) {
            $moduleIds = (new Finder())->files()->name('metadata.php')->depth(2)->in($this->context->getModulesPath());
            /** @var /Symfony\Component\Finder\SplFileInfo $metadata */
            foreach ($moduleIds as $metadata) {
                $this->output->writeln('- Read Module ' . $metadata->getPath(), OutputInterface::VERBOSITY_VERBOSE);
                $moduleConfiguratio = $this->moduleConfigurationDao->get($metadata->getPath());
                $this->moduleIds[$moduleConfiguratio->getId()] = $metadata->getPath();
            }
        }

        if (!isset($this->moduleIds[$moduleId])) {
            throw new StandardException('Module not found ' . $moduleId);
        }

        return $this->moduleIds[$moduleId];
    }
}
