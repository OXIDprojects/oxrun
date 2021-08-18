<?php
/**
 * Autor: ProudCommerce
 */

namespace Oxrun\Command\Deploy;

use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Service\ModuleActivationServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ModuleActivatorCommand extends Command
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
     * @var ConsoleOutput
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
     * Command configuration
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('deploy:module-activator')
            ->setAliases(['module:multiactivator'])
            ->setDescription('Activates multiple modules, based on a YAML file')
            ->addOption('skipDeactivation', 's', InputOption::VALUE_NONE, "Skip deactivation of modules, only activate.")
            ->addOption('clearModuleData', 'd', InputOption::VALUE_NONE, "Clear module data in oxconfig.")
            ->addArgument('yaml', InputArgument::REQUIRED, 'YAML module list filename or YAML string. The file path is relative to ' . $this->oxrunContext->getOxrunConfigPath());

        $help = <<<HELP
<info>usage:</info>
<comment>oe-console deploy:module-activator modules.yml</comment>
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

Supports either a __"whitelist"__ and or a __"blacklist"__ entry with multiple shop ids and the desired module ids to activate (whitelist) or to exclude from activation (blacklist).

With "priorities", you can define the order (per subshop) in which the modules will be activated.

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
oe-console deploy:module-activator $'whitelist:\n  1:\n    - oepaypal\n' --shop-id=1
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

        $moduleValues = $this->taskReadYaml();

        $this->output->writeLn("<info>START module activator shop</info>");

        $this->taskClearModuleData();

        if ($moduleValues && is_array($moduleValues)) {
            $this->aPriorities = $this->getPriorities($moduleValues);
            $this->perShop(
                function ($activateShopId) use ($moduleValues) {
                    if (isset($moduleValues['whitelist'])) {
                        $this->taskWhitelist($moduleValues['whitelist'], $activateShopId);
                    }
                    if (isset($moduleValues['blacklist'])) {
                        $this->taskBlacklist($moduleValues['blacklist'], $activateShopId);
                    }
                });
        } else {
            $this->output->writeLn("<error>No valid YAML data found!</error>");
        }
        $this->output->writeLn("<info>END module activator shop</info>");
    }


    protected function taskClearModuleData(): void
    {
        $clearModuleData = $this->input->getOption('clearModuleData');
        if ($clearModuleData) {
            $this->output->writeLn("<info>Clearing module data in DB!</info>");
            $this->clearModuleData();
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
        $varnames = [
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

        $shopIds = $this->getSelectedShopIds();

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxconfig')
            ->where("oxvarname IN(:oxvarnames)")
            ->setParameter('oxvarnames', $varnames, Connection::PARAM_STR_ARRAY)
            ->andWhere("oxshopid IN (:shopIds)")
            ->setParameter('shopIds', $shopIds, Connection::PARAM_STR_ARRAY)
            ->execute();
    }

    /**
     * @return array
     */
    protected function getSelectedShopIds(): array
    {
        if ($this->input->hasOption('shop-id') && $this->input->getOption('shop-id') > 0) {
            return [$this->input->getOption('shop-id')];
        }

        return $this->context->getAllShopIds();
    }

    /**
     * @return mixed
     */
    protected function taskReadYaml()
    {
        $moduleYml = $this->oxrunContext->getConfigYaml($this->input->getArgument('yaml'));
        $moduleValues = Yaml::parse($moduleYml);

        if (!isset($moduleValues['whitelist']) && !isset($moduleValues['blacklist'])) {
            $this->output->getErrorOutput()->writeLn("<error>No whitelist or blacklist found see --help</error>");
            exit(2);
        }

        return $moduleValues;
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

    protected function perShop(callable $function)
    {
        array_map(
            function ($shopId) use ($function) {
                call_user_func_array($function, [$shopId]);
            },
            $this->getSelectedShopIds()
        );
    }

    /**
     * @param array $whitelist
     * @param int $shopId
     * @return mixed|void
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException
     */
    protected function taskWhitelist(array $whitelist, int $shopId): void
    {
        $this->aYamlShopIds = array_keys($whitelist);

        if (!isset($whitelist[$shopId])) {
            $this->output->getErrorOutput()->writeln('In whitelist not found modules for shop id: ' . $shopId);
            return;
        }

        $moduleIds = $whitelist[$shopId];
        $this->checkModuleInstalled($moduleIds);

        if (count($this->aPriorities)) {
            $this->output->writeLn("<comment>($shopId) Orig module order:</comment>" . print_r($moduleIds, true));
            uasort($moduleIds, [$this, "sortModules"]);
            $this->output->writeLn("<comment>($shopId) Sorted module order:</comment>" . print_r($moduleIds, true));
        }

        //Activate Module
        foreach ($moduleIds as $moduleId) {
            $this->tryActivateModul($moduleId, $shopId);
        }
    }

    /**
     * @param $moduleIds
     * @return array
     */
    protected function checkModuleInstalled($moduleIds): array
    {
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
        return array($moduleId, $moduleIds);
    }

    /**
     * @param string $moduleId
     *
     * @return bool
     */
    private function isInstalled(string $moduleId): bool
    {
        $shopConfiguration = $this->shopConfigurationDao->get(1); //muss ja sowie so in alle gespeichert sein
        return $shopConfiguration->hasModuleConfiguration($moduleId);
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

            $arguments['command'] = 'oe:module:install-configuration';
            if ($this->input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME)) {
                $arguments['--shop-id'] = $this->input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME);
            }
            $arguments['module-source-path'] = $sourceDir;

            $arguments = new ArrayInput($arguments);
            $this->output->writeLn("<comment> No.</comment> Install 'oe:module:install-configuration' {$sourceDir}.");
            $app->find('oe:module:install-configuration')->run($arguments, $this->output);

        } else {
            $this->output->writeLn("<info> is already installed.</info>");
        }
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

    /**
     * @param string $moduleId
     * @param int $shopId
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException
     */
    protected function tryActivateModul(string $moduleId, int $shopId): void
    {
        $skipDeactivation = $this->input->getOption('skipDeactivation') == true;

        if (!$skipDeactivation) {
            if ($this->isModuleActive($moduleId, $shopId) === true) {
                $this->moduleActivationService->deactivate($moduleId, $shopId);
                $this->output->writeLn("<info>($shopId) Module '$moduleId' deactivated</info>");
            } else {
                $this->output->writeLn("<comment>($shopId) Module '$moduleId' not active</comment>");
            }
        }
        if ($this->isModuleActive($moduleId, $shopId) === false) {
            $this->moduleActivationService->activate($moduleId, $shopId);
            $this->output->writeLn("<info>($shopId) Module '$moduleId' activated</info>");
        } else {
            $this->output->writeLn("<comment>($shopId) Module '$moduleId' already active</comment>");
        }
    }

    /**
     * @param string $moduleId
     * @param int $shopId
     * @return bool
     *
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException
     */
    private function isModuleActive(string $moduleId, int $shopId): bool
    {
        $shopConfiguration = $this->shopConfigurationDao->get($shopId);

        $isActiveInDB = $this->stateService->isActive($moduleId, $shopId);
        $isActiveInYaml = (bool)$shopConfiguration->getModuleConfiguration($moduleId)->isConfigured();

        if ($isActiveInDB == true && $isActiveInYaml == false) {
            $this->stateService->setDeactivated($moduleId, $shopId);
        }

        return $isActiveInDB && $isActiveInYaml;
    }

    /**
     * @param $blacklist
     * @param \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration $shopConfiguration
     * @param int $activateShopId
     * @param array|null $skipDeactivation
     * @return \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration[]
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException
     */
    protected function taskBlacklist($blacklist, int $shopId): void
    {
        $this->aYamlShopIds = array_keys($blacklist);

        $shopConfiguration = $this->shopConfigurationDao->get($shopId);
        $aModules = $shopConfiguration->getModuleConfigurations();

        if (count($this->aPriorities)) {
            $this->output->writeLn("<comment>($shopId) Orig module order:</comment>" . print_r(array_keys($aModules), true));
            uasort($aModules, [$this, "sortModules"]);
            $this->output->writeLn("<comment>($shopId) Sorted module order:</comment>" . print_r(array_keys($aModules), true));
        }

        if (!isset($blacklist[$shopId])) {
            $this->output->getErrorOutput()->writeln('In blacklist not found modules for shop id: ' . $shopId);
            return;
        }

        $moduleIds = $blacklist[$shopId];
        $this->checkModuleInstalled($moduleIds);

        foreach ($aModules as $moduleConfiguration) {
            $moduleId = $moduleConfiguration->getId();

            if (in_array($moduleId, $moduleIds)) {
                if ($this->isModuleActive($moduleId, $shopId) === true) {
                    $this->moduleActivationService->deactivate($moduleId, $shopId);
                }
                $this->output->writeLn("<comment>($shopId) Module blacklisted: '$moduleId' - skipping!</comment>");
                continue;
            }

            $this->tryActivateModul($moduleId, $shopId);
        }
    }
}
