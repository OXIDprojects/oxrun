<?php

namespace Oxrun\Command\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

class GetSetCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    private $setCommand = null;

    /**
     * @var CommandTester
     */
    private $getCommand = null;

    /**
     * @var string|null
     */
    private $setCommandName = '';

    /**
     * @var string|null
     */
    private $getCommandName = '';


    protected function setUp(): void
    {
        parent::setUp();
        $app = new Application();
        $app->add(new SetCommand());
        $app->add(new GetCommand());

        $setCommand = $app->find('config:set');
        $getCommand = $app->find('config:get');

        $setCommand->addOption('shop-id', '', InputOption::VALUE_REQUIRED, 'Shop Id', 1);
        $getCommand->addOption('shop-id', '', InputOption::VALUE_REQUIRED, 'Shop Id', 1);

        $this->setCommand = new CommandTester($setCommand);
        $this->setCommandName = $setCommand->getName();

        $this->getCommand = new CommandTester($getCommand);
        $this->getCommandName = $getCommand->getName();

    }


    public function testJsonInput()
    {
        $randomColumns = array(
            md5(microtime(true) . rand(1024, 2048)),
            md5(microtime(true) . rand(1024, 2048)),
            md5(microtime(true) . rand(1024, 2048))
        );
        $randomColumnsJson = json_encode($randomColumns, true);

        $this->setCommand->execute(
            [
                'command' => $this->setCommandName,
                'variableName' => 'aSortCols',
                'variableValue' => $randomColumnsJson
            ]
        );

        $this->getCommand->execute(
            [
                'command' => $this->getCommandName,
                'variableName' => 'aSortCols',
            ]
        );

        $expect = Yaml::dump(['aSortCols' => ['shop-id' => 1, 'type' => "arr", 'value' => $randomColumns]], 3, 2) . PHP_EOL;

        $this->assertEquals($expect, $this->getCommand->getDisplay());
    }

    public function testBooleanInput()
    {
        $this->setCommand->execute(
            [
                'command' => $this->setCommandName,
                'variableName' => 'bl_perfLoadAktion',
                'variableValue' => false
            ]
        );

        $this->getCommand->execute(
            [
                'command' => $this->getCommandName,
                'variableName' => 'bl_perfLoadAktion',
            ]
        );

        $expect = Yaml::dump(['bl_perfLoadAktion' => ['shop-id' => 1, 'type' => "bool", 'value' => false]], 3, 2) . PHP_EOL;

        $this->assertEquals($expect, $this->getCommand->getDisplay());
    }

    public function testBooleanTrueInput()
    {
        $this->setCommand->execute(
            [
                'command' => $this->setCommandName,
                'variableName' => 'bl_perfLoadAktion',
                'variableValue' => true
            ]
        );

        $this->getCommand->execute(
            [
                'command' => $this->getCommandName,
                'variableName' => 'bl_perfLoadAktion',
            ]
        );

        $expect = Yaml::dump(['bl_perfLoadAktion' => ['shop-id' => 1, 'type' => "bool", 'value' => true]], 3, 2) . PHP_EOL;

        $this->assertEquals($expect, $this->getCommand->getDisplay());

    }

    public function testFromModuleInput()
    {
        $this->setCommand->execute(
            [
                'command' => $this->setCommandName,
                'variableName' => 'iTopNaviCatCount',
                'variableValue' => 99,
                '--variableType' => 'int',
                '--moduleId' => 'theme:azure'
            ]
        );

        $this->getCommand->execute(
            [
                'command' => $this->getCommandName,
                'variableName' => 'iTopNaviCatCount',
                '--json' => true,
                '--moduleId' => 'theme:azure',
            ]
        );

        $expect = \json_encode(['iTopNaviCatCount' => ['shop-id' => 1, 'moduleId' => 'theme:azure', 'type' => 'int', 'value' => '99']]) . PHP_EOL;

        $this->assertEquals($expect, $this->getCommand->getDisplay());
    }

}
