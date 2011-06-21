<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\security;

use lithium\security\Auth;
use lithium\storage\Session;
use sli_libs\core\LibraryRegistry;

/**
 * The `CurrentUser` class provides basic actions with the current application user. It is intended
 * for use with appplications utilising the sli_users library and is dependant on the runtime
 * configuration for all actions. All auth handling is passed through to \lithium\security\Auth
 */
class CurrentUser extends \lithium\core\StaticObject {

	/**
	 * Instances
	 *
	 * @var array
	 */
	protected static $_instance = array();

	/**
	 * User records
	 *
	 * @var array
	 */
	protected static $_users = array();

	/**
	 * Class Paths of classes used by this object
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'instance' => 'sli_users\security\CurrentUserInstance',
		'registry' =>'sli_users\security\UserRegistry',
		'permission' => null
	);

	/**
	 * Check/Get current user as instance of CurrentUserInstance
	 *
	 * @see sli_users\security\CurrentUserInstance
	 *
	 * @param string $configName
	 * @param boolean $forceCheck
	 * @param unknown_type $instanceClass
	 */
	public static function &instance($configName, $forceCheck = false, $instanceClass = null){
		if (!isset(static::$_instance[$configName])) {
			static::$_instance[$configName] = null;
			if (is_null($instanceClass) || !class_exists($instanceClass)) {
				$instanceClass = static::$_classes['instance'];
			}
			static::get($configName, $forceCheck);
			$class = get_called_class();
			$config = compact('configName', 'class');
			static::$_instance[$configName] = new $instanceClass($config);
		} elseif($forceCheck) {
			if (!static::get($configName, $forceCheck)) {
				static::$_instance[$configName] = null;
			}
		}
		return static::$_instance[$configName];
	}

	/**
	 * Get current user id
	 *
	 * @param string $configName
	 * @param boolean $forceCheck optional check user from Auth::check()
	 */
	public static function id($configName){
		$user = static::get($configName);
		if ($user) {
			$config = static::_config($configName);
			$model = $config['model']['class'];
			$key = $model::key($user);
			if ($key) {
				if (count($key) === 1) {
					$key = current($key);
				}
				return $key;
			}
		}
	}

	/**
	 * Check/Get current user record array
	 *
	 * @param string $configName
	 * @param boolean $forceCheck optional check user from Auth::check()
	 * @return mixed user | null
	 */
	public static function get($configName, $forceCheck = false, $options = array()){
		$defaults = array('loadData' => true, 'field' => null);
		$options += $defaults;
		if (!isset(static::$_users[$configName])) {
			static::$_users[$configName] = null;
			$forceCheck = true;
		}
		if ($forceCheck) {
			if ($config = static::_config($configName)) {
				$persist = !empty($config['persist']);
				$user = Auth::check($config['auth']['name'], $persist);
				static::set($configName, $user, $options);
			}
		}
		if (static::$_users[$configName]) {
			if ($options['field'] && isset(static::$_users[$configName][$options['field']])) {
				return static::$_users[$configName][$options['field']];
			}
		}
		return static::$_users[$configName];
	}

	/**
	 * Get/Set User record array field value
	 *
	 * @param string $configName
	 * @param string $field
	 * @param mixed $value
	 * @param boolean $forceCheck optional
	 * @return mixed value of field
	 */
	public static function field($configName, $field, $value = null, $forceCheck = false){
		if ($value && $user =& static::get($configName, $forceCheck)) {
			$user[$field] = $value;
			return static::set($configName, $user);
		}
		return static::get($configName, $forceCheck, compact('field'));
	}

	/**
	 * Get current users for all configured keys
	 *
	 * @param boolean $forceCheck optional
	 * @return array
	 */
	public static function all($forceCheck = false){
		$users = array();
		if ($keys = LibraryRegistry::keys('sli_users')) {
			foreach ($keys as $config) {
				$users[$config] = static::get($config);
			}
		}
		return $users;
	}

	/**
	 * Set the current user record array
	 *
	 * @param string $configName
	 * @param mixed $user user record array | ...
	 * @param mixed $loadData optional load data from persistent storage boolean | string path
	 * @return boolean
	 */
	public static function set($configName, &$user, $options = array()){
		$defaults = array('persist' => null, 'getData' => true);
		$options += $defaults;
		if ($config = static::_config($configName)) {
			extract($options, EXTR_SKIP);
			static::$_users[$configName] = Auth::set($config['auth']['name'], $user, compact('persist'));
			if ($getData && $user) {
				$dataPath = $getData === true ? null : "$getData";
				static::getData($configName, $dataPath, true);
			}
			return true;
		}
		return false;
	}

	/**
	 * Log a user in by passing through credentials to Auth::check()
	 *
	 * @param string $configName
	 * @param array $userCredentials user login fields as configured with Auth
	 * @param boolean $persist optional persist user beyond session if configured
	 * @param mixed $loadData optional load data from persistent storage boolean | string path
	 * @return mixed user | null
	 */
	public static function login($configName, $userCredentials, $options = array()){
		$defaults = array('persist' => false, 'getData' => true);
		$options += $defaults;
		if ($config = static::_config($configName)) {
			extract($options, EXTR_SKIP);
			$persist = $persist && !empty($config['persist']);
			$user = Auth::check($config['auth']['name'], $userCredentials, compact('persist'));
			static::set($configName, $user, $options);
			return static::get($configName);
		}
	}

