<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use slicedup_core\configuration\LibraryRegistry;

/**
 * Depandancies
 */
if(!Libraries::get('slicedup_core')) {
	Libraries::add('slicedup_core');
}

/**
 * Initialize LibraryRegistry to handle configuration
 */
LibraryRegistry::init('sli_users', __DIR__ . '/config.php', array(
	'handler' => '\sli_users\config\Configure'
));

/**
 * Check if the library was added via a standard Libararies::add, add to the
 * LibraryRegistry by also checking for a config file located in the app at
 * app/config/slicedup.users.php
 */
$library = Libraries::get('sli_users');
if (empty($library['registry'])) {
	$source = LITHIUM_APP_PATH . '/config/slicedup.users.php';
	LibraryRegistry::add('sli_users', 'default', $source, array('current' => true));
}