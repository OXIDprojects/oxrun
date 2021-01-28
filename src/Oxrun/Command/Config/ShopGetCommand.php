<?php

namespace Oxrun\Command\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShopGetCommand
 * @package Oxrun\Command\Config
 */
class ShopGetCommand extends Command
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('config:shop:get')
            ->setDescription('Gets a shop config value')
            ->addArgument('variableName', InputArgument::REQUIRED, 'Variable name');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Shop config
        $oxShop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
        if ($oxShop->load($input->getOption('shop-id'))) {
            $varibaleValue = $oxShop->getFieldData($input->getArgument('variableName'));
            $output->writeln("Shopconfig <info>{$input->getArgument('variableName')}</info> has value <comment>$varibaleValue</comment>");
        } else {
            $output->writeln("<error>Shop Id: {$input->getOption('shop-id')} don't exits</error>");
            return 2;
        }
        return 0;
    }

}
