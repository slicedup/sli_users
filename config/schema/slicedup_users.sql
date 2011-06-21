/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

--This is a basic Mysql dump. Alter as necessary.

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(128) NOT NULL,
  `last_name` varchar(128) NOT NULL,
  `token` varchar(36) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `username` varchar(24) NOT NULL,
  `password` varchar(48) NOT NULL,
  `email` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
);