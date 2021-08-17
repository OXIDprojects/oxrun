<?php
/**
 * Autor: Tobias Matthaiou
 * Date: 01.02.21
 * Time: 10:22
 */

namespace oxrun\test;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Oxrun\Core\OxrunContext;

$_POST['shp'] = 1;

$bootstrapFilePath = '/var/www/oxid-esale/source/bootstrap.php';

require_once $bootstrapFilePath;

function OxrunContext(): OxrunContext
{
    global $oxrunContext;

    if (empty($oxrunContext)) {
        $oxrunContext = new OxrunContext(
            ContainerFactory::getInstance()->getContainer()->get(BasicContextInterface::class)
        );
    }

    return $oxrunContext;
}
