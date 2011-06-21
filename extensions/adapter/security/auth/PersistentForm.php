<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\extensions\adapter\security\auth;

use lithium\core\Libraries;

/**
 * The `PersistentForm` adapter provides basic authentication facilities for checking credentials stored
 * in a user specific persistent storage source, most commonly a browser cookie, against a database.
 */
class PersistentForm extends \lithium\security\auth\adapter\Form {

	/**
	 * The list of fields to extract from the user record tp set to the persistent storage and
	 * use when querying the database. This can either be a simple array of field names, or a
	 * set of key/value pairs, which map the field names to alternate keys in the storage
	 *
	 * @var array
	 */
	protected $_persistFields = array();

	/**
	 * List of configuration properties to automatically assign to the properties of the adapter
	 * when the class is constructed.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('model', 'fields', 'persistFields', 'scope', 'filters' => 'merge', 'query');

	/**
	 * Dynamic class dependencies.
	 *
	 * @var array Associative array of class names & their namespaces.
	 */
	protected $_classes = array(
		'storage' => 'lithium\storage\Session'
	);

	/**
	 * Overiden to not persist the auth by default when checking passed in credentials
	 * To persist the auth this must be explicitly set by passing the option key `persist` set to
	 * true when calling Auth::check()
	 *
	 * @var boolean
	 */
	protected $_persist = false;

	/**
	 * Sets the initial configuration for the `Persistent` adapter, as detailed below.
	 *
	 * @see lithium\security\auth\adapter\Form::$_model
	 * @see lithium\security\auth\adapter\Form::$_fields
	 * @see lithium\security\auth\adapter\Form::$_filters
	 * @see lithium\security\auth\adapter\Form::$_query
	 * @param array $config Sets the configuration for the adapter, which has the following options:
	 *              - `'model'` _string_: The name of the model class to use. See the `$_model`
	 *                property for details.
	 *              - `'fields'` _array_: The model fields to query against when taking input from
	 *                the request data. See the `$_fields` property for details.
	 *              - `'persist'` _array_: The model fields to set to the persistent storage and
	 *                subsequently query against when reading from the persistent storage.
	 *              - `'filters'` _array_: Named callbacks to apply to request data before the user
	 *                lookup query is generated. See the `$_filters` property for more details.
	 *              - `'query'` _string_: Determines the model method to invoke for authentication
	 *                checks. See the `$_query` property for more details.
	 *              - `'storage'` _array_: the persistent storage source usually a cookie
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'model' => 'User',
			'query' => 'first',
			'filters' => array(),
			'fields' => array(
				'username', 'password'
			),
			'persistFields' => array(
				'token'
			),
			'storage' => array(
				'key' => '_p_',
				'class' => $this->_classes['storage'],
				'options' => array()
			),
			'encryptionSalt' => false
		);
		$config['storage'] += $defaults['storage'];
		$config += $defaults;
		parent::__construct($config);
	}

	/**
	 * Called by the `Auth` class to run an authentication check against a model class using the
	 * credientials as an array, and returns an array of user
	 * information on success, or `false` on failure.
	 *
	 * @param null $credentialsare not used specifically by this adapter and should be passed as
	 * true when calling Auth::check. Optionally this can howevere be a request object as passed to
	 * the form adapter, that in conjunction with the config key 'form' for this adapater will
	 * allow the credentials to be passed through to a form based auth config by that key
	 * @param array $options Additional configuration options to pass through to the storage source
	 * @return array Returns an array containing user information on success, or `false` on failure.
	 */
	public function check($credentials = null, array $options = array()) {
		$storage = $this->_config['storage']['class'];
		$defaults = array('storage' => array(), 'persist' => null);
		$options += $defaults;
		if (is_object($credentials)) {
			$this->_persist = false;
			if (isset($options['persist'])) {
				$this->_persist = (boolean) $options['persist'];
			} elseif($credentialsPersisted = $this->_persisted($options['storage'])) {
				$this->_persist = true;
			}
			$user = parent::check($credentials, $options);
			if ($user) {
				return $user;
			}
		}
		if (!isset($credentialsPersisted)) {
			$credentialsPersisted = $this->_persisted($options['storage']);
		}
		if ($credentialsPersisted) {
			$credentials = json_decode($this->_decrypt($credentialsPersisted));
			if ($credentials) {
				$model = $this->_model;
				$query = $this->_query;
				$filtered = $this->_filters($credentials, $this->_persistFields);
				$conditions = $this->_scope + $filtered;
				$user = $model::$query(compact('conditions'));
				return $user ? $user->data() : false;
			}
		}
		return false;
	}

