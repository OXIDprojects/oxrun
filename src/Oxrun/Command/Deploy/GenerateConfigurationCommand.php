<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 2019-03-02
 * Time: 16:39
 */

namespace Oxrun\Command\Deploy;

use Doctrine\DBAL\Connection;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Executor;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Oxrun\Core\EnvironmentManager;
use Oxrun\Core\OxrunContext;
use Oxrun\Helper\FileStorage;
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
class GenerateConfigurationCommand extends Command
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
     * @var FileStorage
     */
    private $fileStorage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @inheritDoc
     */
    public function __construct(
        OxrunContext $context,
        EnvironmentManager $environmentManager,
        QueryBuilderFactoryInterface $queryBuilderFactory,
        MulitSetConfigConverter $mulitSetConfigConverter,
        FileStorage $fileStorage
    ) {
        $this->oxrunContext = $context;
        $this->environments = $environmentManager;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->mulitSetConfigConverter = $mulitSetConfigConverter;
        $this->fileStorage = $fileStorage;

        parent::__construct('misc:generate:yaml:config');
    }


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('deploy:generate:configuration')
            ->setAliases(['misc:generate:yaml:config'])
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update an exited config file, with data from DB')
            ->addOption('configfile', 'c', InputOption::VALUE_REQUIRED, 'The config file to update or create if not exits', 'dev_config.yml')
            ->addOption('oxvarname', '', InputOption::VALUE_REQUIRED, 'Dump configs by oxvarname. One name or as comma separated List')
            ->addOption('oxmodule', '', InputOption::VALUE_REQUIRED, 'Dump configs by oxmodule. One name or as comma separated List')
            ->addOption('no-descriptions', '-d', InputOption::VALUE_NONE, 'No descriptions are added.')
            ->addOption('language', '-l', InputOption::VALUE_REQUIRED, 'Speech selection of the descriptions.', 0)
            ->addOption('list', '', InputOption::VALUE_NONE, 'list all saved configrationen')
            ->setDescription('Generate a yaml with configuration from Database. For command `deploy:config`')
            ->setHelp(
                'Configration that is not included in the modules can be saved. ' .
                'With the command: deploy:config they can be read again'
            );

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
        $this->output = $output;
        $this->input = $input;

        if ($input->getOption('list')) {
            $this->listfolder($output);
            return 0;
        }

        $this->environments->init($input, $output);

        $yaml = ['environment' => [], 'config' => []];

        $path = $this->getSavePath();
        if (file_exists($path)) {
            $yaml = Yaml::parse($this->fileStorage->getContent($path));
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

            $dbConfig = $this->getConfigFromShop($id);
            $yaml['config'][$id] = array_merge($yaml['config'][$id], $dbConfig);
            ksort($yaml['config'][$id]);
        }

        ksort($yaml['config']);

        $yamltxt = Yaml::dump($yaml, 5, 2);
        if ($input->getOption('no-descriptions') == false) {
            $multiSetTranslator = new MultiSetTranslator(2);
            $yamltxt = $multiSetTranslator->configFile($yamltxt, $input->getOption('language'));
        }

        $this->fileStorage->save($path, $yamltxt);

        $output->writeln("<comment>Config saved. use `oe-console deploy:config " . $input->getOption('configfile') . "`</comment>");
        return 0;
    }

    /**
     * @param $shopId
     */
    protected function getConfigFromShop($shopId)
    {
        $decodeValueQuery = Registry::getConfig()->getDecodeValueQuery();

        $qb = $this->queryBuilderFactory->create();
        $queryVarnameModule = '';

        $qb->select("oxvarname, oxvartype, {$decodeValueQuery} as oxvarvalue, oxmodule")
            ->from('oxconfig')
            ->where('OXSHOPID = :oxshopid')
            ->setParameter('oxshopid', $shopId);

        //--update
        if ($this->input->getOption('update')) {
            $extraVarnames = $this->findVarnamesConfigFile($shopId);
            $this->fileStorage->noUseGlobalArgv();
            $this->output->writeln("({$shopId}) Varnames: <comment>{$extraVarnames}</comment>", OutputInterface::VERBOSITY_VERBOSE);
        }

        //--oxvarname
        if (($option = $this->input->getOption('oxvarname')) || $extraVarnames !== null) {
            $option = trim($option . ',' . $extraVarnames, ',');
            $list = $this->convertParamList($option);
            $queryVarnameModule = $qb->expr()->in("oxvarname", ":oxvarname");
            $qb->setParameter('oxvarname', $list, Connection::PARAM_STR_ARRAY);
        } else {
            $qb->andWhere('NOT oxvarname IN (:oxvarname)')
                ->setParameter('oxvarname', $this->ignoreVariablen, Connection::PARAM_STR_ARRAY);
        }

        //--oxmodule
        if ($option = $this->input->getOption('oxmodule')) {
            $list = $this->convertParamList($option, 'module:');
            $queryVarnameModule .= $queryVarnameModule ? " OR " : "";
            $queryVarnameModule .= $qb->expr()->in("oxmodule", ":oxmodule");
            $qb->setParameter('oxmodule', $list, Connection::PARAM_STR_ARRAY);
        }

        if ($queryVarnameModule) {
            $qb->andWhere($queryVarnameModule);
        }

        $yamlConf = [];
        $dbConf = $qb->execute()->fetchAll();

        $this->output->writeln(sprintf("(%s) <info>%s configs found</info>", $shopId, count($dbConf)));

        array_map(function ($row) use (&$yamlConf) {
            $converd = $this->mulitSetConfigConverter->convert($row);
            $yamlConf[$converd['key']] = $converd['value'];
        }, $dbConf);

        ksort($yamlConf);

        return $yamlConf;
    }

    /**
     * @param $shopId
     * @return string|null
     */
    protected function findVarnamesConfigFile($shopId)
    {
        try {
            $path = $this->getSavePath();
            $yaml = Yaml::parseFile($path);
        } catch (\Exception $e) {
            return null;
        }

        if (isset($yaml['config']) == false || isset($yaml['config'][$shopId]) == false) {
            return null;
        }

        $varnames = [];

        foreach ($yaml['config'][$shopId] as $varname => $varvalue) {
            $varnames[] = $varname;
        }

        if (empty($varname)) {
            return "empty_list_oxrun_configuration";
        }

        return join(',', $varnames);
    }

    /**
     * @return string
     */
    public function getSavePath()
    {
        $filename = $this->input->getOption('configfile');

        if (false == preg_match('/\.ya?ml$/', $filename)) {
            $filename .= '.yml';
            $this->input->setOption('configfile', $filename);
        }

        $oxrunConfigPath = $this->oxrunContext->getOxrunConfigPath();

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

        $list = [];
        foreach ($yamls as $yaml) {
            $list[] = [str_replace($configPath, '', $yaml->getPathname())];
        }
        sort($list);

        $table = new Table($output);
        $table->setHeaders([$configPath]);
        $table->setRows($list);
        $table->render();
    }
}
