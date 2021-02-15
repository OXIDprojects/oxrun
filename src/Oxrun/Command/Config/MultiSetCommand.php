<?php

namespace Oxrun\Command\Config;

use OxidEsales\Eshop\Core\Registry;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Class MultiSetCommand
 * Can be used to set multiple oxconfig values for multiple subshops by providing a
 * YAML file containing the values per shop id.
 *
 * @package Oxrun\Command\Config
 * @see example/malls.yml.dist
 */
class MultiSetCommand extends Command
{

    /**
     * @var OxrunContext
     */
    private $context = null;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;
    private $environments;

    /**
     * @inheritDoc
     */
    public function __construct(
        OxrunContext $context
    )
    {
        $this->context = $context;

        parent::__construct('config:multiset');
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('config:multiset')
            ->setDescription('Sets multiple config values from yaml file')
            ->addOption('production', '', InputOption::VALUE_NONE, 'For "production" system')
            ->addOption('staging', '', InputOption::VALUE_NONE, 'For "staging" system')
            ->addOption('development', '', InputOption::VALUE_NONE, 'For "development" system')
            ->addOption('testing', '', InputOption::VALUE_NONE, 'For "testing" system')
            ->addArgument('configfile', InputArgument::REQUIRED, 'The file containing the config values, see example/malls.yml.dist. (e.g. dev.yml, stage.yml, prod.yml)');

        $help = <<<HELP
The file path is relative to the shop installation_root_path/var/oxrun_config/.
You can also pass a YAML string on the command line.

To create YAML use command `oe-console misc:generate:yaml:multiset --help`

<info>YAML example:</info>
```yaml
environment:
  - "production"
  - "staging"
  - "development"
  - "testing"
config:
  1:
    blReverseProxyActive:
      variableType: bool
      variableValue: false
    # simple string type
    sMallShopURL: http://myshop.dev.local
    sMallSSLShopURL: http://myshop.dev.local
    myMultiVal:
      variableType: aarr
      variableValue:
        - /foo/bar/
        - /bar/foo/
      # optional module id
      moduleId: my_module
  2:
    blReverseProxyActive:
...
```
[Example: malls.yml.dist](example/malls.yml.dist)

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oe-console config:multiset $'config:\n  1:\n    foobar: barfoo\n' --shopId=1
```
HELP;
        $this->setHelp($help);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        // now try to read YAML
        try {
            $mallYml = $this->context->getConfigYaml($this->input->getArgument('configfile'));
            $mallValues = Yaml::parse($mallYml);
        } catch (\Exception $e) {
            $this->output->getErrorOutput()->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }

        if (empty($mallValues)) {
            $this->output->getErrorOutput()->writeln('<error>File ' . $this->input->getArgument('configfile') . ' is broken.</error>');
            return 1;
        }

        // Read Configration Environments
        $environments = [];
        if (isset($mallValues['environment']) && is_array($mallValues['environment'])) {
            $environments = $mallValues['environment'];
        }
        $this->loadEnvironment($this->optionEnvironmentWith($environments));

        // Set configration
        if (is_array($mallValues['config'])) {
            $this->setConfigurations($mallValues['config']);
            $this->saveEnvironments();
        } else {
            $this->output->getErrorOutput()->writeln('<error>No `config:` found in ' . $this->input->getArgument('configfile') . '</error>');
            return 1;
        }

        return 0;
    }

    protected function loadEnvironment($environments)
    {
        if (empty($environments)) {
            $this->loadProjectConfigurationEnvironment();
            return;
        }

        $configurationDirectory = new \SplFileInfo(
            Path::join($this->context->getConfigurationDirectoryPath(), 'environment')
        );

        if (!$configurationDirectory->isDir()) {
            @mkdir($configurationDirectory->getPathname(), 775);
        }

        $shopids = [$this->input->getOption('shop-id')];
        if ($this->input->getOption('shop-id') === null) {
            $shopids = Registry::getConfig()->getShopIds();
        }

        foreach ($environments as $environment) {
            foreach ($shopids as $shopid) {
                $file = new \SplFileInfo(
                    Path::join($configurationDirectory->getPathname(), sprintf('%s.%s.yaml', $environment, $shopid))
                );
                $this->addEnviromentYaml($file, $shopid);
            }
        }
    }

    protected function loadProjectConfigurationEnvironment()
    {
        $shopids = [$this->input->getOption('shop-id')];
        if ($this->input->getOption('shop-id') === null) {
            $shopids = Registry::getConfig()->getShopIds();
        }

        $configurationDirectory = new \SplFileInfo(
            Path::join($this->context->getProjectConfigurationDirectory(), 'shops')
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

    protected function optionEnvironmentWith($environments)
    {
        $optionNames = ['production', 'staging', 'development', 'testing'];
        array_walk($optionNames,
            function ($env) use (&$environments) {
                if ($this->input->getOption($env) && !in_array($env, $environments)) {
                    $environments[] = $env;
                }
            });

        return $environments;
    }

    /**
     * @param array $mallSettings
     */
    protected function setConfigurations($mallSettings)
    {
        // do not use the registry pattern (\OxidEsales\Eshop\Core\Registry::getConfig()) here, so we do not have any caches (breaks unit tests)
        $oxConfig = oxNew(\OxidEsales\Eshop\Core\Config::class);
        $consoleShopId = $this->input->getOption('shop-id');

        foreach ($mallSettings as $shopId => $configData) {
            if ($consoleShopId !== null && $shopId != $consoleShopId) {
                $this->output->writeln('<commit>Skip configration for Shop: ' . $shopId . '</commit>');
                continue;
            }
            foreach ($configData as $configKey => $configValue) {
                $moduleId = '';
                if (!is_array($configValue)) {
                    // assume simple string
                    $variableType = 'str';
                    $variableValue = $configValue;
                } else {
                    $variableType = $configValue['variableType'];
                    $variableValue = $configValue['variableValue'];
                    if (isset($configValue['moduleId'])) {
                        $moduleId = $configValue['moduleId'];
                    }
                }

                if ($moduleId) {
                    $this->setEnvironmentConfigrationYaml($shopId, $moduleId, $configKey, $variableValue);
                }

                $oxConfig->saveShopConfVar(
                    $variableType,
                    $configKey,
                    $variableValue,
                    $shopId,
                    $moduleId
                );

                $this->output->writeln("<info>Config {$configKey} for shop {$shopId} set to " . print_r($variableValue, true) . "</info>");
            }
        }
    }

    /**
     * @param $shopId
     * @param $moduleId
     * @param $variableName
     * @param $variableValue
     */
    protected function setEnvironmentConfigrationYaml($shopId, $moduleId, $variableName, $variableValue)
    {
        list($devnull, $module) = explode(':', $moduleId);
        if (empty($module)) {
            $this->output->getErrorOutput()->writeln(sprintf(
                'ModuleId not can not be detacted ShopId: %s, ModuleId: %s, VariableName: %s, VariableValue: %s',
                $shopId, $moduleId, $variableName, $variableValue
            ));
        }

        if (isset($this->environments[$shopId])) {
            foreach ($this->environments[$shopId] as $environmentYaml) {
                $environmentYaml->content['modules'][$module]['moduleSettings'][$variableName]['value'] = $variableValue;
            }
        }
    }

    protected function saveEnvironments()
    {
        $environmentsYaml = $this->environments['list'];
        if ($this->input->getOption('shop-id') !== null) {
            $environmentsYaml = $this->environments[$this->input->getOption('shop-id')];
        }

        foreach ($environmentsYaml as $yaml) {
            file_put_contents(
                $yaml->path,
                Yaml::dump($yaml->content, 6, 2)
            );
        }
    }
}
