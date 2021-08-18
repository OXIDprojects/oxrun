<?php

namespace Oxrun\Command\Database;

use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand
 * @package Oxrun\Command\Database
 */
class ImportCommand extends Command
{
//    use NeedDatabase;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('db:import')
            ->setDescription('Import a sql file')
            ->addArgument('file', InputArgument::REQUIRED, 'The sql file which is to be imported');

        $help = <<<HELP
Imports an SQL file on the current shop database.

Requires php exec and MySQL CLI tools installed on your system.
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
        $file = $input->getArgument('file');
        if (!is_file($file)) {
            $output->writeln("<error>File $file does not exist.</error>");
            return 2;
        }

        // allow empty password
        $dbPwd = Registry::getConfig()->getConfigParam('dbPwd');
        if (!empty($dbPwd)) {
            $dbPwd = '-p' . $dbPwd;
        }

        $exec = sprintf(
            "mysql -h%s %s -u%s %s < %s 2>&1",
            Registry::getConfig()->getConfigParam('dbHost'),
            $dbPwd,
            Registry::getConfig()->getConfigParam('dbUser'),
            Registry::getConfig()->getConfigParam('dbName'),
            $file
        );

        exec($exec, $commandOutput, $returnValue);

        if ($returnValue > 0) {
            $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
            return 2;
        }

        $output->writeln("<info>File $file is imported.</info>");
        return 0;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return function_exists('exec');
    }
}
