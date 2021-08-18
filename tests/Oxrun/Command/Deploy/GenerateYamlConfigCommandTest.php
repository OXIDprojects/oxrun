<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 2019-03-02
 * Time: 17:50
 */

namespace Oxrun\Command\Deploy;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use Oxrun\Core\OxrunContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

/**
 * Class GenerateYamlMultiSetCommandTest
 * @package Oxrun\Command
 */
class GenerateConfigurationCommandTest extends TestCase
{
    protected static $unlinkFile = null;

    public function testExecute()
    {
        $app = new Application();
        $app->add(
            ContainerFactory::getInstance()->getContainer()->get(GenerateConfigurationCommand::class)
        );

        $command = $app->find('misc:generate:yaml:config');
        $command->addOption('shop-id', '', InputOption::VALUE_REQUIRED, "Shop Id", 1);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--configfile' => 'shopConfigs',
            )
        );
        $expectPath = self::$unlinkFile = \oxrun\test\OxrunContext()->getOxrunConfigPath() . 'shopConfigs.yml';

        $this->assertStringContainsString(
            'Config saved. use `oe-console deploy:config shopConfigs.yml`',
            $commandTester->getDisplay()
        );
        $this->assertFileExists($expectPath);
    }

    public function testExportListOfVariabels()
    {
        $app = new Application();
        $app->add(
            ContainerFactory::getInstance()->getContainer()->get(GenerateConfigurationCommand::class)
        );

        $path = self::$unlinkFile = \oxrun\test\OxrunContext()->getOxrunConfigPath() . 'dev.yaml';
        Registry::getConfig()->saveShopConfVar('str', 'unitVarB', 'abcd1');
        Registry::getConfig()->saveShopConfVar('str', 'unitVarC', 'cdef1');

        $dev_yml = ['config' => ['1' => ['varA' => 'besteht']]];
        file_put_contents($path, Yaml::dump($dev_yml));

        $command = $app->find('misc:generate:yaml:config');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--configfile' => 'dev.yaml',
                '--oxvarname' => 'unitVarB,unitVarC',
            )
        );

        $actual = Yaml::parse(file_get_contents($path));
        $expect = [
            "environment" => [],
            'config' => [
                '1' => [
                    'varA' => 'besteht',
                    'unitVarB' => 'abcd1',
                    'unitVarC' => 'cdef1',
                ]
            ]
        ];
        $this->assertEquals($expect, $actual);
    }

    public function testExportModullVariable()
    {
        $app = new Application();
        $app->add(
            ContainerFactory::getInstance()->getContainer()->get(GenerateConfigurationCommand::class)
        );
        $path = self::$unlinkFile = \oxrun\test\OxrunContext()->getOxrunConfigPath() . 'dev_config.yml';


        Registry::getConfig()->saveShopConfVar('str', 'unitModuleB', 'abcd1', 1, 'module:unitTest');
        Registry::getConfig()->saveShopConfVar('str', 'unitModuleW', 'cdef1', 1, 'module:unitNext');

        $command = $app->find('misc:generate:yaml:config');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--oxmodule' => 'module:unitTest, module:unitNext',
            )
        );

        $actual = Yaml::parse(file_get_contents($path));
        $expect = [
            "environment" => [],
            'config' => [
                '1' => [
                    'unitModuleB' => [
                        'variableType' => 'str',
                        'variableValue' => 'abcd1',
                        'moduleId' => 'module:unitTest'
                    ],
                    'unitModuleW' => [
                        'variableType' => 'str',
                        'variableValue' => 'cdef1',
                        'moduleId' => 'module:unitNext'
                    ],
                ]
            ]
        ];

        $this->assertEquals($expect, $actual);
    }

    public function testExportModulVariableNameAndShop2()
    {
        $app = new Application();
        $app->add(
            ContainerFactory::getInstance()->getContainer()->get(GenerateConfigurationCommand::class)
        );
        $path = self::$unlinkFile = \oxrun\test\OxrunContext()->getOxrunConfigPath() . 'dev_config.yml';

        Registry::getConfig()->saveShopConfVar('str', 'unitSecondShopName', 'Mars', 2, 'module:unitMars');
        Registry::getConfig()->saveShopConfVar('str', 'unitEgal',           'none', 3, 'module:unitMars');


        $command = $app->find('misc:generate:yaml:config');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--oxvarname' => 'unitSecondShopName',
                '--oxmodule' => 'module:unitMars',
                '--shop-id' => '2',
            )
        );

        $actual = Yaml::parse(file_get_contents($path));
        $expect = [
            "environment" => [],
            'config' => [
                '2' => [
                    'unitSecondShopName' => [
                        'variableType' => 'str',
                        'variableValue' => 'Mars',
                        'moduleId' => 'module:unitMars'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expect, $actual);
    }

    public function testExportModullVariableOnlyModulname()
    {
        $app = new Application();
        $app->add(
            ContainerFactory::getInstance()->getContainer()->get(GenerateConfigurationCommand::class)
        );
        $path = self::$unlinkFile = \oxrun\test\OxrunContext()->getOxrunConfigPath() . 'dev_config.yml';


        Registry::getConfig()->saveShopConfVar('str', 'unitModuleB', 'abcd1', 1, 'module:myModuleName');
        Registry::getConfig()->saveShopConfVar('str', 'unitModuleZ', 'abcd2', 1, 'module:myModuleOption');

        $command = $app->find('misc:generate:yaml:config');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--oxmodule' => 'myModuleName,module:myModuleOption',
            )
        );

        $actual = Yaml::parse(file_get_contents($path));
        $expect = [
            "environment" => [],
            'config' => [
                '1' => [
                    'unitModuleB' => [
                        'variableType' => 'str',
                        'variableValue' => 'abcd1',
                        'moduleId' => 'module:myModuleName'
                    ],
                    'unitModuleZ' => [
                        'variableType' => 'str',
                        'variableValue' => 'abcd2',
                        'moduleId' => 'module:myModuleOption'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expect, $actual);
    }

    protected function tearDown(): void
    {
        if (self::$unlinkFile) {
            @unlink(self::$unlinkFile);
        }

        DatabaseProvider::getDb()->execute('DELETE FROM `oxconfig` WHERE `OXVARNAME` LIKE "unit%"');

        parent::tearDown();
    }
}
