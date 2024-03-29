<?php

namespace Oxrun\Command\Module;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new ListCommand());

        $command = $app->find('module:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
            )
        );

        $this->assertStringContainsString('paypal', $commandTester->getDisplay());
    }
}
