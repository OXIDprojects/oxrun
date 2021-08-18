<?php
/**
 * Created for oxrun
 * Author: Tobias Matthaiou <matthaiou@tobimat.eu>
 * Date: 07.06.17
 * Time: 07:46
 */

namespace Oxrun\Command\Module;

use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ReloadCommand extends Command
{
    /**
     * @var InputInterface
     */
    private $input = null;


    /**
     * @var ConsoleOutput
     */
    private $output = null;

    /**
     * @var OxrunContext OxrunContext
     */
    private $oxrunContext;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var array
     */
    private $configYaml;

    /**
     * ReloadCommand constructor.
     * @param OxrunContext $oxrunContext
     */
    public function __construct(
        OxrunContext $oxrunContext,
        ContextInterface $context
    )
    {
        $this->oxrunContext = $oxrunContext;
        $this->context = $context;

        parent::__construct();
    }


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
            ->addOption('based-on-config', 'c',InputOption::VALUE_REQUIRED, 'Checks if module is allowed to be reloaded based on the deploy:module-activator yaml file.')
        ;
    }

    /**
     *
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($input->getOption('based-on-config')) {
            try {
                $configYaml = $this->oxrunContext->getConfigYaml($input->getOption('based-on-config'));
                $this->configYaml = Yaml::parse($configYaml);
            } catch (\Exception $e) {
                $this->output->getErrorOutput()->writeln("<error>[Error] [{$input->getOption('based-on-config')}] {$e->getMessage()}</error>");
                exit(2);
            }
        }
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

        if ($this->isModuleAllowed() == false) {
            $this->output->writeln("<comment>({$this->context->getCurrentShopId()}) '{$input->getArgument('module')}' skip module reload on {$input->getOption('based-on-config')} </comment>");
            return 0;
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
     * @return bool
     */
    private function isModuleAllowed()
    {
        if (empty($this->input->getOption('based-on-config'))) {
            return true;
        }

        $shopId = $this->context->getCurrentShopId();
        $moduleId = $this->input->getArgument('module');

        $whitelist = $this->configYaml['whitelist'][$shopId] ?? null;
        $blacklist = $this->configYaml['blacklist'][$shopId] ?? null;

        if ($whitelist !== null && array_search($moduleId, $whitelist) !== false) {
            return true;
        }

        if ($blacklist !== null && array_search($moduleId, $blacklist) === false) {
            return true; //module is NOT on blacklist so is allowed
        }

        return false;
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
