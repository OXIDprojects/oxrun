<?php

namespace Oxrun\Command\User;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopProfessional\Core\DatabaseProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PasswordCommand
 * @package Oxrun\Command\User
 */
class PasswordCommand extends Command
{
//    use NeedDatabase;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('user:password')
            ->setDescription('Sets a new password')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'New password');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oxUser = \oxNew(User::class);

        $sql = 'SELECT `oxuser`.`OXID` FROM `oxuser` WHERE `oxuser`.`OXUSERNAME` = ?';

        $userOxid = DatabaseProvider::getDb()->getOne($sql, [$input->getArgument('username')]);

        if(empty($userOxid)){
            $output->writeln('<error>User does not exist.</error>');
            return 1;
        }
        $oxUser->load($userOxid);
        $oxUser->setPassword($input->getArgument('password'));
        $oxUser->save();
        $output->writeln('<info>New password set.</info>');

        return 0;
    }
}
