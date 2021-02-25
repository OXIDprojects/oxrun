<?php

namespace Oxrun\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Class EnvironmentManager
 * @package Oxrun\Core
 */
class EnvironmentManager
{
    /**
     * @var array
     */
    private $environments;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var OxrunContext
     */
    protected $oxrunContext;

    /**
     * @var string[]
     */
    private $optionNames = ['production', 'staging', 'development', 'testing'];

    /**
     * EnvironmentManager constructor.
     * @param OxrunContext $oxrunContext
     */
    public function __construct(OxrunContext $oxrunContext)
    {
        $this->oxrunContext = $oxrunContext;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    public function init(InputInterface $input, OutputInterface $output): EnvironmentManager
    {
        $this->input = $input;
        $this->output = $output;

        return $this;
    }

    public function addOptionToCommand(Command $command)
    {
        array_map(function ($name) use ($command) {
            $command->addOption($name, '', InputOption::VALUE_NONE, 'For "'.$name.'" system');
        }, $this->optionNames);
    }

    public function optionWith($environments = [])
    {
        array_walk($this->optionNames,
            function ($env) use (&$environments) {
                if ($this->input->getOption($env) && !in_array($env, $environments)) {
                    $environments[] = $env;
                }
            });

        return $environments;
    }

    /**
     * @return string|null
     */
    public function getActiveOption()
    {
        $environments = $this->getOptions();

        if (count($environments) > 1) {
            $this->output->getErrorOutput()->writeln('<comment>Too many options: --'.join(' --', $environments).'</comment>');
            return null;
        }

        return array_shift($environments);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return array_filter($this->optionNames, function ($name) {
            return $this->input->getOption($name);
        });
    }

    public function load($environments)
    {
        if (empty($environments)) {
            $this->loadProjectConfigurationEnvironment();
            return;
        }

        $configurationDirectory = $this->oxrunContext->getEnvironmentConfigurationDirectoryPath();

        $shopids = $this->getArgumentShopId();

        foreach ($environments as $environment) {
            foreach ($shopids as $shopid) {
                $file = new \SplFileInfo(
                    Path::join($configurationDirectory->getPathname(), sprintf('%s.%s.yaml', $environment, $shopid))
                );
                $this->addEnviromentYaml($file, $shopid);
            }
        }
    }

    /**
     * @param $shopId
     * @param $moduleId
     * @param $variableType
     * @param $variableName
     * @param $variableValue
     */
    public function set($shopId, $moduleId, $variableType, $variableName, $variableValue)
    {
        if (isset($this->environments[$shopId])) {
            foreach ($this->environments[$shopId] as $environmentYaml) {
                $environmentYaml->content['modules'][$moduleId]['moduleSettings'][$variableName]['type'] = $variableType;
                $environmentYaml->content['modules'][$moduleId]['moduleSettings'][$variableName]['value'] = $variableValue;
            }
        }
    }

    public function save()
    {
        $environmentsYaml = $this->environments['list'];

        if ($this->input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) && $this->input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) !== null) {
            $environmentsYaml = $this->environments[$this->input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME)];
        }

        foreach ($environmentsYaml as $yaml) {
            file_put_contents(
                $yaml->path,
                Yaml::dump($yaml->content, 6, 2)
            );

            $this->output->writeln("<info>updated:</info> <comment>{$yaml->path}</comment>");
        }
    }

    protected function loadProjectConfigurationEnvironment()
    {
        $shopids = $this->getArgumentShopId();

        $configurationDirectory = new \SplFileInfo(
            Path::join($this->oxrunContext->getProjectConfigurationDirectory(), 'shops')
        );

        foreach ($shopids as $shopid) {
            $file = new \SplFileInfo(
                Path::join($configurationDirectory->getPathname(), "{$shopid}.yaml")
            );
            $this->addEnviromentYaml($file, $shopid);
        }
    }


    /**
     * @param \SplFileInfo $configurationDirectory
     * @param $shopid
     */
    protected function addEnviromentYaml(\SplFileInfo $file, $shopid)
    {
        if ($file->isFile()) {
            $content = Yaml::parse(file_get_contents($file->getPathname()));
        } else {
            $content = ['modules' => []];
        }
        $this->environments['list'][] =
        $this->environments[$shopid][] =
            (object)['path' => $file->getPathname(), 'content' => $content];
    }

    /**
     * @return array
     */
    protected function getArgumentShopId(): array
    {
        $shopids = Registry::getConfig()->getShopIds();

        if ($this->input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) && $this->input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) !== null) {
            $shopids = [$this->input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME)];
        }

        return $shopids;
    }
}
