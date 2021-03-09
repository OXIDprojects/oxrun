<?php
/**
 * Created by PhpStorm.
 * Autor: Tobias Matthaiou
 * Date: 09.03.21
 * Time: 14:55
 */

namespace Oxrun\Helper;

/**
 * Class FileStorage
 * @package Oxrun\Helper
 */
class FileStorage
{
    /**
     * @var bool
     */
    protected $useGlobalArgv = true;

    /**
     * @var string
     */
    protected $firstline = '';

    /**
     * @param $path
     * @return false|string
     */
    public function getContent($path)
    {
        $data = '';
        $firstline = '';

        $fileObject = new \SplFileObject($path);

        foreach ($fileObject as $line_num => $line) {
            if ($line_num == 0) {
                $firstline = $line;
            }
            $data .= $line ;
        }

        if (preg_match('/^\s*#/', $firstline)) {
            $this->firstline = $firstline;
        }

        return $data;
    }

    /**
     * Not use command from $argv
     */
    public function noUseGlobalArgv()
    {
        $this->useGlobalArgv = false;
    }

    /**
     * @param $path
     * @param $yamltxt
     * @return false|int
     */
    public function save($path, $yamltxt)
    {
        if ($this->useGlobalArgv) {
            $this->firstline = "# Command: " . join(" ", $GLOBALS['argv']) . PHP_EOL;
        }

        return file_put_contents($path, $this->firstline . $yamltxt);
    }
}
