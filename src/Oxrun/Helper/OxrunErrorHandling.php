<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 2019-05-05
 * Time: 04:45
 */

namespace Oxrun\Helper;

use Oxrun\CommandCollection\ContainerCollection;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class OxrunErrorHandling
 * @package Oxrun\Helper
 * @codeCoverageIgnore
 */
class OxrunErrorHandling
{
    public static function shutdown()
    {
        $handledErrorTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR, E_USER_ERROR];

        $error = error_get_last();
        if (in_array($error['type'], $handledErrorTypes)) {
            $consoleOutput = new ConsoleOutput();
            $consoleOutput->writeln("<error>{$error['message']}</error>");
            exit(2);
        }
    }

    public static function handleUncaughtException($exception)
    {
        $consoleOutput = new ConsoleOutput();

        self::isContainerErrorRecreate($exception->getFile());

        $consoleOutput->writeln("<error>$exception</error>");
        exit(2);
    }

    /**
     * Try to recreate container is a fatal error in OxrunCommands.php
     *
     * @param $exception
     * @param $exit_code
     */
    protected static function isContainerErrorRecreate($filename)
    {
        if (basename($filename) == basename(ContainerCollection::getContainerPath())) {
            ContainerCollection::destroyCompiledContainer();

            if (getenv('AUTORESTART') != 1) {
                passthru('AUTORESTART=1 ' . implode(" ", $_SERVER['argv']), $exit_code);
                exit($exit_code);
            }
        }
    }
}
