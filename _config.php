<?php

if (file_exists(__DIR__ . '../vendor/autoload.php')) {
	require_once __DIR__ . '../vendor/autoload.php';
}

Object::add_extension('SiteTree', 'CacheIncludeSiteTreeDecorator');
Object::add_extension('DataObject', 'CacheIncludeExtension');
Object::add_extension('ContentController', 'CacheIncludeExtension');

// Director::addRules(20, array(
//     'cache-include//$Action' => 'CacheIncludeController'
// ));

Heyday\CacheInclude\Container::extendConfig(array(
    'cacheinclude.options.force_expire' => isset($_GET['flush']) && $_GET['flush']
));
