<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 03.09.21
 * Time: 11:20
 */

namespace Oxrun\Command\Misc;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

/**
 * Class RegisterCommandTest
 * @package Oxrun\Command\Misc
 * @group active
 */
class RegisterCommandTest extends TestCase
{

    /**
     * @var BasicContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $basicContext;

    /**
     * @var \Symfony\Component\Console\Command\Command
     */
    private $misc_register_command;

    /**
     * @var string
     */
    private $fs = "/tmp/oxrun_tests";

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = "/tmp/oxrun_tests";
        $this->basicContext = $this->createMock(BasicContextInterface::class);

        $app = new Application();
        $app->add(new RegisterCommand($this->basicContext));
        $this->misc_register_command = $app->find('misc:register:command');
    }

    public function testComandDirIsWrong()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => '/NOT_EXITS_DICT'
            ]
        );

        //Assert
        $this->assertStringContainsString('/NOT_EXITS_DICT is not a Folder', $commandTester->getDisplay());
    }

    public function testRegisterHelloWorldCommand()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);
        $root = vfsStream::setup('root', null, ['default_services.yaml' => 'services:']);
        $services_yaml = $root->url() . '/configurable_services.yaml';

        $this->basicContext
            ->method('getConfigurableServicesFilePath')
            ->willReturn($services_yaml);

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => __DIR__ . '/../../../../example/',
            ]
        );

        //Assert
        $this->assertEquals(
            "services:\n" .
            "  OxidEsales\DemoComponent\Command\HelloWorldCommand:\n" .
            "    tags:\n" .
            "      - { name: console.command, command: 'demo-component:say-hello' }\n",
            file_get_contents($services_yaml)
        );
    }

    public function testFreshNewServiceYaml()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);
        $this->fs = "/tmp/oxrun_tests";
        $this->createMockCommand('FreshNewServiceCommand');
        $services_yaml = $this->fs . '/projekt_services.yaml';

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => $this->fs . '/Commands/',
                '--service-yaml' => $services_yaml
            ]
        );

        //Assert
        $this->assertEquals(
            "services:\n" .
            "  OxidEsales\DemoComponent\Command\FreshNewServiceCommand:\n" .
            "    tags:\n" .
            "      - { name: console.command, command: 'demo-component:say-hello' }\n",
            file_get_contents($services_yaml)
        );
    }

    public function testModuleCommands()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);
        $this->basicContext
            ->method('getModulesPath')
            ->willReturn('/tmp/oxrun_tests/source/modules');

        $this->fs = "/tmp/oxrun_tests/source/modules/oxidprojects/oxrun";
        $this->createMockCommand('ModuleCronCommand', true);
        $services_yaml = $this->fs . '/services.yaml';

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => 'oxidprojects/oxrun',
                '--isModule' => true,
            ]
        );

        //Assert
        $this->assertEquals(
            "services:\n" .
            "  OxidEsales\DemoComponent\Command\ModuleCronCommand:\n" .
            "    tags:\n" .
            "      - { name: console.command, command: 'demo-component:say-hello' }\n",
            file_get_contents($services_yaml)
        );
    }

    public function testModuleCommandWithOtherServicesYaml()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);
        $this->basicContext
            ->method('getModulesPath')
            ->willReturn('/tmp/oxrun_tests/source/modules');

        $services_yaml = $this->fs . '/configurable_services.yaml';

        $this->fs = "/tmp/oxrun_tests/source/modules/oxidprojects/oxrun";
        $this->createMockCommand('ModuleOtherServicesYamCommand', true);

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => 'oxidprojects/oxrun',
                '--isModule' => true,
                '--service-yaml' => $services_yaml
            ]
        );

        //Assert
        $this->assertFileDoesNotExist($services_yaml);
    }

    public function testModuleNotexits()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);
        $this->basicContext
            ->method('getModulesPath')
            ->willReturn($this->fs . '/source/modules');

        mkdir($this->fs . '/source/modules/oxidprojects/oxrun', 755, true);

        //Assert
        $this->expectErrorMessage('oxidprojects/oxrun is not a module');

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => 'oxidprojects/oxrun',
                '--isModule' => true,
            ]
        );
    }

    public function testModuleHasNotACommandsFolder()
    {
        //Arrange
        $commandTester = new CommandTester($this->misc_register_command);
        $this->basicContext
            ->method('getModulesPath')
            ->willReturn($this->fs . '/source/modules');

        mkdir($this->fs . '/source/modules/oxidprojects/oxrun', 755, true);
        touch($this->fs . '/source/modules/oxidprojects/oxrun/metadata.php');

        //Assert
        $this->expectErrorMessage('No Commands found in Module. Put yout commands in Folder: `Commands/*Command.php`');

        //Act
        $commandTester->execute(
            [
                'command' => $this->misc_register_command->getName(),
                'command-dir' => 'oxidprojects/oxrun',
                '--isModule' => true,
            ]
        );
    }

    private function createMockCommand($commandName, $isModule = false): void
    {
        $content = file_get_contents(__DIR__ . '/../../../../example/HelloWorldCommand.php');
        $content = str_replace('HelloWorldCommand', $commandName, $content);

        if (!file_exists($this->fs . '/Commands')) {
            mkdir($this->fs . '/Commands', 755, true);
        }

        if ($isModule) {
            touch($this->fs . '/metadata.php');
        }

        file_put_contents($this->fs . '/Commands/'. "{$commandName}.php", $content);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        exec('rm -rf ' . $this->fs);
    }

}
