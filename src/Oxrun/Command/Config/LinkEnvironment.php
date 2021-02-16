<?php

namespace Oxrun\Command\Config;

use OxidEsales\Eshop\Core\Registry;
use Oxrun\Core\EnvironmentManager;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Class LinkEnvironment
 * @package Oxrun\Command\Config
 */
class LinkEnvironment extends Command
{
    /**
     * @var OxrunContext
     */
    protected $oxrunContext;

    /**
     * @var EnvironmentManager
     */
    private $environments;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * LinkEnvironment constructor.
     * @param OxrunContext $context
     */
    public function __construct(
        OxrunContext $context,
        EnvironmentManager $environments
    ) {
        $this->oxrunContext = $context;
        $this->environments = $environments;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('config:link:environment')
            ->setDescription('Links the environment configration files. Ideal for CI/CD')
            ->setHelp("In files structure you has multiple files per shop in var/configuration/environment directory. e.g. production.1.yaml, staging.1.yaml" . PHP_EOL .
                            "This might be useful when deploying files to some specific environment." . PHP_EOL .
                            "@see: [Modules configuration deployment](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/project/module_configuration/modules_configuration_deployment.html#dealing-with-environment-files)");

        $this->environments->addOptionToCommand($this);
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->environments->init($input, $output);

        $shopIds = [$this->input->getOption('shop-id')];
        if ($this->input->getOption('shop-id') === null) {
            $shopIds = Registry::getConfig()->getShopIds();
        }

        $environment = $this->environments->getActiveOption();
        if (empty($environment)) {
            $this->output->getErrorOutput()->writeln('<error>Please use one option of --production, --staging, --development, --testing</error>');
            return 1;
        }

        $configrationDirector = $this->oxrunContext->getEnvironmentConfigurationDirectoryPath();

        foreach ($shopIds as $shopId) {
            $environmentYaml = new \SplFileInfo(Path::join($configrationDirector->getPathname(), sprintf('%s.%s.yaml', $environment, $shopId)));
            if (!$environmentYaml->isFile()) {
                $this->output->getErrorOutput()->writeln("<comment>File not found: 'environment/{$environmentYaml->getBasename()}'</comment>");
                continue;
            }

            $shopYaml = new \SplFileInfo(Path::join($configrationDirector->getPathname(), sprintf('%s.yaml', $shopId)));

            if ($shopYaml->isFile()) {
                $this->output->writeln("<info>remove old link 'environment/{$shopYaml->getBasename()}'</info>", OutputInterface::VERBOSITY_VERBOSE);
                @unlink($shopYaml->getPathname());
            }

            if (@symlink($environmentYaml->getBasename(), $shopYaml->getPathname())) {
                $this->output->writeln("<info>Link created 'environment/{$shopYaml->getBasename()}' -> 'environment/{$environmentYaml->getBasename()}'</info>");
            } else {
                $this->output->getErrorOutput()->writeln("<comment>Cound't created link 'environment/{$shopYaml->getBasename()}'</comment>");
            };
        }

    }
}
