<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2012, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\extensions\adapter\security\auth;

/**
 * The `Persisted` adapter provides basic authentication facilities for checking credentials stored
 * in a user specific persistent storage source, most commonly a browser cookie, against a database.
 */
class Persisted extends \lithium\security\auth\adapter\Form {
	
	/**
	 * Dynamic class dependencies.
	 *
	 * @var array Associative array of class names & their namespaces.
	 */
	protected $_classes = array(
		'storage' => 'lithium\storage\Session'
	);
	
	protected $_storage = array();
	
	protected $_key = '';
	
	protected $_expose = false;
	
	/**
	 * Constructor
	 * 
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		array_push($this->_autoConfig, 'storage', 'key');
		$this->_autoConfig['classes'] = 'merge';
		$defaults = array(
			'fields' => array('token'),
			'key' => '__p'
		);
		$config += $defaults;
		parent::__construct($config + $defaults);
	}
	
	public function check($credentials = null, array $options = array()) {
		$class = $this->_classes['storage'];
		$options += array('storage' => array());
		$storage = $options['storage'] + $this->_storage;
		unset($options['storage']);
		$persisted = $class::read($this->_key, $storage);
		if (is_array($persisted) && count($persisted) == count($this->_fields)) {
			$credentials = new \stdClass();
			if ($this->_expose) {
				$credentials->data = $persisted;
			} else {
				$credentials->data = array_combine($this->_fields, $persisted);	
			}
			return parent::check($credentials, $options);
		}
		return false;
	}
	
	public function set($data, array $options = array()) {
		$class = $this->_classes['storage'];
		$options += array('storage' => array());
		$storage = $options['storage'] + $this->_storage;
		$persist = array();
		foreach ($this->_fields as $key => $field) {
			$persist[$key] = isset($data[$field]) ? $data[$field] : null;
		}
		if ($persist == array_filter($persist)) {
			if (!$this->_expose) {
				$persist = array_values($persist);	
			}
			$class::write($this->_key, $persist, $storage);
			return $data;
		}
		
	}
	
	public function clear(array $options = array()) {
		$class = $this->_classes['storage'];
		$options += array('storage' => array());
		$storage = $options['storage'] + $this->_storage;
		$k = 0;
		foreach ($this->_fields as $key => $field) {
			if (!$this->_expose) {
				$class::delete($this->_key . ".{$k}", $storage);
			} else {
				$class::delete($this->_key . ".{$key}", $storage);
			}
			$k++;
		}
	}
}

?>