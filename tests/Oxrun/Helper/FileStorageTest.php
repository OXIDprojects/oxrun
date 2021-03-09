<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 09.03.21
 * Time: 15:09
 */

namespace Oxrun\Tests\Helper;

use org\bovigo\vfs\vfsStream;
use Oxrun\Helper\FileStorage;
use PHPUnit\Framework\TestCase;

/**
 * Class FileStorageTest
 * @package tests\Oxrun\Helper
 */
class FileStorageTest extends TestCase
{
    /**
     * @var string
     */
    private $vfs = '';

    private $structure = [
        "FileWithFirstline.yaml"  => "# Command: oe-console test:test --help test" . PHP_EOL . "config: { }",
        "FileNoFirstline.yaml"    => "config: { }",
        "FileOtherFirstline.yaml" => "  \t# Egal irgend einen Satz" . PHP_EOL . "config: { }" . PHP_EOL,
    ];


    protected function setUp(): void
    {
        $this->vfs = vfsStream::setup('root', null, $this->structure)->url();
    }

    /**
     * @dataProvider contentOfFiles
     */
    public function testGetContent($data)
    {
        //Arrange
        $filestorage = new FileStorage();

        //Act
        $actual = $filestorage->getContent($this->vfs . "/{$data->file}");

        //Access
        $this->assertEquals($data->expect, $actual);
    }

    public function contentOfFiles()
    {
        return [
            'file with command firstline' => [(object)['file' => 'FileWithFirstline.yaml', 'expect' => $this->structure['FileWithFirstline.yaml']]],
            'file no firstline' => [(object)['file' => 'FileNoFirstline.yaml', 'expect' => $this->structure['FileNoFirstline.yaml']]],
            'file with other firstline' => [(object)['file' => 'FileOtherFirstline.yaml', 'expect' => $this->structure['FileOtherFirstline.yaml']]],
        ];
    }

    public function testSave()
    {
        //Arrange
        $filestorage = new FileStorage();
        $GLOBALS['argv'] = ['oe-console', 'm:c:s:w', '--help', '-w'];

        //Act
        $filestorage->save($this->vfs . "/WriteFirstLine.yaml", 'config: {}');

        //Access
        $actual = file_get_contents($this->vfs . "/WriteFirstLine.yaml");
        $expect = "# Command: oe-console m:c:s:w --help -w" . PHP_EOL . "config: {}";

        $this->assertEquals($expect, $actual);
    }

    public function testNoUseGlobalArgv()
    {
        //Arrangeis
        $filestorage = new FileStorage();
        $GLOBALS['argv'] = ['oe-console', 'm:c:s:w', '--help', '-w'];

        //Act
        $filestorage->noUseGlobalArgv();
        $filestorage->save($this->vfs . "/WriteFirstLine.yaml", 'config: {}');

        //Access
        $actual = file_get_contents($this->vfs . "/WriteFirstLine.yaml");
        $expect = "config: {}";

        $this->assertEquals($expect, $actual);
    }

    /**
     * @dataProvider orgingFirstlineData
     */
    public function testTakeOriginFirstLine($data)
    {
        //Arrange
        $filestorage = new FileStorage();
        $GLOBALS['argv'] = ['oe-console', 'm:c:s:w', '--help', '-w'];

        //Act
        $filestorage->getContent($this->vfs . '/' . $data->file);
        $filestorage->noUseGlobalArgv();
        $filestorage->save($this->vfs . "/WriteFirstLine.yaml", 'config: {neu: true}');

        //Access
        $actual = file_get_contents($this->vfs . "/WriteFirstLine.yaml");
        $expect = $data->expect . "config: {neu: true}";

        $this->assertEquals($expect, $actual);
    }

    public function orgingFirstlineData()
    {
        return [
          'Origin Command Line' => [(object)['file' => 'FileWithFirstline.yaml', 'expect' => '# Command: oe-console test:test --help test' . PHP_EOL]],
          'Origin Nothing Line' => [(object)['file' => 'FileNoFirstline.yaml', 'expect' => '']],
          'Origin Other Line' => [(object)['file' => 'FileOtherFirstline.yaml', 'expect' => "  \t# Egal irgend einen Satz" . PHP_EOL ]],
        ];
    }
}
