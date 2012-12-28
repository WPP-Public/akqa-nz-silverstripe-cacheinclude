<?php

$dir = __DIR__ . '/..';

if (file_exists($dir . '/vendor/autoload.php')) {

	$loader = require_once $dir . '/vendor/autoload.php';

	$loader->addClassMap(Composer\Autoload\ClassMapGenerator::createMap($dir . '/framework'));
	$loader->add('Heyday\CacheInclude\Tests', __DIR__);

}
