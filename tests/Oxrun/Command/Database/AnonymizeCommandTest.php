<?php

namespace Oxrun\Command\Database;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class AnonymizeCommandTest
 * @package Oxrun\Command\Database
 */
class AnonymizeCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new AnonymizeCommand());

        $command = $app->find('db:anonymize');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--debug' => true,
                '--keepdomain' => '@shoptimax.de',
            ),
            ['interactive' => false]
        );

        $this->assertStringContainsString('oxaddress', $commandTester->getDisplay());
        $this->assertStringContainsString('Anonymizing done.', $commandTester->getDisplay());
    }
}
