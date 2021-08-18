<?php

namespace Oxrun\Command\Views;

use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateCommand
 * @package Oxrun\Command\Views
 */
class UpdateCommand extends Command
{
//    use NeedDatabase;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('views:update')
            ->setDescription('Updates the views');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $myConfig = Registry::getConfig();
        $myConfig->setConfigParam('blSkipViewUsage', true);

        $oMetaData = \oxNew(DbMetaDataHandler::class);
        if ($oMetaData->updateViews()) {
            $output->writeln('<info>Views updated.</info>');
            return 0;
        } else {
            $output->writeln('<error>Views could not be updated.</error>');
            return 2;
        }
    }
}
