<?php

namespace Oxrun\Command\Cache;

use Composer\Console\Application;
use OxidEsales\Eshop\Core\Registry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ClearCommandTest
 * @package Oxrun\Command\Cache
 */
class ClearCommandTest extends TestCase
{
    public function testExecute()
    {
        $app = new Application();
        $app->add(new ClearCommand());

        $command = $app->find('cache:clear');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--force' => 1
            ]
        );

        $actual = $commandTester->getDisplay();

        $this->assertStringContainsString('Cache cleared.', (string)$actual);
    }

    public function testDontClearCompileFolderIfIsNotSameOwner()
    {
        $app = new Application();
        $clearCommand = new ClearCommand();
        $app->add($clearCommand);

        $oxconfigfile = new \OxidEsales\Eshop\Core\ConfigFile(OX_BASE_PATH . DIRECTORY_SEPARATOR . 'config.inc.php');
        $compileDir   = $oxconfigfile->getVar('sCompileDir');

        $owner = fileowner($compileDir);
        $current_owner = posix_getuid();

        if ($current_owner == $owner) {
            $this->markTestSkipped('Test can\'t be testet, becouse the compileDir has the same owner ');
        }

        $this->expectErrorMessage('Please run command as `www-data` user');

        $command = $app->find('cache:clear');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );
    }

    public function testItClearCacheOnEnterpriseEdtion()
    {
        [$facts, $genericCache, $dynamicContentCache] = $this->mockEEClasses();

        $app = new Application();
        $app->add(new ClearCommand($facts, $genericCache, $dynamicContentCache));
        $command = $app->find('cache:clear');


        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--force' => 1
            )
        );

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Generic\\Cache is cleared', $display);
        $this->assertStringContainsString('DynamicContent\\Cache is cleared', $display);
    }

    public function testCatchExcetionByEE()
    {
        [$facts, $genericCache, $dynamicContentCache] = $this->mockEEClasses();

        $app = new Application();
        $app->add(new ClearCommand($facts, $genericCache, $dynamicContentCache));
        $command = $app->find('cache:clear');

        $genericCache
            ->method('flush')
            ->will($this->returnCallback(function () { throw new \Exception('PHPUnit Tests'); }));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--force' => 1
            )
        );

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Only enterprise cache could', $display);
    }


    protected function mockEEClasses(): array
    {
        $facts = $this->createMock(\OxidEsales\Facts\Facts::class);
        $facts
            ->method('isEnterprise')
            ->willReturn($this->returnValue(true));

        return [
            $facts,
            $this->createMock(GenericCache::class),
            $this->createMock(DynamicContentCache::class),
        ];
    }
}

/**
 * Mock GenericCache
 * That is a class only in EE
 * @package Oxrun\Command\Cache
 * @mixin \OxidEsales\Eshop\Core\Cache\Generic\Cache
 */
interface GenericCache {
    public function flush();
}

/**
 * Mock DynamicContentCache
 * That is a class only in EE
 * @package Oxrun\Command\Cache
 * @mixin \OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache
 */
interface DynamicContentCache {
    public function reset($boolean);
}
