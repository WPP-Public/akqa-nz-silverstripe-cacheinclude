<?php

if (file_exists(__DIR__ . '../vendor/autoload.php')) {
    require_once __DIR__ . '../vendor/autoload.php';
}

Director::addRules(100, array(
    'cache-include//$Action' => 'CacheIncludeController'
));

Heyday\CacheInclude\Container::extendConfig(array(
    'cacheinclude.options.force_expire' => isset($_GET['flush']) && $_GET['flush']
));
