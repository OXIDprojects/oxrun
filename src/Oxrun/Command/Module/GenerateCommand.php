<?php

namespace Oxrun\Command\Module;

use OxidEsales\Facts\Facts;
use Oxrun\GenerateModule\CreateModule;
use Oxrun\GenerateModule\InteractModuleForm;
use Oxrun\GenerateModule\ModuleSpecification;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateCommand
 * @package Oxrun\Command\Module
 */
class GenerateCommand extends Command
{

    /**
     * @var string
     */
    protected $defaultTemplateRepo = 'https://github.com/OXIDprojects/oxid-module-skeleton/archive/v6_module.zip';

    /**
     * Configures the current command.
     *
     * @var ModuleSpecification
     */
    private $moduleSpecification;

    protected function configure()
    {
        $this
            ->setName('module:generate')
            ->setDescription('Generates a module skeleton')
            ->addOption('skeleton', 's', InputOption::VALUE_REQUIRED, 'Zip of a Oxid Module Skeleton', $this->defaultTemplateRepo)
        ;

        InteractModuleForm::addCommandOptions($this);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty($this->moduleSpecification)) {
            throw new \InvalidArgumentException('Please use the interactive mode');
        }

        $app = $this->getApplication();
        $oxSourcePath = (new Facts())->getSourcePath();
        $oxInstallationPath = (new Facts())->getShopRootPath();

        $skeletonUri = $input->getOption('skeleton');

        $output->writeln("Module will be create ... <info>please wait</info> ...");
        $output->writeln(" - Use Template: $skeletonUri", OutputInterface::VERBOSITY_VERBOSE);

        $createModule = new CreateModule($oxSourcePath, $app->getName(), $app->getVersion());
        $createModule->run($skeletonUri, $this->moduleSpecification);

        $destinationPath = $this->moduleSpecification->getDestinationPath($oxSourcePath);
        $namespace = $this->moduleSpecification->getNamespace();

        $output->write("Enable new namespace <comment>`$namespace`</comment>, ");
        $output->writeln('run <comment>`composer dumpautoload`</comment> into <comment>`' . $oxInstallationPath . '`</comment>');
        $output->writeln("<info>Module is stored at <comment>{$destinationPath}</comment></info>");

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->moduleSpecification = new ModuleSpecification();

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $form = new InteractModuleForm(
            $this->moduleSpecification,
            $helper,
            $input,
            $output
        );

        $form
            ->askModuleName()
            ->askVendor()
            ->askDescription()
            ->askAuthor()
            ->askEmail()
        ;

        //Validate a throw
        $this->moduleSpecification->getPlaceholders();
    }
}
