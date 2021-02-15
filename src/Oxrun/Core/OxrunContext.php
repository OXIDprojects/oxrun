<?php
/**
 * Created by LoberonEE.
 * Autor: Tobias Matthaiou <tm@loberon.de>
 * Date: 28.01.21
 * Time: 14:01
 */

namespace Oxrun\Core;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Webmozart\PathUtil\Path;

/**
 * Class OxrunContext
 *
 * @package Oxrun\Core
 * @mixin \OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext
 */
class OxrunContext
{
    /**
     * @var \OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext
     */
    private $basicContext = null;

    /**
     * @inheritDoc
     */
    public function __construct(BasicContextInterface $basicContext)
    {
        $this->basicContext = $basicContext;
    }

    /**
     * INSTALLATION_ROOT_PATH/var/oxrun_config/
     *
     * @return string
     */
    public function getOxrunConfigPath()
    {
        $base = Path::join($this->basicContext->getShopRootPath(), 'var', 'oxrun_config');
        $base .= DIRECTORY_SEPARATOR;

        if (file_exists($base) == false) {
            mkdir($base, 0755, true);
        }

        return $base;
    }

    public function getConfigYaml($ymlString)
    {
        // is it a file?
        if (strpos(strtolower($ymlString), '.yml') !== false
            || strpos(strtolower($ymlString), '.yaml') !== false
        ) {
            $basePath = $this->getOxrunConfigPath();
            $ymlFile = $basePath . $ymlString;

            if (file_exists($ymlFile)) {
                $ymlString = file_get_contents($ymlFile);
            } else {
                throw new FileNotFoundException(null, 0, null, $ymlFile);
            }
        }

        return $ymlString;
    }

    /**
     * @inheritDoc
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->basicContext, $name], $arguments);
    }
}
