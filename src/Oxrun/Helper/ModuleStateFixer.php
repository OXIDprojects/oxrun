<?php
/**
 * Created for oxrun
 * Author: Stefan Moises <moises@shoptimax.de>
 * Most of the code in this file was taken from OXID Console package.
 *
 * (c) Eligijus Vitkauskas <eligijusvitkauskas@gmail.com>
 */

namespace Oxrun\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use OxidEsales\Eshop\Core\Module\ModuleInstaller;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\EshopCommunity\Core\Config;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\SettingsHandler;

use OxidEsales\Eshop\Core\Module\ModuleCache;

/**
 * Module state fixer
 */
class ModuleStateFixer extends ModuleInstaller
{
    /** @var OutputInterface $_debugOutput */
    protected $_debugOutput;
    /**
     * Fix module states task runs version, extend, files, templates, blocks,
     * settings and events information fix tasks
     *
     * @param Module $oModule
     * @param Config|null $oConfig If not passed uses default base shop config
     */
    public function fix(Module $oModule, Config $oConfig = null)
    {
        if ($oConfig !== null) {
            $this->setConfig($oConfig);
        }
        $sModuleId = $oModule->getId();
        // $this->_deleteBlock($sModuleId); // Disabled as it is handled by $this->setTemplateBlocks()
        $this->_deleteTemplateFiles($sModuleId);
        $this->_deleteModuleFiles($sModuleId);
        $this->_deleteModuleEvents($sModuleId);
        $this->_addExtensions($oModule);
        $this->setTemplateBlocks($oModule->getInfo("blocks"), $sModuleId);
        $this->_addModuleFiles($oModule->getInfo("files"), $sModuleId);
        $this->_addTemplateFiles($oModule->getInfo("templates"), $sModuleId);
        $settingsHandler = oxNew(SettingsHandler::class);
        $settingsHandler->setModuleType('module')->run($oModule);
        $this->_addModuleVersion($oModule->getInfo("version"), $sModuleId);
        $this->_addModuleEvents($oModule->getInfo("events"), $sModuleId);
        /** @var ModuleCache $oModuleCache */
        $oModuleCache = oxNew(ModuleCache::class, $oModule);
        $oModuleCache->resetCache();
    }
    public function setDebugOutput(OutputInterface $o)
    {
        $this->_debugOutput = $o;
    }
    /**
     * Add extension to module
     *
     * @param Module $oModule
     */
    protected function _addExtensions(Module $oModule)
    {
        $aModulesDefault = $this->getConfig()->getConfigParam('aModules');
        $aModules = $this->getModulesWithExtendedClass();
        $aModules = $this->_removeNotUsedExtensions($aModules, $oModule);
        if ($oModule->hasExtendClass()) {
            $aAddModules = $oModule->getExtensions();
            $aModules = $this->_mergeModuleArrays($aModules, $aAddModules);
        }
        $aModules = $this->buildModuleChains($aModules);
        if ($aModulesDefault != $aModules) {
            $onlyInAfterFix = array_diff($aModules, $aModulesDefault);
            $onlyInBeforeFix = array_diff($aModulesDefault, $aModules);
            if ($this->_debugOutput) {
                $this->_debugOutput->writeLn("[INFO] fixing " . $oModule->getId());
                foreach ($onlyInAfterFix as $core => $ext) {
                    if ($oldChain = $onlyInBeforeFix[$core]) {
                        $newExt = substr($ext, strlen($oldChain));
                        if (!$newExt) {
                            //$newExt = substr($ext, strlen($oldChain));
                            $this->_debugOutput->writeLn("[INFO] remove ext for $core");
                            $this->_debugOutput->writeLn("[INFO] old: $oldChain");
                            $this->_debugOutput->writeLn("[INFO] new: $ext");
                            //$this->_debugOutput->writeLn("[ERROR] extension chain is corrupted for this module");
                            //return;
                            continue;
                        } else {
                            $this->_debugOutput->writeLn("[INFO] append $core => ...$newExt");
                        }
                        unset($onlyInBeforeFix[$core]);
                    } else {
                        $this->_debugOutput->writeLn("[INFO] add $core => $ext");
                    }
                }
                foreach ($onlyInBeforeFix as $core => $ext) {
                    $this->_debugOutput->writeLn("[INFO] remove $core => $ext");
                }
            }
            $this->_saveToConfig('aModules', $aModules);
        }
    }

