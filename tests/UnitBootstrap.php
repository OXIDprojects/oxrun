<?php
/**
 * Autor: Tobias Matthaiou
 * Date: 01.02.21
 * Time: 10:22
 */


$_POST['shp'] = 1;

$bootstrapFilePath = \Webmozart\PathUtil\Path::join((new \OxidEsales\Facts\Facts())->getSourcePath(), 'bootstrap.php');
require_once $bootstrapFilePath;