	/**
	 * Log a user out optionally clear any stored data
	 *
	 * @param string $configName
	 * @param boolean $clearStorage optional clear user data in persistent storage
	 * @return boolean true | null
	 */
	public static function logout($configName, $options = array()){
		$defaults = array('deleteData' => true);
		$options += $defaults;
		if ($config = static::_config($configName)) {
			extract($options, EXTR_SKIP);
			if ($deleteData) {
				static::deleteData($configName);
			}
			Auth::clear($config['auth']['name']);
			return true;
		}
	}

	/**
	 * Get current user, redirect to login if not set
	 *
	 * @param string $configName
	 * @param Controller $controller current controller instance
	 * @param unknown_type $actions
	 * @param mixed $returnUrl mixed url to store in session for redirecting to after login action
	 * @return
	 */
	public static function required($configName, $controller, $actions = true, $returnUrl = null){
		if ($user = static::get($configName)) {
			return $user;
		}
		$params = $controller->request->params;
		if ($actions === true || in_array($params['action'], (array) $actions)) {
			$return = $returnUrl;
			if ($return === null && $controller) {
				$return = '/' . $controller->request->url;
			}
			return static::action($configName, $controller, 'login', compact('return'));
		}
	}

	/**
	 * Goto user action
	 *
	 * @param string $configName
	 * @param Controller $controller current controller instance
	 * @param string $action optional an action defined in the sli_users config under key
	 *        controller.actions
	 * @param array $options optional keys :
	 *        - `redirect` boolean redirect to action default true
	 *        - `return` mixed url to store in session for redirecting to after action
	 * @return
	 */
	public static function action($configName, $controller, $action = 'login', $options = array()){
		$defaults = array('redirect' => true, 'return' => null);
		$options += $defaults;
		if ($config = static::_config($configName)) {
			$actions = $config['controller']['actions'];
			if (isset($actions[$action]) && $actions[$action]) {
				$location = array(
					'controller' => $config['controller']['class'],
					'action' => $action,
					'config' => $configName
				);
				extract($options, EXTR_SKIP);
				if ($redirect && $controller) {
					$redirectOptions = is_array($redirect) ? $redirect : array();
					if ($return) {
						static::actionReturn($configName, $action, $return);
					}
					return $controller->redirect($location, $redirectOptions);
				}
				return $location;
			}
		}
	}

	/**
	 * Set/Get/Delete redirect location to session for redirect after actions
	 *
	 * @param string $configName
	 * @param Controller $controller current controller instance
	 * @param mixed $returnUrl mixed url to store in session for redirecting to after action
	 * @param string $default fallback url
	 * @return
	 */
	public static function actionReturn($configName, $action, $returnUrl = null, $options = array()){
		$defaults = array('default' => '/');
		$options += $defaults;
		if ($config = static::_config($configName)) {
			$auth = Auth::config($config['auth']['name']);
			$sessionReturnKey = "_{$auth['session']['key']}Redirects.{$action}";
			extract($options, EXTR_SKIP);
			if ($returnUrl) {
				Session::write($sessionReturnKey, $returnUrl, $auth['session']['options']);
				$actionReturn = $returnUrl;
			} else {
				$actionReturn = Session::read($sessionReturnKey, $auth['session']['options']);
				if (!$actionReturn) {
					if (isset($config['routing']["{$action}Redirect"])) {
						$actionReturn = $config['routing']["{$action}Redirect"];
					}
					if (empty($actionReturn)) {
						$actionReturn = $default;
					}
				}
			}
			if ($returnUrl === false) {
				Session::delete($sessionReturnKey, $auth['session']['options']);
			}
			return $actionReturn;
		}
	}

	/**
	 * Convenience method for data methods
	 *
	 * @param string $configName
	 * @param mixed $path optional null | string path
	 * @param mixed $value optional
	 */
	public static function data($configName, $path = null, $value = null){
		if ($path === false) {
			return static::deleteData($configName);
		} elseif (isset($value)) {
			return static::setData($configName, $path, $value);
		} else {
			return static::getData($configName, $path);
		}
	}

	/**
	 * Set arbitrary data for a user.
	 *
	 * @param string $configName
	 * @param mixed $path optional null | string path
	 * @param mixed $value optional
	 */
	public static function setData($configName, $path = null, $value = null){
		if ($config = static::_config($configName)) {
			$id = static::id($configName);
			$base = "$configName.$id";
			if (is_array($path)) {
				$value = $path;
				$path = $base;
			} elseif ($path !== false) {
				$path = $path ? "$base.$path" : $base;
			}
			$auth = Auth::config($config['auth']['name']);
			$sessionKey = "_{$auth['session']['key']}Data.{$path}";
			$registry = static::$_classes['registry'];
			$registry::set($path, $value);
			$data = $registry::get($path);
			return Session::write($sessionKey, $data, $auth['session']['options']);
		}
	}