	/**
	 * A pass-through method called by `Auth`. This creates the persistent storage key
	 * later used to identify a user.
	 * Returns the value of `$data`, which is written to a user's session.
	 *
	 * @param array $data User data to be written to the session, this will only write fields
	 * presnet in the fields array of this adapter's configuration
	 * @param array $options Adapter-specific options to pass through to the storage source.
	 * @return array Returns the value of `$data`.
	 */
	public function set($data, array $options = array()) {
		$defaults = array('storage' => array(), 'persist' => null);
		$options += $defaults;
		if (isset($options['persist'])) {
			$this->_persist = (boolean) $options['persist'];
		} elseif($credentialsPersisted = $this->_persisted($options['storage'])) {
			$this->_persist = true;
		}
		if ($this->_persist) {
			if (!isset($credentialsPersisted)) {
				$credentialsPersisted = $this->_persisted($options['storage']);
			}
			$storage = $this->_config['storage']['class'];
			if (!$credentialsPersisted || !$this->_persistedMatch($credentialsPersisted, $data)) {
				$this->_persist(false, $options['storage']);
				if ($data) {
					$persist  = array();
					foreach ($this->_persistFields as $key => $field) {
						$persist[$key] = isset($data[$field]) ? $data[$field] : null;
					}
					$persist = $this->_encrypt(json_encode($persist));
					$this->_persist($persist, $options['storage']);
				}
			}
		}
		return $data;
	}

	/**
	 * Called by `Auth` when a user session is terminated. Removes the key from the
	 * persistent storage
	 *
	 * @param array $options Adapter-specific options to pass through to the storage source.
	 * @return void
	 */
	public function clear(array $options = array()) {
		$storage = $this->_config['storage']['class'];
		$defaults = array('storage' => array());
		$options += $defaults;
		$this->_persist(false, $options['storage']);
	}

	/**
	 * Calls each registered callback, by field name.
	 *
	 * @param string $data Keyed form data.
	 * @return mixed Callback result.
	 */
	protected function _filters($data, array $fields = array()) {
		$result = array();
		if (!$fields) {
			$fields = $this->_fields;
		}
		foreach ($fields as $key => $field) {
			$result[$field] = isset($data[$key]) ? $data[$key] : null;

			if (isset($this->_filters[$key])) {
				$result[$field] = call_user_func($this->_filters[$key], $result[$field]);
			}
		}
		return isset($this->_filters[0]) ? call_user_func($this->_filters[0], $result) : $result;
	}

	/**
	 * Get persisted data, optionaly mach against record array
	 *
	 * @param array $options
	 * @param array $match
	 * @return mixed credentials perosted | null
	 */
	protected function _persisted($options = array(), $match = array()){
		$storage = $this->_config['storage']['class'];
		$_options = $this->_config['storage']['options'];
		$options += $_options;
		$credentialsPersisted = $storage::read($this->_config['storage']['key'], $options);
		if ($credentialsPersisted && $match) {
			$credentialsPersisted = $this->_persistedMatch($credentialsPersisted, $match);
		}
		return $credentialsPersisted;
	}

	/**
	 * Match persisted data against record array
	 *
	 * @param string $credentialsPersisted
	 * @param array $match
	 * @return mixed credentials match | null
	 */
	protected function _persistedMatch($credentialsPersisted, $match){
		$credentials = json_decode($this->_decrypt($credentialsPersisted));
		if ($credentials) {
			foreach ($credentials as $field => $value) {
				if (!isset($match[$field]) || $match[$field] != $value) {
					$credentialsPersisted = null;
					break;
				}
			}
		}
		return $credentialsPersisted;
	}

	/**
	 * Set/Delete persisted data
	 *
	 * @param string $persist
	 * @param array $options
	 * @return mixed result of storage action
	 */
	protected function _persist($persist, $options = array()){
		$storage = $this->_config['storage']['class'];
		$_options = $this->_config['storage']['options'];
		$options += $_options;
		if (!$persist) {
			return $storage::delete($this->_config['storage']['key'], $options);
		} else {
			return $storage::write($this->_config['storage']['key'], $persist, $options);
		}
	}

	/**
	 *
	 * @param string $str
	 */
	protected function _encrypt($str){
		$key = $this->_mcryptKey();
		if ($key) {
			$iv = mcrypt_create_iv(16, MCRYPT_RAND);
			$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_CBC, $iv);
			$encoded = base64_encode($encrypted . '::' . $iv);
			return $encoded;
		}
		return $str;
	}

	/**
	 *
	 * @param unknown_type $str
	 */
    protected function _decrypt($str){
    	$key = $this->_mcryptKey();
		if ($key) {
			$key = $this->_mcryptKey();
			$decoded = base64_decode($str);
			list($str, $iv) = explode('::', $decoded, 2);
			$decrypted = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_CBC, $iv);
			return $decrypted;
		}
		return $str;
	}

	/**
	 *
	 */
	protected function _mcryptKey(){
		if (!empty($this->_config['encryptionSalt']) && extension_loaded('mcrypt')) {
			return md5($this->_config['encryptionSalt']);
		}
	}
}