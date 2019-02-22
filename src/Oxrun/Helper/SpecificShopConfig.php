<?php

/*
 * Created for oxrun
 * Author: Stefan Moises <moises@shoptimax.de>
 * Some code in this file was taken from OXID Console package.
 *
 * (c) Eligijus Vitkauskas <eligijusvitkauskas@gmail.com>
 */
 
namespace Oxrun\Helper;

use oxconnectionexception;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;

/**
 * Specific shop config class
 *
 * Helper class for generating Config instance for specific shop
 */
class SpecificShopConfig extends Config
{

    /**
     * @var int
     */
    protected $_iShopId;

    /**
     * Constructor
     *
     * @param $iShopId
     */
    public function __construct($iShopId)
    {
        $this->_iShopId = $iShopId;
        $this->init();
    }
    
    /**
     * Returns config arrays for all shops
     *
     * @return SpecificShopConfig[]
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseNotConfiguredException
     */
    public static function getAll()
    {
        $aShopIds = DatabaseProvider::getDb()->getCol('SELECT oxid FROM oxshops');
        $aConfigs = array();

        foreach ($aShopIds as $mShopId) {
            // Note: not using static::get() for avoiding checking of is shop id valid
            $aConfigs[] = new SpecificShopConfig($mShopId);
        }

        return $aConfigs;
    }
    
    /**
     * Get config object of given shop id
     *
     * @param string|integer $mShopId
     *
     * @return SpecificShopConfig|null
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseNotConfiguredException
     */
    public static function get($mShopId)
    {
        $sSQL = 'SELECT 1 FROM oxshops WHERE oxid = %s';
        $oDb = DatabaseProvider::getDb();

        if (!$oDb->getOne(sprintf($sSQL, $oDb->quote($mShopId)))) { // invalid shop id
            // Not using oxConfig::_isValidShopId() because its not static, but YES it should be
            return null;
        }

        return new SpecificShopConfig($mShopId);
    }

    /**
     * {@inheritdoc}
     *
     * @return null|void
     */
    public function init()
    {
        // Duplicated init protection
        if ($this->_blInit) {
            return;
        }
        $this->_blInit = true;

        $this->_loadVarsFromFile();
        //include getShopBasePath() . 'core/oxconfk.php';
        // vendor/oxid-esales/oxideshop-ce/source/Core/Config.php?

        $this->_setDefaults();

        try {
            $sShopID = $this->getShopId();
            $blConfigLoaded = $this->_loadVarsFromDb($sShopID);

            // loading shop config
            if (empty($sShopID) || !$blConfigLoaded) {
                /** @var oxConnectionException $oEx */
                $oEx = oxNew(oxconnectionexception::class);
                $oEx->setMessage("Unable to load shop config values from database");
                throw $oEx;
            }

            // loading theme config options
            $this->_loadVarsFromDb(
                $sShopID,
                null,
                Config::OXMODULE_THEME_PREFIX . $this->getConfigParam('sTheme')
            );

            // checking if custom theme (which has defined parent theme) config options should be loaded over parent theme (#3362)
            if ($this->getConfigParam('sCustomTheme')) {
                $this->_loadVarsFromDb(
                    $sShopID,
                    null,
                    Config::OXMODULE_THEME_PREFIX . $this->getConfigParam('sCustomTheme')
                );
            }

            // loading modules config
            $this->_loadVarsFromDb($sShopID, null, Config::OXMODULE_MODULE_PREFIX);

            $aOnlyMainShopVars = array('blMallUsers', 'aSerials', 'IMD', 'IMA', 'IMS');
            $this->_loadVarsFromDb($this->getBaseShopId(), $aOnlyMainShopVars);
        } catch (oxConnectionException $oEx) {
            $oEx->debugOut();
            Registry::getUtils()->showMessageAndExit($oEx->getString());
        }
    }

    /**
     * Get shop id
     *
     * @return int
     */
    public function getShopId()
    {
        return $this->_iShopId;
    }
}
