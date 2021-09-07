<?php

/**
 * Created by Oxrun.
 * Autor: Tobias Matthaiou
 * Date: 16.08.21
 * Time: 13:57
 */

declare(strict_types=1);

namespace Oxrun\Application;

/**
 * Class OxrunLight
 * @package Oxrun\Application
 */
class OxrunLight extends \Symfony\Component\Console\Application
{
    /**
     * @inheritDoc
     */
    public function getHelp()
    {
        $version = parent::getHelp();
        return <<<TAG
$version

-----------------------------------------------------------------------
<comment>  Is a light console line tool for OXID eSale.
  These commands don't need an active OXID eSale database connection.</comment>
-----------------------------------------------------------------------
TAG;
    }

}

$application = new OxrunLight('oxrun-light', '0.3');
$application->add(new \Oxrun\Command\Cache\ClearCommand());
$application->add(new \Oxrun\Command\Misc\RegisterCommand());
$application->add(new \Oxrun\Command\Misc\PhpstormMetadataCommand());
$application->add(new \Oxrun\Command\Misc\GenerateDocumentationCommand());
$application->run();
