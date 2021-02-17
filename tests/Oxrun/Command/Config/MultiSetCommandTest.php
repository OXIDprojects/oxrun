<?php

namespace Oxrun\Command\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class MultiSetCommandTest
 * @package Oxrun\Command\Config
 */
class MultiSetCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new MultiSetCommand());

        $command = $app->find('config:multiset');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'configfile' => "config:\n  1:\n    foobar: barfoo\n"
            )
        );

        $this->assertEquals('Config foobar for shop 1 set to barfoo'. PHP_EOL, $commandTester->getDisplay());
    }
}
