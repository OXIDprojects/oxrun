<?php

namespace Oxrun\Command\Config;


use Doctrine\DBAL\FetchMode;
use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use Oxrun\Core\EnvironmentManager;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateYaml
 * @package Oxrun\Command\Config
 */
class UpdateYaml extends Command
{

    /**
     * @var OxrunContext
     */
    private $oxrunContext;

    /**
     * @var \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDao
     */
    private $shopConfigurationDao;

    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var EnvironmentManager
     */
    private $environments;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * UpdateYaml constructor.
     * @param OxrunContext $oxrunContext
     * @param ShopConfigurationDaoInterface $shopConfigurationDao
     */
    public function __construct(
        OxrunContext $oxrunContext,
        ShopConfigurationDaoInterface $shopConfigurationDao,
        QueryBuilderFactoryInterface $queryBuilderFactory,
        EnvironmentManager $environments
    ) {
        $this->oxrunContext = $oxrunContext;
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->environments = $environments;

        parent::__construct();
    }


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('config:update:yaml')
            ->setDescription('Update the module configuration yaml with the data from the database')
            ->setHelp('Is the reverse command from `oe:module:apply-configuration`.')
        ;

        $this->environments->addOptionToCommand($this);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->environments
            ->init($input, $output)
            ->load($this->environments->getOptions())
        ;

        $shopConfigurations = [];

        if ($input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) && $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) !== null) {
            $shopId = $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME);
            $shopConfigurations[$shopId] = $this->shopConfigurationDao->get($shopId);
        } else {
            $shopConfigurations = $this->shopConfigurationDao->getAll();
        }


        foreach ($shopConfigurations as $shopId => $shopConfiguration) {
            foreach ($shopConfiguration->getModuleIdsOfModuleConfigurations() as $moduleId) {
                $moduleConfiguration = $shopConfiguration->getModuleConfiguration($moduleId);
                foreach ($moduleConfiguration->getModuleSettings() as $setting) {
                    $this->addDatabaseConfig($shopId, $moduleId, $setting->getName());
                }
            };
        }

        $this->environments->save();
    }

    protected function addDatabaseConfig($shopId, $moduleId, $valueName)
    {
        $oxvarvalue = Registry::getConfig()->getDecodeValueQuery('OXVARVALUE');

        $result = $this->queryBuilderFactory->create()
            ->select("OXVARTYPE, $oxvarvalue as OXVARVALUE")
            ->from('oxconfig')
            ->where('OXSHOPID = :oxshopid')
            ->andWhere('OXMODULE = :oxmodule')
            ->andWhere('OXVARNAME = :oxvarname')
            ->setParameter('oxshopid', $shopId)
            ->setParameter('oxmodule', "module:$moduleId")
            ->setParameter('oxvarname', $valueName)
            ->setMaxResults(1)
            ->execute()
        ;

        $rowCount = $result->rowCount();
        if ($rowCount == 0) {
            $this->output->writeln("<comment>No parameter in DB ShopId: $shopId ModuleId: $moduleId ValueName: $valueName</comment>", OutputInterface::VERBOSITY_VERBOSE);
            return;
        }

        $result = $result->fetchAll(\Doctrine\DBAL\FetchMode::ASSOCIATIVE);
        $rawValue = array_shift($result);

        $value = $rawValue['OXVARVALUE'];

        switch ($rawValue['OXVARTYPE']) {
            case 'arr':
            case 'aarr':
                $value = unserialize($value);
                break;
            case 'bool':
                $value = (bool)($value == 'true' || $value == '1');
                break;
        }

        $this->environments->set($shopId, $moduleId, $valueName, $value);
    }
}
