<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

//default schema, this is a straight var export from Model::schema()

$schema = array (
  'id' => 
  array (
    'type' => 'integer',
    'length' => 10,
    'null' => false,
    'default' => NULL,
  ),
  'first_name' => 
  array (
    'type' => 'string',
    'length' => 128,
    'null' => false,
    'default' => NULL,
  ),
  'last_name' => 
  array (
    'type' => 'string',
    'length' => 128,
    'null' => false,
    'default' => NULL,
  ),
  'token' => 
  array (
    'type' => 'string',
    'length' => 36,
    'null' => false,
    'default' => NULL,
  ),
  'active' => 
  array (
    'type' => 'boolean',
    'null' => false,
    'default' => '0',
  ),
  'username' => 
  array (
    'type' => 'string',
    'length' => 24,
    'null' => false,
    'default' => NULL,
  ),
  'password' => 
  array (
    'type' => 'string',
    'length' => 48,
    'null' => false,
    'default' => NULL,
  ),
  'email' => 
  array (
    'type' => 'string',
    'length' => 128,
    'null' => false,
    'default' => NULL,
  ),
)