    /**
     * @param $aInstalledExtensions
     * @param $aarGarbage
     * @deprecated function not found any more in v6.0
     */
    protected function _removeGarbage($aInstalledExtensions, $aarGarbage)
    {
        if ($this->_debugOutput) {
            foreach ($aarGarbage as $moduleId => $aExt) {
                $this->_debugOutput->writeLn("[INFO] removing garbage for module $moduleId: " . join(',', $aExt));
            }
        }
        return parent::_removeGarbage($aInstalledExtensions, $aarGarbage);
    }
    /**
     * Add module templates to database.
     *
     * @deprecated please use setTemplateBlocks this method will be removed because
     * the combination of deleting and adding does unnessery writes and so it does not scale
     * also it's more likely to get race conditions (in the moment the blocks are deleted)
     *
     * @param array  $moduleBlocks Module blocks array
     * @param string $moduleId     Module id
     */
    protected function _addTemplateBlocks($moduleBlocks, $moduleId)
    {
        $this->setTemplateBlocks($moduleBlocks, $moduleId);
    }
    /**
     * Set module templates in the database.
     * we do not use delete and add combination because
     * the combination of deleting and adding does unnessery writes and so it does not scale
     * also it's more likely to get race conditions (in the moment the blocks are deleted)
     * @todo extract oxtplblocks query to ModuleTemplateBlockRepository
     *
     * @param array  $moduleBlocks Module blocks array
     * @param string $moduleId     Module id
     */
    protected function setTemplateBlocks($moduleBlocks, $moduleId)
    {
        if (!is_array($moduleBlocks)) {
            $moduleBlocks = array();
        }
        $shopId = $this->getConfig()->getShopId();
        $db = DatabaseProvider::getDb();
        $knownBlocks = ['dummy']; // Start with a dummy value to prevent having an empty list in the NOT IN statement.
        foreach ($moduleBlocks as $moduleBlock) {
            $blockId = md5($moduleId . json_encode($moduleBlock) . $shopId);
            $knownBlocks[] = $blockId;
            $template = $moduleBlock["template"];
            $position = isset($moduleBlock['position']) && is_numeric($moduleBlock['position']) ?
                intval($moduleBlock['position']) : 1;
            $block = $moduleBlock["block"];
            $filePath = $moduleBlock["file"];
            $sql = "INSERT INTO `oxtplblocks` (`OXID`, `OXACTIVE`, `OXSHOPID`, `OXTEMPLATE`, `OXBLOCKNAME`, `OXPOS`, `OXFILE`, `OXMODULE`)
                     VALUES (?, 1, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                      `OXID` = VALUES(OXID),
                      `OXACTIVE` = VALUES(OXACTIVE),
                      `OXSHOPID` = VALUES(OXSHOPID),
                      `OXTEMPLATE` = VALUES(OXTEMPLATE),
                      `OXBLOCKNAME` = VALUES(OXBLOCKNAME),
                      `OXPOS` = VALUES(OXPOS),
                      `OXFILE` = VALUES(OXFILE),
                      `OXMODULE` = VALUES(OXMODULE)";
            $db->execute($sql, array(
                $blockId,
                $shopId,
                //$theme,
                $template,
                $block,
                $position,
                $filePath,
                $moduleId
            ));
        }
        $listOfKnownBlocks = join(',', $db->quoteArray($knownBlocks));
        $deleteblocks = "DELETE FROM oxtplblocks WHERE OXSHOPID = ? AND OXMODULE = ? AND OXID NOT IN ({$listOfKnownBlocks});";
        $db->execute(
            $deleteblocks,
            array($shopId, $moduleId)
        );
    }
    /**
     * FIX that moduleid is used instead of modulpath https://github.com/OXID-eSales/oxideshop_ce/pull/333
     * Filter module array using module id
     *
     * @param array  $aModules  Module array (nested format)
     * @param string $sModuleId Module id/folder name
     *
     * @return array
     */
    protected function _filterModuleArray($aModules, $sModuleId)
    {
        $aModulePaths = $this->getConfig()->getConfigParam('aModulePaths');
        $sPath = $aModulePaths[$sModuleId];
        if (!$sPath) {
            $sPath = $sModuleId;
        }
        $sPath .= "/";
        $aFilteredModules = array();
        foreach ($aModules as $sClass => $aExtend) {
            foreach ($aExtend as $sExtendPath) {
                if (strpos($sExtendPath, $sPath) === 0) {
                    $aFilteredModules[$sClass][] = $sExtendPath;
                }
            }
        }
        return $aFilteredModules;
    }
}
