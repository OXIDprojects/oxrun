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

        $display = $commandTester->getDisplay();

        $expectedDeactivate = array_map('preg_quote', [
            'Module - "oepaypal" has been deactivated.',
            'It was not possible to deactivate module - "oepaypal", maybe it was not active?'
        ]);

        $this->assertMatchesRegularExpression('/' . implode('|', $expectedDeactivate) . '/', $display);
        $this->assertStringContainsString('Cache cleared', $display);
        $this->assertStringContainsString('Module - "oepaypal" was activated.', $display);
    }
}
