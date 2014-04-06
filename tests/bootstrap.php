<?php

use Symfony\Component\ClassLoader\ClassMapGenerator;

define('BASE_PATH', realpath(dirname(__DIR__)));

if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    echo 'You must first install the vendors using composer.' . PHP_EOL;
    exit(1);
}

$loader = require BASE_PATH . '/vendor/autoload.php';

$classMap = ClassMapGenerator::createMap(BASE_PATH . '/framework');
unset($classMap['PHPUnit_Framework_TestCase']);
$loader->addClassMap($classMap);
