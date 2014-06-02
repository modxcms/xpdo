<?php
/**
 * Defines sample xPDO classes implemented for MySQL.
 *
 * This is an example file containing three closely related classes representing
 * a simple example object model implemented on xPDO for MySQL.
 *
 * @package sample.mysql
 */

/**
 * Include the required database-independent parent classes.
 */
require_once (dirname(dirname(__FILE__)) . '/person.class.php');

/**
 * Represents a Person.
 * @see person.map.inc.php
 * @package sample.mysql
 */
class Person_mysql extends Person {}

