<?php

namespace Oxrun\Command\Config;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class GetCommand
 * @package Oxrun\Command\Config
 */
class GetCommand extends Command
{

//    use NeedDatabase;

    /**
     * @var QueryBuilderFactoryInterface
     */
    protected $queryBuilderFactory;

    /**
     * GetCommand constructor.
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     */
    public function __construct(QueryBuilderFactoryInterface $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Gets a config value')
            ->addArgument('variableName', InputArgument::REQUIRED, 'Variable name')
            ->addOption('moduleId', null, InputOption::VALUE_OPTIONAL, '', '')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as json')
            ->addOption('yaml', null, InputOption::VALUE_NONE, 'Output as YAML (default)');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oxConfig = Registry::getConfig();

        $varName = $input->getArgument('variableName');

        $shopId = $input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) ? $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) : null;
        $moduleId = $input->getOption('moduleId');

        $shopConfVar = $oxConfig->getShopConfVar(
            $varName,
            $shopId,
            $moduleId
        );


        if ($shopConfVar === null) {
            $output->writeln("<error>$varName not found.</error>");
            return 2;
        }

        if ($shopId) {
            $output_var[$varName]['shop-id'] = $shopId;
        }

        if ($moduleId) {
            $output_var[$varName]['moduleId'] = $moduleId;
        }

        $output_var[$varName]['type'] = $this->findType($varName);
        $output_var[$varName]['value'] = $shopConfVar;

        if ($input->getOption('json')) {
            $output_var = \json_encode($output_var);
        } else {
            $output_var = Yaml::dump($output_var, 4, 2);
        }

        $output->writeln("<info>".$output_var."</info>");

        return 0;
    }

    public function findType($varName)
    {
        $qb = $this->queryBuilderFactory->create();
        $qb->select('oxvartype' )
            ->from('oxconfig')
            ->where('OXVARNAME = :oxvarname')
            ->setParameter('oxvarname', $varName)
            ->setMaxResults(1);

        $firstColumn = $qb->execute()->fetchColumn();
        return $firstColumn;
    }
}
