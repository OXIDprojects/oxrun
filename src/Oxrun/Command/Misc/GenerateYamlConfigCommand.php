<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 2019-03-02
 * Time: 16:39
 */

namespace Oxrun\Command\Misc;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use Oxrun\Core\EnvironmentManager;
use Oxrun\Core\OxrunContext;
use Oxrun\Helper\MulitSetConfigConverter;
use Oxrun\Helper\MultiSetTranslator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * Class GenerateYamlMultiSetCommand
 * @package Oxrun\Command\Misc
 */
class GenerateYamlConfigCommand extends Command
{

    protected $ignoreVariablen = [
        'aDisabledModules', // aus 6.1
        'aLegacyModules', // aus 4.x
        'activeModules',
        'aModuleControllers',
        'aModuleEvents',
        'aModuleExtensions',
        'aModuleFiles',
        'aModulePaths',
        'aModules',
        'aModuleTemplates',
        'aModuleVersions',
        'blModuleWasEnabled',
        'moduleSmartyPluginDirectories',
        'sUtilModule',
        'sDefaultLang',
        'aLanguages',
        'aLanguageURLs',
        'aLanguageParams',
        'IMA',
        'IMD',
        'IMS',
        'sCustomTheme',
        'sTheme',
    ];

    /**
     * @var OxrunContext
     */
    private $oxrunContext = null;

    /**
     * @var EnvironmentManager
     */
    private $environments;

    /**
     * @inheritDoc
     */
    public function __construct(
        OxrunContext $context,
        EnvironmentManager $environmentManager

    ) {
        $this->oxrunContext = $context;
        $this->environments = $environmentManager;
        parent::__construct('misc:generate:yaml:config');
    }


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('misc:generate:yaml:config')
            ->addOption('configfile', 'c', InputOption::VALUE_REQUIRED, 'The Config file to change or create if not exits', 'dev_config.yml')
            ->addOption('oxvarname', '', InputOption::VALUE_REQUIRED, 'Dump configs by oxvarname. One name or as comma separated List')
            ->addOption('oxmodule', '', InputOption::VALUE_REQUIRED, 'Dump configs by oxmodule. One name or as comma separated List')
            ->addOption('no-descriptions', '-d', InputOption::VALUE_NONE, 'No descriptions are added.')
            ->addOption('language', '-l', InputOption::VALUE_REQUIRED, 'Speech selection of the descriptions.', 0)
            ->addOption('list', '', InputOption::VALUE_NONE, 'list all saved configrationen')
            ->setDescription('Generate a Yaml File for command `config:multiset`');

        $this->environments->addOptionToCommand($this);
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list')) {
            $this->listfolder($output);
            return 0;
        }

        $this->environments->init($input, $output);

        $yaml = ['environment' => [], 'config' => []];

        $path = $this->getSavePath($input);
        if (file_exists($path)) {
            $yaml = Yaml::parse(file_get_contents($path));
        }

        $yaml['environment'] = $this->environments->getOptions();

        $config = Registry::getConfig();
        $shopIds = $config->getShopIds();

        if ($shopId = $input->getOption('shop-id')) {
            $shopIds = [$shopId];
        }

        foreach ($shopIds as $id) {
            if (isset($yaml['config'][$id]) == false) {
                $yaml['config'][$id] = [];
            }

            $dbConfig = $this->getConfigFromShop($id, $input);
            $yaml['config'][$id] = array_merge($yaml['config'][$id], $dbConfig);
            ksort($yaml['config'][$id]);
        }

        ksort($yaml['config']);

        $yamltxt = Yaml::dump($yaml, 5, 2);
        if ($input->getOption('no-descriptions') == false) {
            $multiSetTranslator = new MultiSetTranslator(2);
            $yamltxt = $multiSetTranslator->configFile($yamltxt, $input->getOption('language'));
        }

        file_put_contents($path, $yamltxt);

        $output->writeln("<comment>Config saved. use `oe-console config:multiset " . $input->getOption('configfile') . "`</comment>");
        return 0;
    }

    /**
     * @param $shopId
     */
    protected function getConfigFromShop($shopId, InputInterface $input)
    {
        $decodeValueQuery = Registry::getConfig()->getDecodeValueQuery();

        $SQL = "SELECT oxvarname, oxvartype, {$decodeValueQuery} as oxvarvalue, oxmodule
                    FROM oxconfig
                    WHERE oxshopid = ?";

        if ($option = $input->getOption('oxvarname')) {
            $SQL .= $this->andWhere('oxvarname', $option);
        } else {
            $ignore = implode("', '", $this->ignoreVariablen);
            $SQL .= " AND NOT oxvarname IN ('$ignore')";
        }

        if ($option = $input->getOption('oxmodule')) {
            $SQL .= $this->andWhere('oxmodule', $option, 'module:');
        }

        $dbConf = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll($SQL, [$shopId]);
        $yamlConf = [];

        $map = new MulitSetConfigConverter();
        array_map(function ($row) use (&$yamlConf, $map) {
            $converd = $map->convert($row);
            $yamlConf[$converd['key']] = $converd['value'];
        }, $dbConf);

        ksort($yamlConf);
        return $yamlConf;
    }

    /**
     * @return string
     */
    public function getSavePath(InputInterface $input)
    {
        $filename = $input->getOption('configfile');

        $oxrunConfigPath = $this->oxrunContext->getOxrunConfigPath();

        if (false == preg_match('/\.ya?ml$/', $filename)) {
            $filename .= '.yml';
        }

        $input->setOption('configfile', $filename);

        return Path::join($oxrunConfigPath, $filename);
    }

    /**
     * @param string $column
     * @param string $input
     * @param string $prefix Automatically sets a prefix if it does not exist.
     * @return string
     */
    protected function andWhere($column, $input, $prefix = '')
    {
        $list = explode(',', $input);
        $list = array_map('trim', $list);
        if ($prefix) {
            $list = array_map(function ($item) use ($prefix) { return strpos($item, $prefix) === false ? $prefix . $item : $item; }, $list);
        }
        $list = DatabaseProvider::getDb()->quoteArray($list);
        $list = implode(',', $list);

        return " AND $column IN ($list)";
    }

    /**
     * @param $output
     */
    protected function listfolder($output)
    {
        $configPath = $this->oxrunContext->getOxrunConfigPath();

        $yamls = (new Finder())->files()->name('/\.ya?ml/i')->in($configPath);

        $table = new Table($output);
        $table->setHeaders([$configPath]);
        foreach ($yamls as $yaml) {
            $table->addRow([str_replace($configPath, '', $yaml->getPathname())]);
        }
        $table->render();
    }
}
