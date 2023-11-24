<?php

namespace Oxrun\Command\Cache;

use OxidEsales\Facts\Facts;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Path;

/**
 * Class ClearCommand
 * @package Oxrun\Command\Cache
 */
class ClearCommand extends Command
{

    /**
     * @var Facts
     */
    private $facts;

    /**
     * @var ?\OxidEsales\Eshop\Core\Cache\Generic\Cache
     */
    private $genericCache = null;

    /**
     * @var ?\OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache
     */
    private $dynamicContentCache = null;

    /**
     * ClearCommand constructor.
     * @param Facts|null $facts
     * @param \OxidEsales\Eshop\Core\Cache\Generic\Cache|null $genericCache
     * @param \OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache|null $dynamicContentCache
     */
    public function __construct(
        Facts $facts = null,
        $genericCache = null,
        $dynamicContentCache = null
    ) {
        $this->facts = $facts ?? new Facts();
        $this->genericCache = $genericCache;
        $this->dynamicContentCache = $dynamicContentCache;

        parent::__construct();
    }


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear OXID cache')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Try to delete the cache anyway. [danger or permission denied]');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $compileDir = $this->getCompileDir();
        if (!is_dir($compileDir)) {
            mkdir($compileDir);
        }

        if ($this->isLinuxSystem() && $input->getOption('force') == false) {
            $this->checkSameOwner($compileDir);
            $this->unixFastClear($compileDir);
        } else {
            $this->oneByOneClear($compileDir);
        }

        $this->enterpriseCache($output);

        $output->writeln('<info>Cache cleared.</info>');
        return 0;
    }

    /**
     * Find sCompileDir path without connect to DB.
     *
     * @return string
     */
    protected function getCompileDir()
    {
        $sourcePath = (new \OxidEsales\Facts\Facts())->getSourcePath();
        $configfile = Path::join($sourcePath, 'config.inc.php');

        if ($sourcePath && file_exists($configfile)) {
            $oxConfigFile = new \OxidEsales\Eshop\Core\ConfigFile($configfile);
            return $oxConfigFile->getVar('sCompileDir');
        }

        throw new FileNotFoundException("$configfile");
    }

    /**
     * @param $compileDir
     */
    protected function oneByOneClear($compileDir)
    {
        foreach (glob($compileDir . DIRECTORY_SEPARATOR . '*') as $filename) {
            if (!is_dir($filename)) {
                unlink($filename);
            }
        }
        foreach (glob($compileDir . DIRECTORY_SEPARATOR . 'smarty' . DIRECTORY_SEPARATOR . '*') as $filename) {
            if (!is_dir($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * @param $compileDir
     */
    protected function unixFastClear($compileDir)
    {
        $compileDir = escapeshellarg(rtrim($compileDir, '/\\'));
        // Fast Process: Move folder and create new folder
        passthru("mv ${compileDir} ${compileDir}_old && mkdir -p ${compileDir}/smarty");
        // Low Process delete folder on slow HD
        passthru("rm -Rf ${compileDir}_old");
    }

    /**
     * @return bool
     */
    protected function isLinuxSystem()
    {
        return (PHP_SHLIB_SUFFIX == 'so');
    }

    /**
     * Check has Process same Owner permission
     *
     * @param $compileDir
     * @throws \Exception
     */
    protected function checkSameOwner($compileDir)
    {
        $owner = fileowner($compileDir);
        $current_owner = posix_getuid();
        if ($current_owner != $owner) {
            global $argv;
            $owner = posix_getpwuid($owner);
            throw new \Exception(
                "Please run command as `${owner['name']}` user." . PHP_EOL .
                "    sudo -u ${owner['name']} " .
                join(' ', $argv)
            );
        }
    }

    /**
     * Clear Cache form a Enterprise Edtion
     */
    protected function enterpriseCache(OutputInterface $output)
    {
        if ($this->facts->isEnterprise() == false) {
            return;
        }

        if ($this->getApplication() instanceof \Oxrun\Application\OxrunLight) {
            $output->writeln(
                '<comment>[Info] The enterprise cache could not be cleared. ' .
                'Goes only via the command `oe-console cache:clear`.</comment>',
                OutputInterface::VERBOSITY_NORMAL
            );
            return;
        }

        try {
            $this->getGenericCache()->flush();
            $output->writeln('<info>Generic\Cache is cleared</info>');

            $this->getDynamicContentCache()->reset(true);
            $output->writeln('<info>DynamicContent\Cache is cleared</info>');

        } catch (\Exception $e) {
            $output->writeln('<error>Only enterprise cache could\'t be cleared: ' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @return \OxidEsales\Eshop\Core\Cache\Generic\Cache
     */
    private function getGenericCache()
    {
        if ($this->genericCache === null) {
            $this->genericCache = new \OxidEsales\Eshop\Core\Cache\Generic\Cache();
        }
        return $this->genericCache;
    }

    /**
     * @return \OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache
     */
    private function getDynamicContentCache()
    {
        if ($this->dynamicContentCache === null) {
            $this->dynamicContentCache = new \OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache();
        }

        return $this->dynamicContentCache;
    }
}
