<?php

namespace Oxrun\Command\Misc;

use OxidEsales\Facts\Facts;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Class PhpstormMetadataCommand
 *
 * @package Oxrun\Command\Misc
 */
class PhpstormMetadataCommand extends Command
{

    protected $path = [];

    protected $namespace = [];

    /**
     * @var Facts
     */
    protected $fact = null;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('misc:phpstorm:metadata')->setDescription(
                'Generate a PhpStorm metadata file for auto-completion and a oxid module chain.' .
                'Ideal for psalm or phpstan'
            )->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_REQUIRED,
                'Writes the metadata for PhpStorm to the specified directory.'
            );
    }

    /**
     * @return Facts
     */
    public function getFact(): Facts
    {
        if ($this->fact === null) {
            $this->fact = new Facts();
        }

        return $this->fact;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param ConsoleOutput $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modulePath = Path::join($this->getFact()->getSourcePath(), 'modules');
        $metadates = (new \Symfony\Component\Finder\Finder())
            ->in($modulePath)
            ->name('/metadata.php/')
            ->depth(2)
            ->files();


        foreach ($metadates as $file) {
            $realPath = $file->getRealPath();

            try {
                @include $realPath;
                $output->writeln("<info>" . str_replace($modulePath, '', $realPath) . '</info>');
            } catch (\Throwable $throwable) {
                $output->getErrorOutput()->writeln(sprintf(
                    '<comment>[WARN]</comment> <info>%s</info> could not be read',
                    str_replace($modulePath, '', $realPath)
                ));
                $output->getErrorOutput()->writeln(sprintf(
                    "<comment>[WARN]</comment> <error>%s</error> %s:%s",
                    $throwable->getMessage(),
                    str_replace($modulePath, '', $throwable->getFile()),
                    $throwable->getLine()
                ));
            }

            if (!isset($aModule['extend'])) {
                continue;
            }

            foreach ($aModule['extend'] as $originClass => $moduleClass) {
                if (strpos($moduleClass, '/') !== false) {
                    $this->classic($originClass, $moduleClass);
                } else {
                    $this->namespace($originClass, $moduleClass);
                }
            }
        }

        $metaFiles = $this->createSavePlace($input);

        $this->saveDataInto($metaFiles->oxid_module_chain);
        $this->saveOxideShop($metaFiles->oxid_esale);

        $output->writeln("OXID eShop Cheats is saved: $metaFiles->oxid_esale");
        $output->writeln("OXID Module Chain is saved: $metaFiles->oxid_module_chain");

        return 0;
    }

    /**
     * @param string $originClass
     * @param string $moduleClass
     */
    private function classic($originClass, $moduleClass)
    {
        $explode = explode('/', $moduleClass);
        $moduleClass = array_pop($explode);

        $this->addChain('', $moduleClass, $originClass);
    }

    /**
     * @param string $namespace
     * @param string $className
     * @param string $originClass
     */
    private function addChain(string $namespace, string $className, string $originClass)
    {
        $this->addNamespace($namespace, "class {$className}_parent extends \\$originClass {}");
    }

    /**
     * @param string $namespace
     * @param string $code
     */
    private function addNamespace(string $namespace, string $code)
    {
        $this->namespace[$namespace][] = $code;
    }

    /**
     * @param string $originClass
     * @param string $moduleClass
     */
    private function namespace(string $originClass, string $moduleClass)
    {
        $explode = explode('\\', $moduleClass);
        $moduleClass = array_pop($explode);
        $namespace = implode('\\', $explode);

        $this->addChain($namespace, $moduleClass, $originClass);
    }

    private function saveDataInto($phpFile)
    {
        $script = [
            '<?php',
            '/**',
             '* Builds the module chain that the OXID eShop framework creates at runtime.',
             '* ideal for phpstan.',
             '*',
             '* @generated oxidprojects/oxrun command: ' . $this->getName(),
             '*/',
            '',
        ];

        unset($this->namespace['']); // legasy Klassen müssen raus

        foreach ($this->namespace as $namespace => $codes) {
            $script[] = "namespace {$namespace} {";
            foreach ($codes as $code) {
                $script[] = "    {$code}";
            }
            $script[] = "}";
        }
        $script[] = '';

        (new \Symfony\Component\Filesystem\Filesystem())
            ->dumpFile($phpFile, implode(PHP_EOL, $script));
    }

    private function saveOxideShop($phpFile)
    {
        (new \Symfony\Component\Filesystem\Filesystem())
            ->dumpFile($phpFile, <<<'EOT'
<?php

/**
 * Used by PhpStorm to map factory methods to classes for code completion, source code analysis, etc.
 *
 * The code is not ever actually executed and it only needed during development when coding with PhpStorm.
 *
 * @see http://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @see http://blog.jetbrains.com/webide/2013/04/phpstorm-6-0-1-eap-build-129-177/
 */

namespace PHPSTORM_META {
    override(\oxNew(0), type(0));

    override(Registry::get(0),type(0));
}

EOT
            );
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    private function createSavePlace(InputInterface $input): \stdClass
    {
        $outputDir = $this->getFact()->getShopRootPath();

        if ($input->hasOption('output-dir') && (null !== $input->getOption('output-dir'))) {
            $outputDir = $input->getOption('output-dir');
        }

        $phpstormMetaDir = "{$outputDir}/.phpstorm.meta.php";
        $metaFiles['oxid_module_chain'] = "{$phpstormMetaDir}/oxid_module_chain.meta.php";
        $metaFiles['oxid_esale'] = "{$phpstormMetaDir}/oxid_esale.meta.php";

        if (is_file($phpstormMetaDir)) {
            unlink($phpstormMetaDir);
        }

        if (!is_dir($phpstormMetaDir)) {
            mkdir($phpstormMetaDir, 0777, true);
        }
        return (object)$metaFiles;
    }
}
