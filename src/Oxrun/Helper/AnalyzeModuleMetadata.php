<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 22.02.21
 * Time: 14:00
 */

namespace Oxrun\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class AnalyzeModuleMetadata
 * @package Oxrun\Helper
 */
class AnalyzeModuleMetadata
{

    private $moduleDir;

    private $moduleIds = [];

    private $moduleSettingNames = [];

    /**
     * AnalyzeModuleMetadata constructor.
     *
     * @param string $moduleDir
     * @param OutputInterface $output
     */
    public function __construct($moduleDir)
    {
        $this->moduleDir = $moduleDir;
        $this->init();
    }

    protected function init()
    {
        $metadataFiles = (new Finder())->files()->name('metadata.php')->depth(2)->in($this->moduleDir);
        foreach ($metadataFiles as $metadata) {
            $aModule = [];
            $sMetadataVersion = [];

            include $metadata->getPathname();

            if (!isset($aModule['id'])) {
                continue;
            }

            $this->moduleIds[] = $aModule['id'];

            if (!isset($aModule['settings'])) {
                continue;
            }

            foreach ($aModule['settings'] as $setting) {
                $this->moduleSettingNames[] = $aModule['id'] . '__' . $setting['name'];
            }
        }
    }

    /**
     * @param $moduleId
     */
    public function existsModule($moduleId)
    {
        return (bool)in_array($moduleId, $this->moduleIds);
    }

    /**
     * @param $moduleId
     */
    public function existsModuleSetting($moduleId, $param)
    {
        return (bool)in_array("{$moduleId}__{$param}", $this->moduleSettingNames);
    }

}