	/**
	 * Get arbitrary data for a user.
	 *
	 * @param string $configName
	 * @param mixed $path optional null | string path | array paths => values | boolean false
	 * @param boolean $force optional
	 */
	public static function getData($configName, $path = null, $force = false){
		if ($config = static::_config($configName)) {
			$id = static::id($configName);
			$base = "$configName.$id";
			$path = $path ? "$base.$path" : $base;
			$auth = Auth::config($config['auth']['name']);
			$sessionKey = "_{$auth['session']['key']}Data.{$path}";
			$registry = static::$_classes['registry'];
			$data = $registry::get($path);
			if (!$data || $force) {
				$data = Session::read($sessionKey, $auth['session']['options']);
				if ($data) {
					$registry::set($path, $data);
					$data = $registry::get($path);
				}
			}
			return $data;
		}
	}

	/**
	 * Delete arbitrary data for a user.
	 *
	 * @param string $configName
	 * @param mixed $path optional null | string path
	 */
	public static function deleteData($configName, $path = null){
		if ($config = static::_config($configName)) {
			$id = static::id($configName);
			$base = "$configName.$id";
			$path = $path ? "$base.$path" : $base;
			$auth = Auth::config($config['auth']['name']);
			$sessionKey = "_{$auth['session']['key']}Data.{$path}";
			$registry = static::$_classes['registry'];
			$registry::delete($path);
			return Session::delete($sessionKey, $auth['session']['options']);
		}
	}

	/**
	 * Permission check
	 *
	 * @see CurrentUser::permission()
	 */
	public static function can($configName, $action, $aco){
		return static::permission($configName, $action, $aco, __FUNCTION__);
	}

	/**
	 * Grant permission
	 *
	 * @see CurrentUser::permission()
	 */
	public static function allow($configName, $action, $aco){
		return static::permission($configName, $action, $aco, __FUNCTION__);
	}

	/**
	 * Revoke permission
	 *
	 * @see CurrentUser::permission()
	 */
	public static function deny($configName, $action, $aco){
		return static::permission($configName, $action, $aco, __FUNCTION__);
	}

	/**
	 * Pass off permission checking to permission class
	 *
	 * @param string $configName
	 * @param mixed $action
	 * @param mixed $aco
	 * @param string $method
	 * @return mixed result of permission method | null
	 */
	public static function permission($configName, $action, $aco, $method = 'can'){
		$permission = static::$_classes['permission'];
		if ($permission && class_exists($permission) && is_callable(array($permission, $method))) {
			$config = static::_config($configName);
			$id = static::id($configName);
			if ($id && $config) {
				$aro = array(
					'entity' => $config['model']['class'],
					'key' => $id
				);
				return $permission::$method($aro, $aco, $action);
			}
		}
	}

	/**
	 * Load passed config name
	 *
	 * @param string $configName
	 * @return mixed config
	 */
	protected static function _config($configName){
		if ($configName === null) {
			$configName = LibraryRegistry::current('sli_users', null, true);
		}
		return LibraryRegistry::get("sli_users.$configName");
	}
}

/**
 * The `CurrentUserInstance` class provides instnace based access to `CurrentUser` configurations
 *
 * @see CurrentUser::instance();
 */
class CurrentUserInstance extends \lithium\core\Object{

	/**
	 * Instance config name
	 *
	 * @var string
	 */
	protected $_configName;

	/**
	 * Classname that instantiated this object that is used for all user actions
	 *
	 * @var string
	 */
	protected $_class = '\sli_users\security\CurrentUser';

	/**
	 * Class vars set by default from config array passed to constructor
	 *
	 * @var array
	 */
	protected $_autoConfig = array('configName', 'class');

	/**
	 * Returns user record array
	 *
	 * @param boolean $forceCheck
	 * @return mixed user record array | null
	 */
	public function __invoke($forceCheck = false){
		$class = $this->_class;
		return $class::get($this->_configName, $forceCheck);
	}

	/**
	 * Get user record var by key param
	 *
	 * @param string $param
	 * @return mixed user record var | null
	 */
	public function __get($param){
		$class = $this->_class;
		return $class::field($this->_configName, $param);
	}

	/**
	 * Set user record var by key param
	 *
	 * @param string $param
	 * @return mixed user record var | null
	 */
	public function __set($param, $value){
		$class = $this->_class;
		return $class::field($this->_configName, $param, $value);
	}

	/**
	 * Pass through to methods of the CurrentUser class as set in $this->_class
	 * Prepends config from $this->_configName to args list
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args){
		$class = $this->_class;
		if (is_callable(array($class, $method))) {
			array_unshift($args, $this->_configName);
			return call_user_func_array(array($class, $method), $args);
		}
	}
}

/**
 * The `UserRegistry` provides storage for arbitrary user data for `CurrentUser`
 */
class UserRegistry extends \slicedup_core\configuration\Registry{

	/**
	 * Registry Data Storage
	 *
	 * @var array
	 */
	protected static $_storage = array();
}