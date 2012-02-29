<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\controllers;

use lithium\core\Libraries;
use sli_util\net\http\MediaPaths;
use lithium\util\Set;
use sli_util\action\FlashMessage;
use sli_util\storage\Registry;
use sli_users\security\Authorized;

/**
 * The `UsersController` class is the base controller used for all actions of the sli_users
 * library, it should be extended as required to alter & add actions.
 */
class UsersController extends \lithium\action\Controller {

	/**
	 * Runtime config set on __invoke
	 *
	 * @var array
	 */
	public $runtime;
	
	/**
	 * Runtime user instance
	 * 
	 * @var object
	 */
	public $_user;

	/**
	 * Init to set __invoke filter to load runtime config
	 */
	protected function _init(){
		parent::_init();
		$this->applyFilter('__invoke', function($self, $params, $chain) {
			extract($params);
			$action = $request->action;
			$config = $configKey =  null;
			if (!empty($request->params['config'])) {
				$configKey = $request->params['config'];
				$self->runtime = Registry::get("sli_users.{$configKey}");
			}
			if (!$self->runtime) {
				$actionKey = "sli_users.{$configKey}.controller.actions.{$action}";
				$handledAction = Registry::get($actionKey);
				if (isset($handledAction)) {
					$class = get_class($self);
					$exception = "{$class}::{$action} cannot be run without a runtime config.";
					throw new \RuntimeException($exception);
				}
			}
			if ($self->runtime) {
				$self->_user =& Authorized::instance($self->runtime['name'], false,  $self->runtime['class']); 
				$library = Libraries::get('sli_users');
				MediaPaths::setDefaults('html');
				MediaPaths::addPaths('html', array(
					'template' => array(
						"{:library}/views/{$configKey}/{:template}.{:type}.php",
						$library['path'] . '/views/{:controller}/{:template}.{:type}.php',
					)
				), false);
				$self->set(array('sliUserConfig' => $configKey));
			}
			return $chain->next($self, $params, $chain);
		});
	}

	/**
	 * Login action
	 */
	public function login() {
		if (isset($this->request->query['return'])) {
			$this->_user->actionReturn('login', $this->request->query['return']);
		}
		$persist = ($this->runtime['persist'] && isset($this->request->data['remember_me']));
		if ($this->_user->login($this->request, compact('persist'))) {
			return $this->redirect($this->_user->actionReturn('login', false));
		}
		if (isset($this->runtime['template']['login']['fields'])) {
			$fields = $this->runtime['template']['login']['fields'];
		} elseif (isset($this->runtime['auth']['options']['fields'])) {
			$fields = (array) $this->runtime['auth']['options']['fields'];
		} else {
			$fields = array('username', $this->runtime['model']['password']);
		}

		if (!empty($this->request->data)) {
			FlashMessage::error('Login failed, please check your details!');
		}

		$persist = (boolean) $this->runtime['persist'];
		$passwordReset = (boolean) $this->runtime['controller']['actions']['password_reset'];
		$register = (boolean) $this->runtime['controller']['actions']['register'];
		$this->set(compact('status', 'persist', 'register', 'passwordReset', 'fields'));
	}

	/**
	 * Logout action
	 */
	public function logout() {
		if (isset($this->request->query['return'])) {
			$this->_user->actionReturn('logout', $this->request->query['return']);
		}
		$this->_user->logout();
		FlashMessage::success('You have been logged out.');
		$this->redirect($this->_user->actionReturn('logout', false));
	}

	/**
	 * Register action
	 *
	 * @todo registration processing
	 */
	public function register() {
		$status = '';
		if ($this->request->data) {
			$status = 'error';
		}
		if (isset($this->runtime['template']['register']['fields'])) {
			$fields = $this->runtime['template']['register']['fields'];
		} else {
			$model = $this->runtime['model']['class'];
			$schema = $model::schema();
			$key = $model::key();
			unset($schema[$key]);
			$fields = array_keys((array) $schema);
		}
		$this->set(compact('status', 'fields'));
	}

	/**
	 * Password Reset Action
	 *
	 * @todo password reset processing
	 */
	public function password_reset() {
		$status = '';
		if ($this->request->data) {
			$status = 'error';
		}
		if (isset($this->runtime['template']['password_reset']['fields'])) {
			$fields = $this->runtime['template']['password_reset']['fields'];
		} else {
			$fields = array('email');
		}
		$this->set(compact('status', 'fields'));
	}
}