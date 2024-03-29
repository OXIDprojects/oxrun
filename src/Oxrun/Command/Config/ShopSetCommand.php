<?php

namespace Oxrun\Command\Config;

use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShopSetCommand
 * @package Oxrun\Command\Config
 */
class ShopSetCommand extends Command
{
//    use NeedDatabase;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('config:shop:set')
            ->setDescription('Sets a shop config value')
            ->addArgument('variableName', InputArgument::REQUIRED, 'Variable name')
            ->addArgument('variableValue', InputArgument::REQUIRED, 'Variable value');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oxShop = oxNew(\OxidEsales\Eshop\Application\Model\Shop::class);
        $shopId = $input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) ? $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) : 1;
        if ($oxShop->load($shopId)) {
            $oxShop->assign([
                $input->getArgument('variableName')  => $input->getArgument('variableValue')
            ]);
            $oxShop->save();
            $output->writeln("Shopconfig <info>{$input->getArgument('variableName')}</info> set to <comment>{$input->getArgument('variableValue')}</comment>");
        } else {
            $output->writeln("<error>Shop Id: {$shopId} don't exits</error>");
            return 1;
        }
        return 0;
    }
}
