<?php
/**
 * Created by oxrun.
 * Autor: Tobias Matthaiou <225997+TumTum@users.noreply.github.com>
 * Date: 23.09.17
 * Time: 22:52
 */

namespace Oxrun\Command\Route;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Oxrun\Command\Route;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class Debug
 */
class DebugTest extends TestCase
{
    const COMMANDNAME = 'route:debug';

    /**
     * Shop URL, will be read from config
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Seo path to search for
     *
     * @var string
     */
    protected $seoKey = 'Nach-Hersteller';

    /**
     * @var Route\DebugCommand|CommandTester
     */
    protected $commandTester;

    /**
     * Prepare
     *
     * @return void
     */
    protected function setUp(): void
    {
        $app = new Application();
        $app->add(new Route\DebugCommand());

        $command = $app->find(self::COMMANDNAME);

        $this->baseUrl = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sShopURL');

        // TODO - insert static seo url for seoKey!?
        $this->commandTester = new CommandTester($command);
    }

    public function testCompleteUrl()
    {
        $this->commandTester->execute(
            array(
                'url' => $this->baseUrl . "/{$this->seoKey}/",
                'command' => self::COMMANDNAME,
            )
        );

        //echo "\nRESULT: " . $this->commandTester->getDisplay();
        $this->assertMatchesRegularExpression('~\|\s+Controller\s+\|\s+manufacturerlist\s+\|~', $this->commandTester->getDisplay());
    }

    public function testHalfBrokenUrl()
    {
        $this->commandTester->execute(
            array(
                'url' => $this->baseUrl . '/' . $this->seoKey,
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertMatchesRegularExpression('~\|\s+Controller\s+\|\s+manufacturerlist\s+\|~', $this->commandTester->getDisplay());
    }

    public function testOnlyPath()
    {
        $this->commandTester->execute(
            array(
                'url' => $this->seoKey. '/',
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertMatchesRegularExpression('~\|\s+Controller\s+\|\s+manufacturerlist\s+\|~', $this->commandTester->getDisplay());
    }

    public function testHalfOnlyPath()
    {
        $this->commandTester->execute(
            array(
                'url' => $this->seoKey,
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertMatchesRegularExpression('~\|\s+Controller\s+\|\s+manufacturerlist\s+\|~', $this->commandTester->getDisplay());
    }

    public function testGiveMeClassPath()
    {
        $this->commandTester->execute(
            array(
                'url' => $this->seoKey,
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertStringContainsString('manufacturerlist', $this->commandTester->getDisplay());
    }

    public function testGiveFunctionLineNumber()
    {
        /** @var \OxidEsales\Eshop\Core\SeoDecoder $oxSeoEncoder */
        $oxSeoEncoder = oxNew(\OxidEsales\Eshop\Core\SeoEncoder::class);
        $oxSeoEncoder->getDynamicUrl('index.php?cl=news&amp;fnc=render', 'newspage/', 0);

        $this->commandTester->execute(
            array(
                'url' => 'NewsPage/',
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertMatchesRegularExpression('~NewsController.php:\d+~', $this->commandTester->getDisplay());
    }

    public function testClassDontExists()
    {
        /** @var \OxidEsales\Eshop\Core\SeoDecoder $oxSeoEncoder */
        $oxSeoEncoder = oxNew(\OxidEsales\Eshop\Core\SeoEncoder::class);
        $oxSeoEncoder->getDynamicUrl('index.php?cl=classdontexists', 'class/dont/exists/', 0);

        $this->commandTester->execute(
            array(
                'url' => 'Class/Dont/Exists/',
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertStringContainsString('EXCEPTION_SYSTEMCOMPONENT_CLASSNOTFOUND', $this->commandTester->getDisplay());
    }

    public function testMethodInClassDontExists()
    {
        /** @var \OxidEsales\Eshop\Core\SeoEncoder $oxSeoEncoder */
        $oxSeoEncoder = oxNew(\OxidEsales\Eshop\Core\SeoEncoder::class);
        $oxSeoEncoder->getDynamicUrl('index.php?cl=news&amp;fnc=nameXYX', 'method/in/class/dont/exists/', 0);

        $this->commandTester->execute(
            array(
                'url' => 'Method/In/Class/Dont/Exists/',
                'command' => self::COMMANDNAME,
            )
        );

        $this->assertMatchesRegularExpression(
            '/Method (?:[^:]+::nameXYX\(\)|nameXYX) does not exist/',
            $this->commandTester->getDisplay()
        );
    }

    /**
     * Reset seo db
     */
    public static function tearDownAfterClass(): void
    {
        $db = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $seoURls[] = $db->quote('newspage/');
        $seoURls[] = $db->quote('class/dont/exists/');
        $seoURls[] = $db->quote('method/in/class/dont/exists/');
        $seoURls = implode(", ", $seoURls);

        $db->execute("DELETE FROM oxseo WHERE OXSEOURL IN ($seoURls)");
    }
}
