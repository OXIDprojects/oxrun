<?php

namespace Oxrun\Command\Database\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Oxrun\Command\Database\DumpCommand;
use Symfony\Component\Console\Tester\CommandTester;

class DumpCommandTest extends TestCase
{
    public function testExecute()
    {
        $dumpCommand = new DumpCommand();

        $app = new Application();
        $app->add($dumpCommand);

        $command = $app->find('db:dump');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );

        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxacceptedterms`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxaccessoire2article`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxactions`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxaddress`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxarticles`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxattribute`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxcategories`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxconfig`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxorder`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxorderarticles`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxuser`;', $commandTester->getDisplay());
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxvendor`;', $commandTester->getDisplay());

        $path = tempnam(sys_get_temp_dir(), 'oxrun_db_import_test');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--file' => $path
            )
        );

        $dump = file_get_contents( $path );
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxacceptedterms`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxaccessoire2article`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxactions`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxaddress`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxarticles`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxattribute`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxcategories`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxconfig`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxorder`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxorderarticles`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxuser`;', $dump);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `oxvendor`;', $dump);
    }

}
