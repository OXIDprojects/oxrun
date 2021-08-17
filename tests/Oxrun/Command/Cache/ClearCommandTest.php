<?php

namespace Oxrun\Command\Cache;

use Composer\Console\Application;
use OxidEsales\Eshop\Core\Registry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;

class ClearCommandTest extends TestCase
{
    use ProphecyTrait;

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
        $app = new Application();
        $app->add(new ClearCommand());
        $command = $app->find('cache:clear');

        if ((new \OxidEsales\Facts\Facts)->isEnterprise() == false) {
            $this->mockEEGenericCacheAndDynamicContentClass();
        }


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
        $app = new Application();
        $app->add(new ClearCommand());
        $command = $app->find('cache:clear');

        list($facts, $genericCache) = $this->mockEEGenericCacheClass();
        $genericCache->flush()->willThrow(new \Exception('PHPUnit Tests'));


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

    protected function tearDown(): void
    {
        Registry::set(\OxidEsales\Facts\Facts::class, null);
        Registry::set('\OxidEsales\Eshop\Core\Cache\Generic\Cache', null);
        Registry::set('\OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache', null);
    }


    /**
     * @return array
     */
    protected function mockEEGenericCacheClass()
    {
        $facts = $this->prophesize(\OxidEsales\Facts\Facts::class);
        $facts->isEnterprise()->willReturn(true);
        $genericCache = $this->prophesize(GenericCache::class);

        Registry::set(\OxidEsales\Facts\Facts::class, $facts->reveal());
        Registry::set('OxidEsales\Eshop\Core\Cache\Generic\Cache', $genericCache->reveal());

        return [$facts, $genericCache];
    }

    /**
     * @return array
     */
    protected function mockEEGenericCacheAndDynamicContentClass()
    {
        list($facts, $genericCache) = $this->mockEEGenericCacheClass();

        $dynamicContentCache = $this->prophesize(DynamicContentCache::class);
        Registry::set('OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache', $dynamicContentCache->reveal());

        return [$facts, $genericCache, $dynamicContentCache];
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
