<?php
/**
 * Created by LoberonEE.
 * Autor: Tobias Matthaiou <tm@loberon.de>
 * Date: 28.01.21
 * Time: 14:01
 */

namespace Oxrun\Traits;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Trait OxrunConfigPool
 *
 * @package Oxrun\Traits
 */
trait OxrunConfigPool
{
    /**
     * INSTALLATION_ROOT_PATH/oxrun_config/
     *
     * @return string
     */
    protected function getOxrunConfigPath()
    {
        $DS = DIRECTORY_SEPARATOR;
        $base = INSTALLATION_ROOT_PATH . "{$DS}oxrun_config{$DS}";

        if (file_exists($base) == false) {
            mkdir($base, 0755, true);
        }

        return $base;
    }

    protected function getConfigYaml($ymlString)
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
                throw new FileNotFoundException($ymlFile);
            }
        }

        return $ymlString;
    }
}
