<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_users\models;

/**
 * The `Users` class is the default model for use with the sli_users library
 *
 * Password & token create/update handling should be added to your own models.
 * For example:
 *
 * {{{
 * static::applyFilter('create', function($self, $params, &$chain) {
 * 		$record = $chain->next($self, $params, $chain);
 * 		if (!empty($record->password)) {
 * 			$record->new_password = $record->password;
 * 		} else {
 * 			$record->new_password = $record->password = bin2hex(String::random(4));
 * 		}
 * 		$record->token = String::uuid();
 * 		return $record;
 * });
 * }}}
 *
 * {{{
 * static::applyFilter('save', function($self, $params, &$chain) {
 * 		$record = $params['entity'];
 * 		if (!empty($params['data']['new_password'])) {
 * 			$record->new_password = $params['data']['new_password'];
 * 			unset($params['data']['new_password']);
 * 		}
 * 		if (!empty($record->new_password)) {
 * 			$record->password = String::hash($record->new_password);
 * 		}
 * 		$params['entity'] = $record;
 * 		return $chain->next($self, $params, $chain);
 * 	});
 * }}}
 *
 */
class Users extends \lithium\data\Model{}

?>