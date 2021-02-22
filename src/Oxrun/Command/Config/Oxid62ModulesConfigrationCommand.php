<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 18.02.21
 * Time: 10:54
 */

namespace Oxrun\Command\Config;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use Oxrun\Application;
use Oxrun\Helper\AnalyzeModuleMetadata;
use Oxrun\Traits\NeedDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Class Oxid62ModulesConfigrationCommand
 * @package Oxrun\Command\Config
 */
class Oxid62ModulesConfigrationCommand extends Command implements \Oxrun\Command\EnableInterface
{
    use NeedDatabase;

    /**
     * @var string[]
     */
    private $optionNames = ['production', 'staging', 'development', 'testing'];

    /**
     * @var array
     */
    private $moduleConfigrations;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var \SplFileInfo
     */
    private $envDir;

    /**
     * @var AnalyzeModuleMetadata
     */
    private $analyzeModuleMetadata;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('config:oxid62:modules-configuration')
            ->setDescription('Creates the modules configurations for OXID eSale v6.2.x. Ideal for upgrade')
            ->addOption('force', '', InputOption::VALUE_NONE, 'Trozdem Einstellungen Speichern, wenn sie nicht vorhanden sind in den Module Settings')
            ->setHelp(
                'With this command modules-configuration can be created.. ' . PHP_EOL .
                'Which will be needed later when updating to >6.2' . PHP_EOL .
                'Otherwise the settings will be lost.' . PHP_EOL .
                'See [Module configuration deployment](https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/project/module_configuration/modules_configuration_deployment.html)'
            );

        array_map(function ($name) {
            $this->addOption($name, '', InputOption::VALUE_NONE, 'Valid for "' . $name . '" system');
        }, $this->optionNames);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $enviromentOptions = $this->getEnviromentOptions();

        if (empty($enviromentOptions)) {
            $this->output->getErrorOutput()->writeln('<error>Please use one of this option --' . join(' --', $this->optionNames) . '</error>');
            return 1;
        }
        $this->initModuleMetadata();

        $this->initEnviromentDir();

        $this->loadModuleConfigrations();

        $this->showMessages();

