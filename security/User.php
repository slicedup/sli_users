<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\security;

/**
 * The `UserInstance` class provides instance based access to `User` configurations
 *
 * @see Authorized::instance();
 */
class User extends \lithium\core\Object{

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
	protected static $_classes = array(
		'handler' => '\sli_users\security\Authorized'
	);

	/**
	 * Class vars set by default from config array passed to constructor
	 *
	 * @var array
	 */
	protected $_autoConfig = array('configName', 'class');

	public static function &instance($configName, $forceCheck = false, $class = null){
		if (is_null($class) || !class_exists($class)) {
			$class = static::$_classes['handler'];
		}
		return $class::instance($configName, $forceCheck, get_called_class());
	}
	
	/**
	 * Returns user record array
	 *
	 * @param boolean $forceCheck
	 * @return mixed user record array | null
	 */
	public function __invoke($forceCheck = false){
		$class = static::$_classes['handler'];
		return $class::get($this->_configName, $forceCheck);
	}

	/**
	 * Get user record var by key param
	 *
	 * @param string $param
	 * @return mixed user record var | null
	 */
	public function __get($param){
		$class = static::$_classes['handler'];
		return $class::field($this->_configName, $param);
	}

	/**
	 * Set user record var by key param
	 *
	 * @param string $param
	 * @return mixed user record var | null
	 */
	public function __set($param, $value){
		$class = static::$_classes['handler'];
		return $class::field($this->_configName, $param, $value);
	}

	/**
	 * Pass through to methods of the CurrentUser class as set in static::$_class
	 * Prepends config from $this->_configName to args list
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args){
		$class = static::$_classes['handler'];
		if (is_callable(array($class, $method))) {
			array_unshift($args, $this->_configName);
			return call_user_func_array(array($class, $method), $args);
		}
	}
}
?>