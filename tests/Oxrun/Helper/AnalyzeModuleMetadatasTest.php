<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 22.02.21
 * Time: 14:04
 */

namespace Oxrun\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Class AnalyzeModuleMetadatasTest
 * @package Oxrun\Helper
 */
class AnalyzeModuleMetadatasTest extends TestCase
{

    private $analyzeModuleMetadata = null;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->analyzeModuleMetadata === null) {
            $this->analyzeModuleMetadata = new AnalyzeModuleMetadata(__DIR__ . '/testData/eShopVirtual/modules');
        }
    }

    /**
     * @dataProvider moduleNames
     */
    public function testExistsModule($data)
    {
        //Arrange
        $analyzeModuleMetadata = $this->analyzeModuleMetadata;

        //Act
        $actual = $analyzeModuleMetadata->existsModule($data->moduleId);

        //Assert
        $this->assertEquals($data->expect, $actual);
    }

    public function moduleNames()
    {
        return [
          'has module oegdproptin'  => [(object)['expect' => true, 'moduleId' => 'oegdproptin']],
          'has module not tmmodule'  => [(object)['expect' => false, 'moduleId' => 'tmmodule']],
          'has moduleId oepaypal in wrong folder'  => [(object)['expect' => true, 'moduleId' => 'oepaypal']],
        ];
    }

    /**
     * @dataProvider moduleParameter
     */
    public function testExistsModuleSetting($data)
    {
        //Arrange
        $analyzeModuleMetadata = $this->analyzeModuleMetadata;

        //Act
        $actual = $analyzeModuleMetadata->existsModuleSetting($data->moduleId, $data->param);

        //Assert
        $this->assertEquals($data->expect, $actual);
    }

    public function moduleParameter()
    {
        return [
            'has setting oegdproptin.OeGdprOptinContactFormMethod'  => [(object)['expect' => true, 'moduleId' => 'oegdproptin', 'param' => 'OeGdprOptinContactFormMethod']],
            'has not setting tmmodule.OeGdprOptinContactFormMethod'  => [(object)['expect' => false, 'moduleId' => 'tmmodule', 'param' => 'OeGdprOptinContactFormMethod']],
            'has setting oepaypal.sOEPayPalBrandName'  => [(object)['expect' => true, 'moduleId' => 'oepaypal', 'param' => 'sOEPayPalBrandName']],
        ];
    }
}
