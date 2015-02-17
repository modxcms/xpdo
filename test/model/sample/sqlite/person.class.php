<?php
/**
 * Include the required database-independent parent classes.
 */
require_once (dirname(dirname(__FILE__)) . '/person.class.php');

/**
 * Represents a Person.
 * @see person.map.inc.php
 * @package sample.sqlite
 */
class Person_sqlite extends Person {}
