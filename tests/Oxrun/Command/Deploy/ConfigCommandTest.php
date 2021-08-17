<?php

namespace Oxrun\Command\Deploy;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;
use Oxrun\Core\EnvironmentManager;
use Oxrun\Core\OxrunContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ConfigCommandTest
 * @package Oxrun\Command\Config
 */
class ConfigCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $context = new OxrunContext(new BasicContext());
        $app->add(new ConfigCommand($context, new EnvironmentManager($context)));

        $command = $app->find('config:multiset');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'configfile' => "config:\n  1:\n    foobar: barfoo\n"
            )
        );

        $this->assertEquals('(1) Config foobar: barfoo write into Database.'. PHP_EOL, $commandTester->getDisplay());
    }
}