        return $this->saveEnviroments($enviromentOptions) ? 0 : 1;
    }

    /**
     * @return array
     */
    public function getEnviromentOptions(): array
    {
        return array_filter($this->optionNames, function ($name) {
            return $this->input->getOption($name);
        });
    }

    protected function initModuleMetadata()
    {
        /** @var Application $application */
        $application = $this->getApplication();

        $this->analyzeModuleMetadata = new AnalyzeModuleMetadata($application->getShopDir() . '/modules');
    }

    /**
     * @see https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/project/module_configuration/modules_configuration.html
     */
    protected function initEnviromentDir()
    {
        /** @var \Oxrun\Application $application */
        $application = $this->getApplication();
        $shopDir = $application->getShopDir();

        $this->envDir = new \SplFileInfo(Path::join($shopDir, '..', 'var', 'configuration', 'environment'));

        if (!$this->envDir->isDir()) {
            @mkdir($this->envDir->getPathname(), 0775, true);
        }
    }

    protected function loadModuleConfigrations()
    {
        $decodeValueQuery = Registry::getConfig()->getDecodeValueQuery('oxvarvalue');

        $result = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->select(
            "SELECT OXSHOPID, OXMODULE, OXVARTYPE, OXVARNAME, $decodeValueQuery as 'OXVARVALUE' FROM oxconfig WHERE OXMODULE LIKE 'module:%' ORDER BY OXSHOPID, OXMODULE, OXVARNAME"
        )->fetchAll();

        foreach ($result as $rowconfig) {
            $moduleId = $this->convertModuleId($rowconfig['OXMODULE']);

            if ($this->analyzeModuleMetadata->existsModule($moduleId) == false) {
                $this->addMessage('noModule', [$moduleId]);
                if ($this->input->getOption('force') == false) {
                    continue;
                }
            }

            if ($this->analyzeModuleMetadata->existsModuleSetting($moduleId, $rowconfig['OXVARNAME']) == false) {
                $this->addMessage('noSetting', [$moduleId, $rowconfig['OXVARNAME']]);
                if ($this->input->getOption('force') == false) {
                    continue;
                }
            }

            $variableValue = $this->valueConvert($rowconfig['OXVARTYPE'], $rowconfig['OXVARVALUE']);

            $this->addModuleConfigration($rowconfig['OXSHOPID'], $moduleId, $rowconfig['OXVARNAME'], $rowconfig['OXVARTYPE'], $variableValue);
        }
    }

    protected function addMessage($key, $values)
    {
        $value = join('::', $values);
        if (!isset($this->messages[$key]) || !in_array($value, $this->messages[$key])) {
            $this->messages[$key][] = $value;
        }
    }

    protected function showMessages()
    {
        if (empty($this->messages)) {
            return;
        }

        if (!empty($this->messages['noModule'])) {
            $this->output->writeln('WARN: We have module settings in the DB and no a module exits for them.');
            $this->output->writeln('  - Module Setting: '.join(PHP_EOL . "  - Module Setting: ", $this->messages['noModule']));
            $sql = 'DELETE FROM oxconfig WHERE OXMODULE IN ("module:'. join('", "module:', $this->messages['noModule'] ).'");';
            $this->output->writeln("Use SQL to fix: <comment>$sql</comment>");
        }

        if (!empty($this->messages['noSetting'])) {
            $this->output->writeln('WARN: We have settings in the DB where are no in module used.');
            $this->output->writeln('  - Module Setting: '.join(PHP_EOL . "  - Module Setting: ", $this->messages['noSetting']));
            $wheres = [];
            foreach ($this->messages['noSetting'] as $noSetting) {
                list($moduleId, $moduleSetting) = explode('::', $noSetting);
                $wheres[] = "(OXMODULE = 'module:{$moduleId}' AND OXVARNAME = '{$moduleSetting}')";
            }
            $sql = 'DELETE FROM oxconfig WHERE ' . join(' OR ', $wheres) . ';';
            $this->output->writeln("Use SQL to fix: <comment>$sql</comment>");
        }

        if ($this->input->getOption('force')) {
            $this->output->writeln('These settings were recorded anyway');
        }

    }

    /**
     * @param $rowModuleId
     * @return mixed|string
     */
    protected function convertModuleId($rowModuleId)
    {
        list($devnull, $moduleId) = explode(':', $rowModuleId, 2);

        return $moduleId;
    }

    /**
     * @param $rawVartype
     * @param $rawVarvalue
     */
    protected function valueConvert($rawVartype, $rawVarvalue)
    {
        $value = $rawVarvalue;

        switch ($rawVartype) {
            case 'arr':
            case 'aarr':
                $value = unserialize($rawVarvalue);
                break;
            case 'bool':
                $value = (bool)($value == 'true' || $value == '1');
                break;
            default:
                if (strpos($rawVarvalue, 'a:') === 0) {
                    $value = unserialize($rawVarvalue);
                }
        }

        return $value;
    }

    /**
     * @param $shopId
     * @param $moduleId
     * @param $variableName
     * @param $variableValue
     */
    public function addModuleConfigration($shopId, $moduleId, $variableName, $variableType, $variableValue)
    {
        if (!isset($this->moduleConfigrations[$shopId])) {
            $this->moduleConfigrations[$shopId] = ['modules' => []];
        }

        $this->moduleConfigrations[$shopId]['modules'][$moduleId]['moduleSettings'][$variableName] = ['type'=> $variableType, 'value' => $variableValue];
    }

    /**
     * @param array $enviromentOptions
     */
    public function saveEnviroments($enviromentOptions)
    {
        $success = true;

        foreach ($this->moduleConfigrations as $shopId => $shopConfig) {
            $yamlContent = Yaml::dump($shopConfig, 6, 2);
            foreach ($enviromentOptions as $environment) {
                $yamlPath = Path::join($this->envDir->getPathname(), sprintf('%s.%s.yaml', $environment, $shopId));
                $saved = file_put_contents(
                    $yamlPath,
                    $yamlContent
                );
                if ($saved) {
                    $this->output->writeln("<info>{$yamlPath} is saved</info>");
                } else {
                    $this->output->getErrorOutput()->writeln("<comment>Could not be saved: {$yamlPath}</comment>");
                    $success = false;
                }
            }
        }

        return $success;
    }
}
