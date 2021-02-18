<?php

namespace Oxrun\Command\Config;

use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
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

        $shopId = $input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) ? $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) : 1;

        if ($oxShop->load($shopId)) {
            $varibaleValue = $oxShop->getFieldData($input->getArgument('variableName'));
            $output->writeln("Shopconfig <info>{$input->getArgument('variableName')}</info> has value <comment>$varibaleValue</comment>");
        } else {
            $output->writeln("<error>Shop Id: {$shopId} don't exits</error>");
            return 2;
        }
        return 0;
    }

}
