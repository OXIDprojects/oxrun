<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 27.11.18
 * Time: 07:34
 */
namespace Oxrun\CommandCollection;

use Oxrun\CommandCollection;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

/**
 * Class ContainerCollection
 * @package Oxrun\CommandCollection
 */
class ContainerCollection implements CommandCollection
{
    /**
     * @var string
     */
    private $shopDir = '';

    /**
     * @var string
     */
    private $oxrunconfDir = '';

    /**
     * @var string
     */
    private static $template = '';

    /**
     * @var bool
     */
    private $isFoundShopDir = false;

    /**
     * @var CommandFinder
     */
    private $commandFinder = null;

    /**
     * @var OutputInterface
     */
    private $consoleOutput = null;

    /**
     * ContainerCollection constructor.
     * @param CommandFinder $commandFinder
     */

    public function __construct(CommandFinder $commandFinder, OutputInterface $output = null)
    {
        $this->commandFinder = $commandFinder;
        $this->consoleOutput = $output ? : new ConsoleOutput();
    }

    /**
     * @param \Oxrun\Application $application
     * @throws \Exception
     */
    public function addCommandTo(\Oxrun\Application $application)
    {
        $this->isFoundShopDir = $application->bootstrapOxid(false);

        if ($this->isFoundShopDir) {
            $this->shopDir = $application->getShopDir();
            $this->oxrunconfDir = $application->getOxrunConfigPath();
            static::$template = $this->shopDir . '/../vendor/oxidprojects';
        }

        /** @var DICollection $commandContainer */
        $commandContainer = $this->getContainer()->get('command_container');
        $commandContainer->addCommandTo($application);
    }

    /**
     * @return Container
     * @throws \Exception
     */
    protected function getContainer()
    {
        $containerCache = new ConfigCache(static::getContainerPath(), true);
        if (!$containerCache->isFresh()) {
            $this->buildContainer($containerCache);
        }

        if (!in_array('oxidprojects\OxrunCommands', get_declared_classes())) {
            include static::getContainerPath();
        }

        return new \oxidprojects\OxrunCommands();
    }

    /**
     * @param ConfigCache $containerCache
     */
    protected function buildContainer($containerCache)
    {
        $symfonyContainer = new ContainerBuilder();
        
        $symfonyContainer->setDefinition(
            'command_container',
            (new Definition(DICollection::class))->setPublic(true)
        );

        $this->findCommands($symfonyContainer);

        $symfonyContainer->compile();

        $phpDumper = new PhpDumper($symfonyContainer);

        $containerCache->write(
            $phpDumper->dump([
                'class' => 'OxrunCommands',
                'namespace' => 'oxidprojects',
            ]),
            CacheCheck::getResource()
        );
    }

    /**
     * @return string
     */
    public static function getContainerPath()
    {
        if (static::$template == '') {
            static::$template = sys_get_temp_dir();
        }

        return static::$template . '/OxrunCommands.php';
    }

    /**
     * @return void
     */
    public static function destroyCompiledContainer()
    {
        @unlink(static::getContainerPath());
    }

    /**
     * @param ContainerBuilder $symfonyContainer
     */
    protected function findCommands($symfonyContainer)
    {
        if ($this->isFoundShopDir) {
            try {
                $aggregators = $this->commandFinder->getPassNeedShopDir();
                $this->addPassToContainer($aggregators, $symfonyContainer);
            } catch (\Exception $e) {
                $this->consoleOutput->writeln('<comment>Own commands error: '.$e->getMessage().'</comment>');
            }
        }

        //Oxrun Commands
        $aggregators = $this->commandFinder->getPass();
        $this->addPassToContainer($aggregators, $symfonyContainer);
    }

    /**
     * @param Aggregator[] $aggregators
     * @param ContainerBuilder $symfonyContainer
     *
     * @throws \Exception
     */
    protected function addPassToContainer($aggregators, $symfonyContainer)
    {
        foreach ($aggregators as $pass) {
            $pass->setShopDir($this->shopDir);
            $pass->setOxrunConfigDir($this->oxrunconfDir);
            $pass->setConsoleOutput($this->consoleOutput);
            $pass->valid();

            $symfonyContainer->addCompilerPass($pass);
        }
    }
}
