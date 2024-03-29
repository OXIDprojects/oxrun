<?php

namespace Oxrun\Command\Database;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class QueryCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new QueryCommand());

        $command = $app->find('db:query');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'query' => 'SELECT * FROM oxuser',
            )
        );

        $this->assertStringContainsString('oxdefaultadmin', $commandTester->getDisplay());
    }

}
