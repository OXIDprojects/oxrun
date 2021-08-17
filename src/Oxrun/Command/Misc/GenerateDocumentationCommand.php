<?php

namespace Oxrun\Command\Misc;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Console\ExecutorInterface;
use OxidEsales\Facts\Facts;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Webmozart\PathUtil\Path;

/**
 * Class GenerateDocumentationCommand
 * @package Oxrun\Command\Misc
 */
class GenerateDocumentationCommand extends Command
{
//    use NeedDatabase;

    protected $skipCommands = ['help', 'list'];

    protected $skipLines = array(
        '* Aliases: <none>',
        '* Is multiple: no',
        '* Shortcut: <none>',
        '* Default: `NULL`',
        '* Is required: yes',
        '* Is array: no',
        '* Is required: no',
        '* Accept value: yes',
    );

    protected $isOeConsoleExecuted = false;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('misc:generate:documentation')
            ->setDescription('Generate a raw command documentation of the available commands');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->skipLines = array_map(
            function ($item) {
                return $item . PHP_EOL;
            },
            $this->skipLines
        );

        $availableCommands = array_keys($this->getOeConsoleApp()->all());

        $availableCommands = array_filter($availableCommands, function ($commandName) use ($output) {
            if (in_array($commandName, $this->skipCommands) || preg_match('/^oe:/', $commandName) === 1) {
                return false;
            }
            return true;
        });

        sort($availableCommands);

        $this->writeToc($output, $availableCommands);

        $output->writeLn(PHP_EOL);

        $this->writeCommandUsage($output, $availableCommands);
        return 0;
    }

    /**
     * @return \Symfony\Component\Console\Application
     */
    public function getOeConsoleApp(): Application
    {
        $container = ContainerFactory::getInstance()->getContainer();
        /** @var Application $app */
        $app = $container->get('oxid_esales.console.symfony.component.console.application');

        if ($this->isOeConsoleExecuted === false) {
            include_once Path::join((new Facts())->getSourcePath(), 'bootstrap.php');
            $app->setAutoExit(false);
            $container->get(ExecutorInterface::class)->execute(new ArrayInput([]), new NullOutput());
            $this->isOeConsoleExecuted = true;
        }

        return $app;
    }

    /**
     * @param OutputInterface $output
     */
    protected function writeToc(OutputInterface $output, array $availableCommands)
    {
        $command = $this->getOeConsoleApp()->find('list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--format' => 'json'
            )
        );

        $commandOutput = $commandTester->getDisplay();
        $commandOutput = json_decode($commandOutput);
        $output->writeLn("Available commands");
        $output->writeLn("==================" . PHP_EOL);

        $description = [];
        array_walk(
            $commandOutput->commands,
            function ($item) use (&$description, $availableCommands) {
                if (in_array($item->name, $availableCommands)) {
                    $description[$item->name] = $item->description;
                }
            }
        );

        foreach ($commandOutput->namespaces as $namespace) {
            $title = "";
            if ($namespace->id != '_global') {
                $title = $namespace->id;
            };
            $links = [];
            foreach ($namespace->commands as $command) {
                if (in_array($command, $this->skipCommands) || !in_array($command, $availableCommands)) {
                    continue;
                }
                $links[] = sprintf(
                    '  - [%s](#%s)   %s',
                    $command,
                    str_replace(':', '', $command),
                    $description[$command]
                );
            }
            if (!empty($links)) {
                $output->writeLn("##### $title");
                array_map([$output, 'writeLn'], $links);
            }
        };
    }

    /**
     * @param OutputInterface $output
     * @param $availableCommands
     */
    protected function writeCommandUsage(OutputInterface $output, $availableCommands)
    {
        $command = $this->getOeConsoleApp()->find('help');
        $commandTester = new CommandTester($command);
        $applicationOptions = array_keys($this->getOeConsoleApp()->getDefinition()->getOptions());

        foreach ($availableCommands as $commandName) {
            $commandTester->execute(
                array(
                    'command' => $command->getName(),
                    'command_name' => $commandName,
                    '--format' => 'md'
                )
            );

            $commandOutput = $commandTester->getDisplay();

            //Remove unnecessary information
            $commandOutput = str_replace($this->skipLines, '', $commandOutput);

            //Remove Title Option if has no Options
            $currentCommand = $this->getOeConsoleApp()->find($commandName);
            if (count($currentCommand->getDefinition()->getOptions()) < 8) {
                $commandOutput = str_replace('### Options', '', $commandOutput);
            }

            //Remove standard application options
            $pattern = "/#### `--(?:" . implode('|', $applicationOptions) . ")[^#]+/";
            $commandOutput = preg_replace($pattern, '', $commandOutput);

            $commandOutput = trim($commandOutput);
            $output->writeLn($commandOutput . PHP_EOL);
        }
    }
}
