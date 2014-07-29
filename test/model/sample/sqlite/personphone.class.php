<?php
/**
 * Include the required database-independent parent classes.
 */
require_once (dirname(dirname(__FILE__)) . '/personphone.class.php');

/**
 * Represents a one to many relationship between a Person and a Phone.
 * @see personphone.map.inc.php
 * @package sample.sqlite
 */
class PersonPhone_sqlite extends PersonPhone {}
