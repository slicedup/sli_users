<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\config;

use lithium\security\Auth;
use lithium\storage\Session;
use lithium\net\http\Router;

/**
 * This `Configure` class is a configuration handling class for the sli_users
 * library which implements sli_libs\core\LibraryRegistry to define runtime
 * configurations applied to this library as described in ./config.php. This
 * class is not intended for direct usage but to be called by the
 * LibraryRegistry initializing & altering config instances for this library.
 *
 */
class Configure {

	/**
	 * Format config for library instance
	 *
	 */
	public static function add($configName, $config, $base){
		$configKeys = array_keys($base);
		extract($config);

		//set string model
		if (!is_array($model)) {
			$model = array(
				'class' => $model
			) + $base['model'];
		}
		//set string controller
		if (!is_array($controller)) {
			$controller = array(
				'class' => $controller
			) + $base['controller'];
		}
		//set string routing
		if (!is_array($routing)) {
			$routingBase = $routing;
			$routing = $base['routing'];
			$routing['base'] = $routingBase;
		}

		//auths & sessions
		$sessionDefaults = array(
			'adapter' => 'Php'
		);
		$session = static::_formatAdapter($configName, 'session', $session, $sessionDefaults);
		$authDefaults = array(
			'model' => $model['class'],
			'adapter' => 'Form',
			'fields' =>  array('username', $model['password'])
		);
		$auth = static::_formatAdapter($configName, 'auth', $auth, $authDefaults);
		$auth['session']['options']['name'] = $session['name'];

		//set up persistence
		if ($persist) {
			$persistDefaults = array(
				'adapter' => 'Cookie'
			);
			$persist = static::_formatAdapter($configName, 'persist', $persist, $persistDefaults);
			if (array_key_exists('encryptionSalt', $persist)) {
				$auth['encryptionSalt'] = $persist['encryptionSalt'];
				unset($persist['encryptionSalt']);
			}
			$auth['storage']['options']['name'] = $persist['name'];
			$auth['adapter'] = 'PersistentForm';
		}
		return compact($configKeys);
	}

	/**
	 * Bootstrap library instance
	 *
	 */
	public static function bootstrap($configName, $config) {
		$configKeys = array_keys($config);
		extract($config);

		//set up session / persist cookie
		$_sessions = Session::config();
		//set up session
		if (!isset($_sessions[$session['name']])) {
			$_sessions[$session['name']] = array();
		}
		$_sessions[$session['name']] += $session;
		$session = $session['name'];
		if ($persist) {
			if (!isset($_sessions[$persist['name']])) {
				$_sessions[$persist['name']] = array();
			}
			$_sessions[$persist['name']] += $persist;
			$persist = $persist['name'];
		}
		Session::config($_sessions);

		//set up auth
		$_auths = Auth::config();
		if (!isset($_auths[$auth['name']])) {
			$_auths[$auth['name']] = array();
		}
		$_auths[$auth['name']] += $auth;
		$auth = $auth['name'];
		Auth::config($_auths);

		return compact($configKeys);
	}

	/**
	 * Set up routes for library instance
	 *
	 */
	public static function routes($configName, $config){
		extract($config);
		$actions = array_filter($controller['actions']);
		foreach ($actions as $action => $route) {
			if (strpos($route, '/') === 0) {
				$url = $route;
			} else {
				$url = $routing['base'] . '/' . $route;
			}
			Router::connect($url, array(
				'controller' => $controller['class'],
				'action' => $action,
				'config' => $configName
			),
			array(
				'persist' => array(
					'controller',
					'config'
				)
			));
		}
	}

	/**
	 * Format adapter configurations used for Session & Auth
	 * Primarily transforms string configs into array, where
	 * return array always at least has a `name` key
	 *
	 * @param string $configName
	 * @param string $key
	 * @param array $config
	 * @param array $defaults
	 * @return array
	 */
	protected static function _formatAdapter($configName, $key, $config, array $defaults = array()) {
		if (is_array($config)) {
			$name = isset($config['name']) ? $config['name'] : $configName;
		} else {
			$name = $config;
			$config = array();
		}
		$config['name'] = $name;
		if ($defaults) {
			$config += $defaults;
		}
		return $config;
	}
}