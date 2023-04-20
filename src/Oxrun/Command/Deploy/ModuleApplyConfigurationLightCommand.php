<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 29.03.21
 * Time: 12:36
 */

namespace Oxrun\Command\Deploy;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\SettingDao;
use OxidEsales\EshopCommunity\Internal\Framework\Module\State\ModuleStateServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ModuleApplyConfigurationLightCommand
 * @package Oxrun\Command\Deploy
 */
class ModuleApplyConfigurationLightCommand extends Command
{
    /**
     * @var Closure[]
     */
    protected $originApplyModulesConfigurationCommand = [
        'activateConfiguredNotActiveModules' => null,
        'deactivateNotConfiguredActivateModules' => null,
    ];
    /**
     * @var ShopConfigurationDaoInterface
     */
    private $shopConfigurationDao;

    /**
     * @var ModuleStateServiceInterface
     */
    private $moduleStateService;

    /**
     * @var SettingDao
     */
    private $settingDao;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * ModuleApplyConfigurationLightCommand constructor.
     */
    public function __construct(
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ModuleStateServiceInterface $moduleStateService,
        SettingDao $settingDao
    )
    {
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->moduleStateService = $moduleStateService;
        $this->settingDao = $settingDao;

        parent::__construct(null);
    }


    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('deploy:module-apply-configuration-light')
            ->setDescription('It the same as `oe:module:apply-configuration` but faster.')
            ->setHelp(<<<TAG
The module configurations will ONLY written into the database.
- Without deactivating or activating the modules
- Without rewrite module configration yaml's

WARNING: If you make changes on metadata.php::controllers|::extend then this command doesn't work.

That automatic activate or deactive module with the param `configured: true|false`.
It the same as `oe:module:apply-configuration` but faster!
TAG
            );
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->callablePrivateFunction($this->getApplication()->find('oe:module:apply-configuration'));
    }

    /**
     * @param Command $command
     */
    private function callablePrivateFunction(Command $command): void
    {
        $reflectionClass = new \ReflectionClass($command);
        $reflectionMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PRIVATE);

        array_map(
            function (\ReflectionMethod $reflectionMethod) use ($command) {
                $functionName = $reflectionMethod->getName();
                $reflectionMethod->setAccessible(true);

                //create a closure of all private functions
                $this->originApplyModulesConfigurationCommand[$functionName] = function () use ($reflectionMethod, $command) {
                    $reflectionMethod->invoke($command, ...func_get_args());
                };
            },
            $reflectionMethods
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($input->hasOption('shop-id') && $input->getOption('shop-id')) {
            $this->applyModulesConfigurationForOneShop((int)$input->getOption('shop-id'));
        } else {
            $this->applyModulesConfigurationForAllShops();
        }

        return self::SUCCESS;
    }

    private function applyModulesConfigurationForOneShop(int $shopId)
    {
        $shopConfiguration = $this->shopConfigurationDao->get($shopId);

        $this->applyModulesConfigurationForShop($shopConfiguration, $shopId);
    }

    private function applyModulesConfigurationForAllShops()
    {
        $shopConfigurations = $this->shopConfigurationDao->getAll();

        foreach ($shopConfigurations as $shopId => $shopConfiguration) {
            $this->applyModulesConfigurationForShop($shopConfiguration, $shopId);
        }
    }

    /**
     * @param ShopConfiguration $shopConfiguration
     * @param int $shopId
     */
    private function applyModulesConfigurationForShop(ShopConfiguration $shopConfiguration, int $shopId)
    {
        foreach ($shopConfiguration->getModuleConfigurations() as $moduleConfiguration) {
            $this->output->writeln(
                "<info>[light] ($shopId) Applying configuration for module with id "
                . $moduleConfiguration->getId()
                . '</info>'
            );
            try {
                $this->originApplyModulesConfigurationCommand['deactivateNotConfiguredActivateModules']($moduleConfiguration, $shopId);
                $this->saveOnlyConfigrationToDatabase($moduleConfiguration, $shopId);
                $this->originApplyModulesConfigurationCommand['activateConfiguredNotActiveModules']($moduleConfiguration, $shopId);
            } catch (\Exception $exception) {
                $this->originApplyModulesConfigurationCommand['showErrorMessage']($this->output, $exception);
            }
        }
    }

    /**
     * Diese eine abkürzung.
     * Hierbei wird das Module nicht deaktiviert und aktiviert was zeit kostet ...
     * sondern es wird einfach in die DB hineingeschrieben.
     *
     * @param ModuleConfiguration $moduleConfiguration
     * @param int $shopId
     */
    private function saveOnlyConfigrationToDatabase(ModuleConfiguration $moduleConfiguration, int $shopId)
    {
        if (
            $moduleConfiguration->isConfigured() === true
            && $this->moduleStateService->isActive($moduleConfiguration->getId(), $shopId) === true
            && $moduleConfiguration->hasModuleSettings()
        ) {
            $moduleId = $moduleConfiguration->getId();
            array_map(
                function (Setting $moduleSetting) use ($moduleId, $shopId) {
                    $this->output->writeln("($shopId) Module: <info>$moduleId</info> Config: <info>" . $moduleSetting->getName() .'</info>', OutputInterface::VERBOSITY_VERBOSE);
                    $this->settingDao->save(
                        $moduleSetting,
                        $moduleId,
                        $shopId
                    );
                },
                $moduleConfiguration->getModuleSettings()
            );
        }
    }
}
