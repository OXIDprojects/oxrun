<?php

/**
 * Created by Oxrun.
 * Autor: Tobias Matthaiou
 * Date: 16.08.21
 * Time: 13:57
 */

declare(strict_types=1);

$application = new \Symfony\Component\Console\Application('oxrun-light', '0.2');
$application->add(new \Oxrun\Command\Misc\RegisterCommand());
$application->add(new \Oxrun\Command\Misc\PhpstormMetadataCommand());
$application->add(new \Oxrun\Command\Misc\GenerateDocumentationCommand());
$application->run();
