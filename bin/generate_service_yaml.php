#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 27.01.21
 * Time: 16:25
 */

$search_autoloader = [
    __DIR__ . "/../vendor/autoload.php",
    __DIR__ . "/../../../../vendor/autoload.php",
];

$isAutoloaderFound = null;

foreach ($search_autoloader as $autoloader) {
    if (file_exists($autoloader)) {
        $isAutoloaderFound = include $autoloader;
    }
}

if ($isAutoloaderFound === null) {
    echo "Please run `composer install`" . PHP_EOL;
    exit(2);
}

file_put_contents(
    __DIR__ . '/../services.yaml',
    (new Commands())->find()->extract()->sort()->getServiceYaml()
);

echo realpath(__DIR__ . '/../services.yaml') . ' is success generated';


class Commands
{
    /**
     * @var int
     */
    private $yamlInline = 3;

    /**
     * @var array[]
     */
    protected $service_yaml = [ 'services' => [] ];

    /**
     * @var RecursiveIteratorIterator
     */
    private $commandsPhps = [];

    /**
     * @return $this
     */
    public function find()
    {
        $this->commandsPhps = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                __DIR__ . '/../src/Oxrun/Command',
                FilesystemIterator::SKIP_DOTS
            )
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function extract()
    {
        /** @var SplFileInfo $item */
        foreach ($this->commandsPhps as $item) {

            $class = $this->extractClassName($item);

            if (empty($class)) {
                @trigger_error($item->getRealPath() . ' has not loaded Class' . PHP_EOL);
                continue;
            }

            $this->addCommand($class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function sort()
    {
        asort($this->service_yaml['services']);
        return $this;
    }

    /**
     * @return string
     */
    public function getServiceYaml(): string
    {
        return \Symfony\Component\Yaml\Yaml::dump($this->service_yaml, $this->yamlInline);
    }

    /**
     * @param SplFileInfo $file
     */
    private function extractClassName($file)
    {
        $classesBefore = get_declared_classes();

        include_once $file->getRealPath();

        $classesAfter = get_declared_classes();

        $newClasses = array_diff($classesAfter, $classesBefore);

        return array_shift($newClasses);
    }

    /**
     * @param $class
     * @param array $service_yaml
     */
    private function addCommand($class)
    {
        $this->service_yaml['services'][$class] = [
            'tags' => [
                'name' => 'console.command',
                'command' => $this->extractCommandName($class)
            ]
        ];

    }

    private function extractCommandName($class)
    {
        /** @var \Symfony\Component\Console\Command\Command $consoleCommand */
        $consoleCommand = new $class;
        return $consoleCommand->getName();
    }
}

?>

