<?php
/**
 * Created for oxrun
 * Author: Tobias Matthaiou <matthaiou@tobimat.eu>
 * Date: 07.06.17
 * Time: 07:56
 */

namespace Oxrun\Command\Module;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Oxrun\Command\Cache\ClearCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ReloadCommandTest
 * @package Oxrun\Command\Module
 */
class ReloadCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new ReloadCommand());
        $app->add(new DeactivateCommand());
        $app->add(new ClearCommand());
        $app->add(new ActivateCommand());

        $command = $app->find('module:reload');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'module' => 'oepaypal',
                '--shopId' => 1,
                '--force'  => true
            )
        );

        $this->assertStringContainsString('activated', $commandTester->getDisplay());
        $this->assertStringContainsString('Cache cleared', $commandTester->getDisplay());
        $this->assertStringContainsString('deactivated', $commandTester->getDisplay());
    }
}
