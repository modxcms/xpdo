<?php
/**
 * Include the required database-independent parent class.
 */
require_once (dirname(dirname(__FILE__)) . '/person.class.php');
/**
 * Represents a Person.
 * @see person.map.inc.php
 * @package sample.pgsql
 */
class Person_pgsql extends Person {}
