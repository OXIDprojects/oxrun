<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 2019-03-02
 * Time: 17:00
 */

namespace Oxrun\Helper;

use OxidEsales\EshopCommunity\Internal\Framework\Config\Utility\ShopSettingEncoderInterface;

/**
 * Class MulitSetConfigConverter
 * @package Oxrun\Helper
 */
class MulitSetConfigConverter
{
    /**
     * @var ShopSettingEncoderInterface
     */
    private $shopSettingEncoder;

    /**
     * MulitSetConfigConverter constructor.
     */
    public function __construct(
        ShopSettingEncoderInterface $shopSettingEncoder
    ) {
        $this->shopSettingEncoder = $shopSettingEncoder;
    }

    /**
     * @param array $config
     * @return array
     */
    public function convert($config)
    {
        $newconfig['variableType'] = $config['oxvartype'];
        $newconfig['variableValue'] = $this->shopSettingEncoder->decode($config['oxvartype'], $config['oxvarvalue']);

        if (isset($config['oxmodule']) && $config['oxmodule']) {
            $newconfig['moduleId'] = $config['oxmodule'];
        }

        //Simple string
        if ($config['oxvartype'] == 'str' && $config['oxmodule'] == '') {
            $newconfig = $config['oxvarvalue'];
        }

        return [
            'key' => $config['oxvarname'],
            'value' => $newconfig
        ];
    }
}
