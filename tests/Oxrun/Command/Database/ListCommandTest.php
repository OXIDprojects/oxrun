<?php

namespace Oxrun\Command\Database;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Oxrun\Command\Database\ListCommand as TestListCommand;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new TestListCommand());

        $command = $app->find('db:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );

        $this->assertStringContainsString('Table', $commandTester->getDisplay());
        $this->assertStringContainsString('Type', $commandTester->getDisplay());
    }

}
