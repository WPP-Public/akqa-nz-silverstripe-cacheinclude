<?php

define('BASE_PATH', realpath(dirname(__DIR__)));

if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    echo 'You must first install the vendors using composer.' . PHP_EOL;
    exit(1);
}

$loader = require BASE_PATH . '/vendor/autoload.php';

$loader->addClassMap(Symfony\Component\ClassLoader\ClassMapGenerator::createMap(BASE_PATH . '/sapphire'));
$loader->add('Heyday\CacheInclude\Tests', __DIR__);
