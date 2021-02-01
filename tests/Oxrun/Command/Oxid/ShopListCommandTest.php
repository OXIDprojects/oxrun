<?php
/**
 * Created by PhpStorm.
 * User: tobi
 * Date: 2019-02-20
 * Time: 01:33
 */

namespace Oxrun\Oxid;

use Symfony\Component\Console\Application;
use Oxrun\Command\Oxid\ShopListCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ShopListCommandTest
 * @package Oxrun\Oxid
 */
class ShopListCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $shopListCommand = new ShopListCommand();
        $app->add($shopListCommand);

        $command = $app->find('oxid:shops');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('ShopId', $display);
        $this->assertStringContainsString('Shop name', $display);
        $this->assertStringContainsString('OXID eShop', $display);
    }

    public function testVerboseExecute()
    {
        $app = new Application();
        $shopListCommand = new ShopListCommand();
        $app->add($shopListCommand);

        $command = $app->find('oxid:shops');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' =>'oxid:shops'], ['verbosity' => Output::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('oxactive', $display);
        $this->assertStringContainsString('oxproductive', $display);
        $this->assertStringContainsString('oxedition', $display);
    }
}
