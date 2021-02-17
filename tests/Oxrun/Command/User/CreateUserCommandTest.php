<?php

namespace Oxrun\Command\User;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test expecting user input
 * @see http://symfony.com/doc/2.8/components/console/helpers/questionhelper.html
 */
class CreateUserCommandTest extends TestCase
{


    /**
     * Cleanup
     */
    public static function tearDownAfterClass(): void
    {
        /** @var QueryBuilder $qb */
        $qb = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $qb->delete('oxuser')
            ->where('OXUSERNAME = :oxusername')
            ->setParameter('oxusername', 'dummyuser@oxrun.com')
            ->execute();
    }

    public function testExecute()
    {
        $app = new Application();
        $app->add(new CreateUserCommand());

        $command = $app->find('user:create');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(["dummyuser@oxrun.com", "secretpass", "Dummy", "User", "yes"]);
        $commandTester->execute(
            array('command' => $command->getName())
        );

        $this->assertStringContainsString('User created', $commandTester->getDisplay());
    }
}
