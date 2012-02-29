<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * Default configuration
 *
 * @var $config array
 */
$config = array(

	/**
	 * User class
	 */
	'class' => 'sli_users\security\User',

	/**
	 * Model for user process data string class name | array keys:
	 *  - class model class name fully namespaced,
	 *  - meta any params to pass to Model::meta()
	 *    this is handy if you want to use the default model
	 *    supplied in the library but have it access a different
	 *    source or connection, or use a non default connection
	 *    for the processes run by this library
	 *  - password the name of the password field, this is double
	 *    confirmed on registration by default & is the field
	 *    that gets reset on assword reset action
	 *  - token name of the token field the token is used as a
	 *    identifer for the user throughout various auth
	 *    processes, by default it chnages when a user logs in
	 *    so this field should be exclusively for use by the
	 *    library with no other dependancies
	 */
	'model' => array(
		'class' => '\sli_users\models\Users',
		'meta' => array(),
		'password' => 'password',
		'token' => 'token'
	),

	/**
	 * Controller for user processes array keys:
	 *  - class controller class name fully namespaced
	 *  - actions array action names => routes used to access them
	 *    routes starting with / are taken as absolute, otherwise routing
	 *    base is prepended. Any actions not required should be set to false
	 */
	'controller' => array(
		'class' => 'sli_users\controllers\UsersController',
		'actions' => array(
			'login' => 'login',
			'logout' => 'logout',
			'register' => 'register',
			'password_reset' => 'password_reset'
		)
	),

	/**
	 * Template to set custom layout & view templates and view vars
	 */
	'template' => array(
		'register' => array(
			'fields' => array( //optional but we'll set defaults
				'first_name',
				'last_name',
				'email',
				'username',
				'password'
			)
		),
		'login' => array(
			//'fields' => array() optional fields for login
		),
		'password_reset' => array(
			//'fields' => array() optional fields for reset
		)
	),

	/**
	 * Routing directives array keys:
	 *  - base url prepended to all actions that are not absolute
	 *  - loginRedirect default redirect for login action defaults to base
	 *    if not set or passed to login page via GET param 'return'
	 *  - logout default logout redirect location default to login
	 */
	'routing' => array(
		'base' => '/users',
		'loginRedirect' => '',
		'logoutRedirect' => ''
	),

	/**
	 * Session configuration for Auth
	 * string config name to use | create config array keys:
	 * 	- name session configuration name to use create
	 *  - {s} option keys as per session adapter
	 *
	 * When creating the session configs if one exists with the name
	 * specified it will not be recreated but used as is
	 */
	'session' => array(),

	/**
	 * Auth Adapter configuration
	 * string config name to use | create config array keys:
	 *  - name auth configuration name to use/create
	 *  - {s} keys as per auth adapter
	 *
	 * Note when config is created the option 'model' is overidden to
	 * match model specifified above and 'adapater' is set to Form.
	 * When creating the auth configs if one exists with the name
	 * specified it will not be recreated but used as is
	 */
	'auth' => array(),

	/**
	 * Persist configuration for cookie
	 * false do not provide this feature | config array
	 *  - name auth configuration name to use/create
	 *  - encryptionSalt used to hash the cookie values string | null
	 *  - {s} option keys as per session adapter
	 */
	'persist' => array(
		'name' => '__u',
		'encryptionSalt' => sha1(__FILE__),
		'adapter' => 'Cookie',
		'expire' => '+2 weeks'
	)
);