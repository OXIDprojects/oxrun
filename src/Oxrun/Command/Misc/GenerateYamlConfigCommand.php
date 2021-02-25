<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 2019-03-02
 * Time: 16:39
 */

namespace Oxrun\Command\Misc;

use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
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
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var MulitSetConfigConverter
     */
    private $mulitSetConfigConverter;

    /**
     * @inheritDoc
     */
    public function __construct(
        OxrunContext $context,
        EnvironmentManager $environmentManager,
        QueryBuilderFactoryInterface $queryBuilderFactory,
        MulitSetConfigConverter $mulitSetConfigConverter
    ) {
        $this->oxrunContext = $context;
        $this->environments = $environmentManager;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->mulitSetConfigConverter = $mulitSetConfigConverter;

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

        if ($input->hasOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME) && $shopId = $input->getOption(Executor::SHOP_ID_PARAMETER_OPTION_NAME)) {
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

        $qb = $this->queryBuilderFactory->create();

        $qb->select("oxvarname, oxvartype, {$decodeValueQuery} as oxvarvalue, oxmodule")
            ->from('oxconfig')
            ->where('OXSHOPID = :oxshopid')
            ->setParameter('oxshopid', $shopId);

        //--oxvarname
        if ($option = $input->getOption('oxvarname')) {
            $list = $this->convertParamList($option);
            $qb->andWhere("oxvarname IN (:oxvarname)")
                ->setParameter('oxvarname', $list, Connection::PARAM_STR_ARRAY);
        } else {
            $qb->andWhere('NOT oxvarname IN (:oxvarname)')
                ->setParameter('oxvarname', $this->ignoreVariablen, Connection::PARAM_STR_ARRAY);
        }

        //--oxmodule
        if ($option = $input->getOption('oxmodule')) {
            $list = $this->convertParamList($option, 'module:');
            $qb->andWhere("oxmodule IN (:oxmodule)")
                ->setParameter('oxmodule', $list, Connection::PARAM_STR_ARRAY);
        }

        $yamlConf = [];
        $dbConf = $qb->execute()->fetchAll();

        array_map(function ($row) use (&$yamlConf) {
            $converd = $this->mulitSetConfigConverter->convert($row);
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
     * @param $input
     * @param string $prefix
     * @return array|string[]
     */
    protected function convertParamList($input, $prefix = '')
    {
        $list = explode(',', $input);
        $list = array_map('trim', $list);
        if ($prefix) {
            $list = array_map(function ($item) use ($prefix) { return strpos($item, $prefix) === false ? $prefix . $item : $item; }, $list);
        }

        return $list;
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
