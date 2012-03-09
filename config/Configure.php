<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\config;

use lithium\core\Libraries;
use lithium\util\Set;
use sli_base\storage\Registry;
use sli_base\util\Store;
use sli_base\storage\Source;

use lithium\security\Auth;
use lithium\storage\Session;
use lithium\net\http\Router;

/**
 * This `Configure` class is a configuration handling class for the sli_users
 *
 */
class Configure {

	protected static $_base;
	
	public static function init() {
		if (isset(static::$_base)) {
			return;
		}
		static::$_base = Source::read(__DIR__ . '/config.php');
		$library = Libraries::get('sli_users');
		if (!isset($library['config'])) {
			$appConfigFile = LITHIUM_APP_PATH . '/config/bootstrap/sli_users.php';
			if (file_exists($appConfigFile)) {
				$configs = Source::read($appConfigFile);
			} else {
				$configs = array((array('name' => 'default')));	
			}
		} else {
			$configs = $library['config'];
		}
		if (!$configs) {
			return;
		}
		if (isset($configs['name'])) {
			$configs = array($configs);
		}
		$class = get_called_class();
		array_map("{$class}::add", $configs);
	}
	
	/**
	 * Format config for library instance
	 *
	 */
	public static function add($config, $bootstrap = true, $routes = false){
		$config = Store::merge(static::$_base, null, Store::create($config));
		$configKeys = array_keys($config);
		extract($config);

		//set string model
		if (!is_array($model)) {
			$model = array(
				'class' => $model
			) + static::$_base['model'];
		}
		//set string controller
		if (!is_array($controller)) {
			$controller = array(
				'class' => $controller
			) + static::$_base['controller'];
		}
		//set string routing
		if (!is_array($routing)) {
			$routingBase = $routing;
			$routing = static::$_base['routing'];
			$routing['base'] = $routingBase;
		}

		//auths & sessions
		$sessionDefaults = array(
			'adapter' => 'Php'
		);
		$session = static::_formatAdapter($name, 'session', $session, $sessionDefaults);
		$authDefaults = array(
			'adapter' => 'Form',
			'model' => $model['class'],
			'fields' =>  array('username', $model['password']),
			'session' => array('options' => array('name' => $session['name'])),
		);
		$auth = static::_formatAdapter($name, 'auth', $auth, $authDefaults);
		$auth['session']['options']['name'] = $session['name'];

		//set up persistence
		if ($persist) {
			
			$persistDefaults = array(
				'adapter' => 'Persisted',
				'model' => $model['class'],
				'fields' =>  array($model['token']),
				'session' => array('options' => array('name' => $session['name'])),
			);
			
			$p = "persist_{$name}";
			$persist = static::_formatAdapter($p, 'persist', $persist, $persistDefaults);
			
			$storage = isset($persist['storage']) ? $persist['storage'] : 'persist';
			if ($storage) {
				$storageDefaults = array(
					'name' => 'cookie',
					'adapter' => 'Cookie'
				);
				$s = "storage_{$name}";
				$storage = static::_formatAdapter($s, 'storage', $storage, $storageDefaults);
				$persist['storage'] = $storage;	
			}
		}
		
		$config = compact($configKeys);
		Registry::set("sli_users.{$name}", $config);
		if ($bootstrap) {
			static::bootstrap($name);
		}
		if ($routes) {
			static::routes($name);
		}
	}

	/**
	 * Bootstrap library instance
	 *
	 */
	public static function bootstrap($name = null) {
		if (!isset($name)) {
			if ($configs = Registry::keys('sli_users')) {
				$class = get_called_class();
				array_map("{$class}::bootstrap", $configs);
			}
			return;
		}
		if (!($config = Registry::get("sli_users.{$name}"))) {
			return;
		}

		$configKeys = array_keys($config);
		extract($config);

		//set up session / persist cookie
		$_sessions = Session::config();
		//set up session
		if (!isset($_sessions[$session['name']])) {
			$_sessions[$session['name']] = array();
		}
		$_sessions[$session['name']] += $session;
		if ($persist) {			
			$storage = $persist['storage'];
			if (!isset($_sessions[$storage['name']])) {
				$_sessions[$storage['name']] = array();
			}
			$_sessions[$storage['name']] += $storage;
		}

		//set up auth
		$_auths = Auth::config();
		if (!isset($_auths[$auth['name']])) {
			$_auths[$auth['name']] = array();
		}
		$_auths[$auth['name']] += $auth;
		if ($persist) {
			if (!isset($_auths[$persist['name']])) {
				$_auths[$persist['name']] = array();
			}
			unset($persist['storage']['adapter'], $persist['storage']['strategies']);
			$_auths[$persist['name']] += $persist;
		}
		Session::config($_sessions);
		Auth::config($_auths);
	}

	/**
	 * Set up routes for library instance
	 *
	 */
	public static function routes($name = null){
		if (!isset($name)) {
			if ($configs = Registry::keys('sli_users')) {
				$class = get_called_class();
				array_map("{$class}::routes", $configs);
			}
			return;
		}
		if (!($config = Registry::get("sli_users.{$name}"))) {
			return;
		}
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
				'config' => $name
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
	 * @param string $name
	 * @param string $key
	 * @param array $config
	 * @param array $defaults
	 * @return array
	 */
	protected static function _formatAdapter($name, $key, $config, array $defaults = array()) {
		if (is_array($config)) {
			$name = isset($config['name']) ? $config['name'] : $name;
		} else {
			$name = ($config === true) ? $key : $config;
			$config = array();
		}
		$config['name'] = $name;
		if ($defaults) {
			$config = Set::merge($defaults, $config);
		}
		return $config;
	}
}