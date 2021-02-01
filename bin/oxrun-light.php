#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 27.01.21
 * Time: 16:25
 */

namespace Oxrun;

$search_autoloader = [
    __DIR__ . "/../vendor/autoload.php",
    __DIR__ . "/../../../../vendor/autoload.php",
];

$isAutoloaderFound = null;

foreach ($search_autoloader as $autoloader) {
    if (file_exists($autoloader)) {
        $isAutoloaderFound = include $autoloader;
    }
}

if ($isAutoloaderFound === null) {
    echo "Please run `composer install`" . PHP_EOL;
    exit(2);
}

$application = new \Symfony\Component\Console\Application('oxrun-light', '0.1');
$application->add(new \Oxrun\Command\Misc\RegisterCommand);
$application->run();
