<?php

namespace Oxrun\Command\Cache;

use OxidEsales\Eshop\Core\Registry;
use Oxrun\Traits\NoNeedDatabase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Class ClearCommand
 * @package Oxrun\Command\Cache
 */
class ClearCommand extends Command implements \Oxrun\Command\EnableInterface
{

    use NoNeedDatabase;

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
    }

    /**
     * Find sCompileDir path without connect to DB.
     *
     * @return string
     */
    protected function getCompileDir()
    {
        $oxidPath = $this->getApplication()->getShopDir();
        $configfile = $oxidPath . DIRECTORY_SEPARATOR . 'config.inc.php';

        if ($oxidPath && file_exists($configfile)) {
            $oxConfigFile = new \OxConfigFile($configfile);
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
            throw new \Exception("Please run command as `${owner['name']}` user." . PHP_EOL . "    sudo -u ${owner['name']} " . join(' ', $argv));
        }
    }

    /**
     * Clear Cache form a Enterprise Edtion
     */
    protected function enterpriseCache(OutputInterface $output)
    {
        if (class_exists('\OxidEsales\Facts\Facts') == false) {
            return;
        }

        if ((Registry::get(\OxidEsales\Facts\Facts::class))->isEnterprise() == false) {
            return;
        }

        try {
            Registry::get('OxidEsales\Eshop\Core\Cache\Generic\Cache')->flush();
            $output->writeln('<info>Generic\Cache is cleared</info>');

            Registry::get('OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache')->reset(true);
            $output->writeln('<info>DynamicContent\Cache is cleared</info>');

        } catch (\Exception $e) {
            $output->writeln('<error>Only enterprise cache could\'t be cleared: '.$e->getMessage().'</error>');
        }
    }
}
