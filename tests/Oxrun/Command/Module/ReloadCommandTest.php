<?php
/**
 * Created for oxrun
 * Author: Tobias Matthaiou <matthaiou@tobimat.eu>
 * Date: 07.06.17
 * Time: 07:56
 */

namespace Oxrun\Command\Module;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
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
        $container = ContainerFactory::getInstance()->getContainer();
        $app = new Application();
        $app->add($container->get(ReloadCommand::class));
        $app->add($container->get('oxid_esales.command.module_deactivate_command'));
        $app->add(new ClearCommand());
        $app->add($container->get('oxid_esales.command.module_activate_command'));

        $command = $app->find('module:reload');


        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'module' => 'oepaypal',
                '--force-cache'  => true
            )
        );

        $this->assertStringContainsString('Module - "oepaypal" has been deactivated.', $commandTester->getDisplay());
        $this->assertStringContainsString('Cache cleared', $commandTester->getDisplay());
        $this->assertStringContainsString('Module - "oepaypal" was activated.', $commandTester->getDisplay());
    }
}
