<?php
/**
 * Created for oxrun
 * Author: Tobias Matthaiou <matthaiou@tobimat.eu>
 * Date: 07.06.17
 * Time: 07:46
 */

namespace Oxrun\Command\Module;

use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReloadCommand extends Command
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:reload')
            ->setDescription('Deactivate and activate a module')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name')
            ->addOption('force-cache', 'f',InputOption::VALUE_NONE, 'cache:clear with --force option')
            ->addOption('skip-cache-clear', 's',InputOption::VALUE_NONE, 'skip cache:clear command')
        ;
    }

    /**
     * Executes the current commandd
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $skipCacheClear = (bool)$input->getOption('skip-cache-clear');

        $clearCommand      = $app->find('cache:clear');
        $deactivateCommand = $app->find('oe:module:deactivate');
        $activateCommand   = $app->find('oe:module:activate');

        $argvInputClearCache = $this->createInputArray($clearCommand, $input);
        $argvInputDeactivate = $this->createInputArray($deactivateCommand, $input, ['module-id' => $input->getArgument('module')]);
        $argvInputActivate   = $this->createInputArray($activateCommand, $input, ['module-id' => $input->getArgument('module')]);

        if ($input->getOption('force-cache')) {
            $argvInputClearCache->setOption('force', true);
        }

        //Run Command
        if (!$skipCacheClear) {
            $clearCommand->execute($argvInputClearCache, $output);
        }
        $deactivateCommand->execute($argvInputDeactivate, $output);

        if (!$skipCacheClear) {
            $clearCommand->execute($argvInputClearCache, $output);
        }
        $activateCommand->execute($argvInputActivate, $output);

        return 0;
    }

    /**
     * @param Command$command
     * @param InputInterface $input
     */
    protected function createInputArray($command, $input, $extraOption = [])
    {

        $parameters = $extraOption;

        //default --shop-id
        if ($input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) && $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME)) {
            $command->getDefinition()->addOption(new InputOption('--' . Executor::SHOP_ID_PARAMETER_OPTION_NAME, '', InputOption::VALUE_REQUIRED));
            $parameters = array_merge(
                ['--' . Executor::SHOP_ID_PARAMETER_OPTION_NAME => $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME)],
                $extraOption
            );
        }


        return new ArrayInput(
            $parameters,
            $command->getDefinition()
        );
    }
}
