<?php
/**
 * Created by oxrun.
 * Autor: Tobias Matthaiou <tm@loberon.de>
 * Date: 23.09.17
 * Time: 23:04
 */

namespace Oxrun\Command\Route;

use OxidEsales\Eshop\Core\SeoDecoder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DebugCommand
 * @package Oxrun\Command\Route
 */
class DebugCommand extends Command
{
//    use NeedDatabase;

    /**
     * @var array
     */
    protected $paramMap = [
        'cl'  => 'Controller',
        'fnc' => 'Method'
    ];

    /**
     * @var string
     */
    protected $filenametoCopy = '';

    /**
     *
     */
    protected function configure()
    {
        $this->setName("route:debug")
            ->setDescription("Returns the route. Which controller and parameters are called.")
            ->addArgument("url", InputArgument::REQUIRED, "Website URL. Full or Path")
            ->addOption('copy', 'c', InputOption::VALUE_NONE, 'Copy file path from the class to the clipboard (only MacOS)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $this->cleanUrlPath($input);

        $output->writeln("<info>Results for:</info> $url");

        /** @var \oxSeoDecoder $oxSeoDecoder */
        $oxSeoDecoder = \oxNew(SeoDecoder::class);
        $aSeoURl = $oxSeoDecoder->decodeUrl($url);

        if ($aSeoURl == false) {
            $output->writeln('<error>URL not found in oxseo</error>');
            return 2;
        }

        $table = new Table($output);

        $this->addInfos($table, $aSeoURl);
        $this->addClassInfos($table, $aSeoURl);

        $table->render();

        if ($input->getOption('copy')) {
            if ($this->copyFilePath()) {
                $output->writeln('<comment>File path has been copied.</comment>');
            };
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @return mixed|string
     */
    protected function cleanUrlPath(InputInterface $input)
    {
        $url = $input->getArgument('url');
        $url = parse_url($url, PHP_URL_PATH);
        $url = preg_replace('~^/+~', '', $url);
        $url .= preg_match('~/$~', $url) ? '' : '/';
        return $url;
    }

    /**
     * @param Table $table
     * @param $aSeoURl
     */
    protected function addInfos($table, $aSeoURl)
    {
        $table->setHeaders(['Key', 'Value']);

        foreach ($aSeoURl as $key => $value) {
            if (isset($this->paramMap[$key])) {
                $key = $this->paramMap[$key];
            }
            $table->addRow([$key, $value]);
        }
    }

    /**
     * @param Table $table
     * @param $aSeoURl
     */
    protected function addClassInfos($table, $aSeoURl)
    {
        $shopDir = INSTALLATION_ROOT_PATH;
        $file = '';
        $reflectionClass = null;

        if (isset($aSeoURl['cl'])) {
            try {
                $controllerClass = oxNew($aSeoURl['cl']);
                $reflectionClass = new \ReflectionClass($controllerClass);
                $fileName = $reflectionClass->getFileName();
                $file = str_replace($shopDir, '', $fileName);
                $this->filenametoCopy = $file;
            } catch (\Exception $e) {
                $file = "<error> " . $e->getMessage() . " </error>";
            }
        }

        if (isset($aSeoURl['fnc']) && $reflectionClass) {
            try {
                $reflectionMethod = $reflectionClass->getMethod($aSeoURl['fnc']);
                $file .= ":" . $reflectionMethod->getStartLine();
                $this->filenametoCopy = $file;
            } catch (\Exception $e) {
                $file .= " <error> " . $e->getMessage() . " </error>";
            }
        }

        $table->addRow([new TableSeparator(['colspan' => 2])]);
        $table->addRow(['File:', $file]);
    }

    /**
     *
     */
    protected function copyFilePath()
    {
        @exec('which pbcopy', $output, $return);
        if ($return > 0) {
            throw new \Exception('Can not copy filename on this system. `'.$this->filenametoCopy.'`');
        }

        $pbcopy = popen('pbcopy', 'w');
        fwrite($pbcopy, $this->filenametoCopy);
        pclose($pbcopy);

        return true;
    }

}
