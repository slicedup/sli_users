<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use sli_libs\core\LibraryRegistry;

if (!Libraries::get('sli_libs')) {
	Libraries::add('sli_libs');
}

/**
 * Initialize LibraryRegistry to handle configuration
 */
LibraryRegistry::init('sli_users', __DIR__ . '/config.php', array(
	'load' => array('name' => 'default')
));

LibraryRegistry::applyFilter('add.sli_users', function($self, $params, $chain){
	$source = $chain->next($self, $params, $chain);
	$base = LibraryRegistry::base('sli_users');
	$call = 'sli_users\config\Configure';
	return $call::add($params['name'], $source, $base);
});

LibraryRegistry::applyFilter('bootstrap.sli_users', function($self, $params, $chain){
	if($source = $chain->next($self, $params, $chain)) {
		$keys = array_keys($source);
		$call = 'sli_users\config\Configure';
		array_map($call . '::bootstrap', $keys, $source);
	}
});

LibraryRegistry::applyFilter('routes.sli_users', function($self, $params, $chain){
	if($source = $chain->next($self, $params, $chain)) {
		$keys = array_keys($source);
		$call = 'sli_users\config\Configure';
		array_map($call . '::routes', $keys, $source);
	}
});

/**
 * Check if the library was added via a standard Libararies::add, add to the
 * LibraryRegistry by also checking for a config file located in the app at
 * app/config/slicedup.users.php
 */
$library = Libraries::get('sli_users');
$source = LITHIUM_APP_PATH . '/config/sli_users.config.php';
if (empty($library['registry']) && file_exists($source)) {
	LibraryRegistry::add('sli_users', 'default', $source, array(
		'load' => array('name' => 'default')
	));
}