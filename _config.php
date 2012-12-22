<?php

Object::add_extension('SiteTree', 'CacheIncludeSiteTreeDecorator');
Object::add_extension('DataObject', 'CacheIncludeExtension');
Object::add_extension('ContentController', 'CacheIncludeExtension');

// Director::addRules(20, array(
//     'cache-include//$Action' => 'CacheIncludeController'
// ));

CacheIncludeContainer::extendConfig(array(
	'cacheinclude.options.force_expire' => isset($_GET['flush']) && $_GET['flush']
));
