<?php

namespace Oxrun\Command\Module;

use Oxrun\Application;
use Oxrun\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ActivateCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new ActivateCommand());
        $app->add(new DeactivateCommand());

        $command = $app->find('module:deactivate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'module' => 'oepaypal',
                '--shopId' => 1
            )
        );

        $command = $app->find('module:activate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'module' => 'oepaypal',
                '--shopId' => 1
            )
        );

        $this->assertContains('Module oepaypal activated for shopId 1.', $commandTester->getDisplay());


        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'module' => 'oepaypal',
                '--shopId' => 1
            )
        );

        $this->assertContains('Module oepaypal already activated for shopId 1.', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'module' => 'not_and_existing_module',
                '--shopId' => 1
            )
        );

        $this->assertContains('Cannot load module not_and_existing_module.', $commandTester->getDisplay());
    }
}
