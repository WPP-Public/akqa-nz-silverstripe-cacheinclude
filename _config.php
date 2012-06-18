<?php

Object::add_extension('DataObject', 'CacheIncludeExtension');
Object::add_extension('ContentController', 'CacheIncludeExtension');

Director::addRules(20, array(
	'cache-include//$Action' => 'CacheIncludeController'
));

if (isset($_GET['flush']) && $_GET['flush'] == 'allcache') {

	CacheIncludeExtension::clearAll();

}