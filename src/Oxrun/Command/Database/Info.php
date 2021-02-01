<?php
/**
 * Created by LoberonEE.
 * Autor: Tobias Matthaiou <tm@loberon.de>
 * Date: 01.02.21
 * Time: 14:07
 */

namespace Oxrun\Command\Database;

use OxidEsales\EshopProfessional\Core\DatabaseProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Info
 * @package Oxrun\Command\Database
 */
class Info extends Command
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('db:info')
            ->setDescription('Show a Table with size of all Tables')
            ->addOption('tableSize', '', InputOption::VALUE_NONE, 'Size of all Tables')
            ->addOption('databaseSize', '', InputOption::VALUE_NONE, 'Size of the Databases');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('databaseSize')) {
            $this->showIndexSize($output);
        } else {
            $this->showTableSize($output);
        }
    }

    /**
     * @param OutputInterface $output
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function showTableSize(OutputInterface $output)
    {
        $sql = 'SELECT
                    table_schema as "Database",
                    table_name AS "Table",
                    round(((data_length + index_length) / 1024 / 1024), 2) "Size in MB"
                FROM information_schema.TABLES WHERE `TABLE_TYPE` = "BASE TABLE"
                ORDER BY (data_length + index_length) ASC';

        $resultSet = DatabaseProvider::getDb()->select($sql)->fetchAll();

        $table = new Table($output);
        $table->setHeaders(['Database', 'Table', 'Size in MB']);
        $table->addRows($resultSet);
        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function showIndexSize(OutputInterface $output)
    {
        $sql = 'SELECT  ENGINE,
                    ROUND(SUM(data_length) /1024/1024, 1) AS "Data MB",
                    ROUND(SUM(index_length)/1024/1024, 1) AS "Index MB",
                    ROUND(SUM(data_length + index_length)/1024/1024, 1) AS "Total MB",
                    COUNT(*) "Num Tables"
                FROM  INFORMATION_SCHEMA.TABLES
                WHERE  table_schema not in ("information_schema", "PERFORMANCE_SCHEMA", "SYS_SCHEMA", "ndbinfo")
                GROUP BY  ENGINE;
                ';

        $resultSet = DatabaseProvider::getDb()->select($sql)->fetchAll();

        $table = new Table($output);
        $table->setHeaders(['Engine', 'Data MB', 'Index MB', 'Total MB', 'Num Tables']);
        $table->addRows($resultSet);
        $table->render();

        $output->writeln('<comment>see https://mariadb.com/kb/en/library/mariadb-memory-allocation/</comment>');
    }
}
