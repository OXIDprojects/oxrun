<?php

namespace Oxrun\Command\Config;

use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use Oxrun\Core\EnvironmentManager;
use Oxrun\Core\OxrunContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

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
    private $oxrunContext;

    /**
     * @var EnvironmentManager
     */
    private $environments;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var bool
     */
    private $isChangeModuleSettings = false;

    /**
     * @inheritDoc
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
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('config:multiset')
            ->setDescription('Sets multiple configuration values that are not in module settings')
            ->addArgument('configfile', InputArgument::REQUIRED, 'The file containing the config values, see example/malls.yml.dist. (e.g. dev.yml, stage.yml, prod.yml)');

        $this->environments->addOptionToCommand($this);

        $help = <<<HELP
This command can import settings into the database that are not found in the module settings.
If they are module settings, they are stored in the module configuration yaml, not in the database.

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
    sMallShopURL: http://myshop.dev.local
    sMallSSLShopURL: http://myshop.dev.local
  2:
    blReverseProxyActive:
    ...
```
[Example: malls.yml.dist](example/malls.yml.dist)

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oe-console config:multiset $'config:\n  1:\n    foobar: barfoo\n' --shop-id=1
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
        $this->environments->init($input, $output);

        // now try to read YAML
        try {
            $mallYml = $this->oxrunContext->getConfigYaml($this->input->getArgument('configfile'));
            $mallValues = Yaml::parse($mallYml);
        } catch (\Exception $e) {
            $this->output->getErrorOutput()->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }

        if (empty($mallValues)) {
            $this->output->getErrorOutput()->writeln('<error>File ' . $this->input->getArgument('configfile') . ' is broken.</error>');
            return 1;
        }

        // Read Configration EnvironmentManager
        $environments = [];
        if (isset($mallValues['environment']) && is_array($mallValues['environment'])) {
            $environments = $mallValues['environment'];
        }
        $this->environments->load($this->environments->optionWith($environments));

        // Set configration
        if (is_array($mallValues['config'])) {
            $this->setConfigurations($mallValues['config']);
            $this->saveModuleConfigationYaml();
        } else {
            $this->output->getErrorOutput()->writeln('<error>No `config:` found in ' . $this->input->getArgument('configfile') . '</error>');
            return 1;
        }

        return 0;
    }

    /**
     * @param array $mallSettings
     */
    protected function setConfigurations($mallSettings)
    {
        // do not use the registry pattern (\OxidEsales\Eshop\Core\Registry::getConfig()) here, so we do not have any caches (breaks unit tests)
        $oxConfig = oxNew(\OxidEsales\Eshop\Core\Config::class);
        $consoleShopId = $this->input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) ? $this->input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) : 1;

        foreach ($mallSettings as $shopId => $configData) {
            if ($consoleShopId !== null && $shopId != $consoleShopId) {
                $this->output->writeln('<commit>Skip configration for Shop: ' . $shopId . '</commit>');
                continue;
            }
            foreach ($configData as $varName => $configValue) {
                $moduleId = '';
                if (!is_array($configValue)) {
                    // assume simple string
                    $varType = 'str';
                    $varValue = $configValue;
                } else {
                    $varType = $configValue['variableType'];
                    $varValue = $configValue['variableValue'];
                    if (isset($configValue['moduleId'])) {
                        $moduleId = $configValue['moduleId'];
                    }
                }

                if ($moduleId) {
                    list($devnull, $module) = explode(':', $moduleId);
                    if (empty($module)) {
                        $this->output->getErrorOutput()->writeln(sprintf(
                            'ModuleId not can not be detacted ShopId: %s, ModuleId: %s, VariableName: %s, VariableValue: %s',
                            $shopId, $moduleId, $varName, $varValue
                        ));
                    }
                    $this->environments->set($shopId, $module, $varType, $varName, $varValue);
                    $this->output->writeln("({$shopId}) Module Config <info>".trim(trim(Yaml::dump([$varName => $varValue], 0, 1), '{}'))."</info> will be saved.");
                    $this->isChangeModuleSettings = true;
                    //Do not save module configs in the database. Otherwise there is a huge chaos.
                    continue;
                }

                $oxConfig->saveShopConfVar(
                    $varType,
                    $varName,
                    $varValue,
                    $shopId
                );
                $this->output->writeln("({$shopId}) Config <info>".trim(trim(Yaml::dump([$varName => $varValue], 0, 1), '{}'))."</info> write into Database.");

            }
        }
    }

    protected function saveModuleConfigationYaml(): void
    {
        if ($this->isChangeModuleSettings ) {
            $this->environments->save();
            $this->output->writeln("<info>run now:</info> <comment>oe-console oe:module:apply-configuration</comment>");
        }
    }
}
