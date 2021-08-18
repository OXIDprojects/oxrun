<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 2019-03-29
 * Time: 07:12
 */

namespace Oxrun\Tests\Helper;

use Oxrun\Helper\MultiSetTranslator;
use PHPUnit\Framework\TestCase;

/**
 * Class MultiSetTranslatorTest
 * @package Oxrun\Helper
 */
class MultiSetTranslatorTest extends TestCase
{


    public function testYamlHasNotAConfigSection()
    {
        //Arrange
        $multiSetTranslator = new MultiSetTranslator();

        $ymltxt = 'andere: "config"';

        //Assert
        $this->expectException(\Exception::class);

        //Act
        $multiSetTranslator->configFile($ymltxt, 0);
    }

    public function testTranslateConfig()
    {
        //Arrange
        $multiSetTranslator = new MultiSetTranslator(2);

        $ymltxt = file_get_contents(__DIR__.'/testData/plan_config.yml');
        $expect = file_get_contents(__DIR__.'/testData/translated_config.yml');

        //Act
        $actual = $multiSetTranslator->configFile($ymltxt, 0);

        //Assert
        $this->assertEquals($expect, $actual);
    }

}
