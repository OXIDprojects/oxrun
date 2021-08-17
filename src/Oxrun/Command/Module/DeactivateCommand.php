<?php

namespace Oxrun\Command\Module;

use Oxrun\Traits\NeedDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeactivateCommand
 * @package Oxrun\Command\Module
 *
 * @deprecated use 'oe:module:deactivate'
 * @see \OxidEsales\EshopCommunity\Internal\Framework\Module\Command\ModuleDeactivateCommand
 */
class DeactivateCommand extends Command implements \Oxrun\Command\EnableInterface
{
    use NeedDatabase;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:deactivate')
            ->setDescription('Deactivates a module')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shopId = $input->getOption('shopId');
        if ($shopId) {
            $this->getApplication()->switchToShopId($shopId);
        }

        $this->executeDeactivate($input, $output);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function executeDeactivate(InputInterface $input, OutputInterface $output)
    {
        $sModule = $input->getArgument('module');
        $shopId = $input->getOption('shopId');

        $oModule = oxNew(\OxidEsales\Eshop\Core\Module\Module::class);
        $oModuleCache = oxNew(\OxidEsales\Eshop\Core\Module\ModuleCache::class, $oModule);
        $oModuleInstaller = oxNew(\OxidEsales\Eshop\Core\Module\ModuleInstaller::class, $oModuleCache);

        if (!$oModule->load($sModule)) {
            $output->writeLn("<error>Cannot load module $sModule.</error>");
        }

        if (!$oModule->isActive()) {
            $output->writeLn("<comment>Module $sModule already deactivated for shopId $shopId.</comment>");
        } else {
            try {
                if ($oModuleInstaller->deactivate($oModule) === true) {
                    $output->writeLn("<info>Module $sModule deactivated for shopId $shopId.</info>");
                } else {
                    $output->writeLn("<comment>Module $sModule already deactivated for shopId $shopId.</comment>");
                }
            } catch (\Exception $ex) {
                $output->writeLn("<error>Exception deactivating module: $sModule for shop $shopId: {$ex->getMessage()}</error>");
            }
        }
    }
}
