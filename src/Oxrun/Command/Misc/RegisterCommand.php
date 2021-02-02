<?php

/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 27.01.21
 * Time: 16:25
 */

namespace Oxrun\Command\Misc;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class RegisterCommand
 * @package Oxrun\Command\Misc
 */
class RegisterCommand extends Command
{

    /**
     * @var int
     */
    private $yamlInline = 4;

    /**
     * @var array[]
     */
    protected $service_yaml = [ 'services' => [] ];

    /**
     * @var \RecursiveIteratorIterator
     */
    private $commandsPhps = [];

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var BasicContext
     */
    private $basicContext;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->basicContext = new BasicContext();
        $this->setName('misc:register:command')
            ->setDescription(
            'Extends the service.yaml file with the commands. So that they are found in oe-console.')
            ->addArgument(
            'command-dir',
            InputArgument::REQUIRED,
        'The folder where the commands are located or Module with option --isModule')
            ->addOption(
                'isModule',
            '',
                InputOption::VALUE_NONE,
                'Just write the Module and the path and the service-yaml will be found automatically.'
            )
            ->addOption(
                'service-yaml',
                's',
                InputOption::VALUE_REQUIRED,
                'The service.yaml file that will be updated (default: var/configuration/configurable_services.yaml)',
                $this->basicContext->getConfigurableServicesFilePath()
            )
            ->addOption(
                'yaml-inline',
                'y',
                InputOption::VALUE_REQUIRED,
                'The level where you switch to inline YAML',
                $this->yamlInline
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandDir = $input->getArgument('command-dir');
        $serviceYaml = $input->getOption('service-yaml');
        $this->yamlInline = $input->getOption('yaml-inline');
        $this->output = $output;


        if ($input->getOption('isModule')) {
            list($commandDir, $serviceYaml) = $this->moduleContext($commandDir);
        }

        $commandDir = new \SplFileInfo($commandDir);
        if ($commandDir->isDir() === false) {
            $output->writeln('<error>' . $commandDir->getPath() . ' is not a Folder </error>');
            return 2;
        }

        $serviceYaml = new \SplFileInfo($serviceYaml);
        if ($serviceYaml->isFile()) {
            $this->loadYamel($serviceYaml);
        }

        $serviceYamlContent = $this->find($commandDir)->extract()->sort()->getServiceYaml();

        $output->writeln('<info>' . $serviceYaml->getPathname() . ' was updated</info>');
        $output->writeln('<comment>Please start command clear:cache</comment>');

        return file_put_contents($serviceYaml->getPathname(), $serviceYamlContent) !== false ? 0 : 2;
    }

    private function moduleContext($commandDir)
    {
        $moduleDir = $this->basicContext->getModulesPath() . DIRECTORY_SEPARATOR . $commandDir;
        $serviceYaml = $moduleDir . DIRECTORY_SEPARATOR .'services.yaml';

        $moduleDirCommands = (new Finder())
            ->name('Command*')
            ->directories()
            ->in($moduleDir);

        if ($moduleDirCommands->hasResults() == false) {
            $this->output->writeln('<error>No Commands found in Module. Has a `Commands/*Command.php`-Folder?</error>');
            return 2;
        }

        foreach ($moduleDirCommands as $folder) {
            return [$folder->getPathname(), $serviceYaml];
        }
    }


    /**
     * @param \SplFileInfo $commandDir
     * @return $this
     */
    private function find($commandDir)
    {
        $this->commandsPhps = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $commandDir->getPathname(),
                \FilesystemIterator::SKIP_DOTS
            )
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function extract()
    {
        $loaded_services = join('$7$', array_keys($this->service_yaml['services']));

        /** @var \SplFileInfo $item */
        foreach ($this->commandsPhps as $item) {

            $class = $this->extractClassName($item);

            if (empty($class)) {
                if (strpos($loaded_services, $item->getBasename('.php')) === false) {
                    $this->output->writeln('<error>' . $item->getBasename('.php') . ' has not loaded Class' . '</error>');
                }
                continue;
            }

            $this->addCommand($class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function sort()
    {
        asort($this->service_yaml['services']);
        return $this;
    }

    /**
     * @return string
     */
    public function getServiceYaml(): string
    {
        return \Symfony\Component\Yaml\Yaml::dump($this->service_yaml, $this->yamlInline);
    }

    /**
     * @param \SplFileInfo $file
     */
    private function extractClassName($file)
    {
        if (__FILE__ == $file->getRealPath()) {
            return self::class;
        }

        $classesBefore = get_declared_classes();

        include_once $file->getRealPath();

        $classesAfter = get_declared_classes();

        $newClasses = array_diff($classesAfter, $classesBefore);

        return array_shift($newClasses);
    }

    /**
     * @param $class
     * @param array $service_yaml
     */
    private function addCommand($class)
    {
        try {
            $this->service_yaml['services'][$class] = [
                'tags' => [[
                    'name' => 'console.command',
                    'command' => $this->extractCommandName($class)
                ]]
            ];
        } catch (\Exception $e) {
            $this->output->writeln('<comment>' . $e->getMessage() . '</comment>');

        }

    }

    private function extractCommandName($class)
    {
        /** @var \Symfony\Component\Console\Command\Command $consoleCommand */
        $consoleCommand = new $class;
        if ( ! $consoleCommand instanceof Command) {
            throw new \Exception($class . ' is not a ' . Command::class);
        }
        return $consoleCommand->getName();
    }

    /**
     * @param \SplFileInfo $serviceYaml
     */
    private function loadYamel(\SplFileInfo $serviceYaml)
    {
        try {
            $this->service_yaml = Yaml::parse(file_get_contents($serviceYaml->getPathname()));
            if (isset($this->service_yaml['services']) == false) {
                $this->service_yaml['services'] = [];
            }
        } catch (\Exception $exception) {

        }
    }
}
