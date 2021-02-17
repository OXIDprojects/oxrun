<?php

namespace Oxrun\Command\Misc;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('misc:phpstorm:metadata')->setDescription(
                'Generate a PhpStorm metadata file for auto-completion.'
            )->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_REQUIRED,
                'Writes the metadata for PhpStorm to the specified directory.'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $metadates = (new \Symfony\Component\Finder\Finder())
            ->in(OX_BASE_PATH . 'modules')
            ->name('/metadata.php/')
            ->depth(2)
            ->files();


        foreach($metadates as $file) {
            $realPath = $file->getRealPath();

            $output->writeln("<info>". str_replace(OX_BASE_PATH . 'modules', '', $realPath) . '</info>');

            include $realPath;

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

        $phpstormMetaFile = $this->createSavePlace($input);

        $this->saveDataInto($phpstormMetaFile);

        $output->writeln("PHPStormMetaFile is saved: $phpstormMetaFile");

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
        $script = [$this->getMetaHeader()];

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

    /**
     * @return string
     */
    private function getMetaHeader()
    {
        return <<<'EOT'
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

EOT;
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    private function createSavePlace(InputInterface $input)
    {
        $outputDir = INSTALLATION_ROOT_PATH;

        if ($input->hasOption('output-dir') && (null !== $input->getOption('output-dir'))) {
            $outputDir = $input->getOption('output-dir');
        }

        $phpstormMetaDir = "{$outputDir}/.phpstorm.meta.php";
        $phpstormMetaFile = "{$phpstormMetaDir}/oxid_module_extend.meta.php";

        if (is_file($phpstormMetaDir)) {
            unlink($phpstormMetaDir);
        }

        if (!is_dir($phpstormMetaDir)) {
            mkdir($phpstormMetaDir, 0777, true);
        }
        return $phpstormMetaFile;
    }
}
