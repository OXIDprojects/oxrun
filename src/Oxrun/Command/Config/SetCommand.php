<?php

namespace Oxrun\Command\Config;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetCommand
 * @package Oxrun\Command\Config
 */
class SetCommand extends Command
{

    /**
     * @var QueryBuilderFactoryInterface
     */
    protected $queryBuilderFactory;

    /**
     * SetCommand constructor.
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
            ->setName('config:set')
            ->setDescription('Sets a config value')
            ->addArgument('variableName', InputArgument::REQUIRED, 'Variable name')
            ->addArgument('variableValue', InputArgument::REQUIRED, 'Variable value')
            ->addOption('variableType', null, InputOption::VALUE_REQUIRED, 'Variable type')
            ->addOption('moduleId', null, InputOption::VALUE_OPTIONAL, '');
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

        // determine variable type
        if ($input->getOption('variableType')) {
            $variableType = $input->getOption('variableType');
        } else {
            $qb = $this->queryBuilderFactory->create();
            $qb->select('oxvartype' )
                ->from('oxconfig')
                ->where('OXVARNAME = :oxvarname')
                ->setParameter('oxvarname', $input->getArgument('variableName'))
                ->setMaxResults(1);

            $firstColumn = $qb->execute()->fetchColumn();
            $variableType = array_shift($firstColumn);
        }

        if (in_array($variableType, array('aarr', 'arr'))) {
            $variableValue = json_decode($input->getArgument('variableValue'), true);
        } else {
            $variableValue = $input->getArgument('variableValue');
        }

        $oxConfig->saveShopConfVar(
            $variableType,
            $input->getArgument('variableName'),
            $variableValue,
            $input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) ? $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) : null,
            $input->getOption('moduleId')
        );

        $output->writeln("<info>Config {$input->getArgument('variableName')} set to {$input->getArgument('variableValue')}</info>");
        return 0;
    }

}
