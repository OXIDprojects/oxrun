<?php

namespace Oxrun\Command\Views;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCommandTest extends TestCase
{

    public function testExecute()
    {
        $app = new Application();
        $app->add(new UpdateCommand());

        $command = $app->find('views:update');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );

        $this->assertStringContainsString('Views updated.', $commandTester->getDisplay());
    }
}
