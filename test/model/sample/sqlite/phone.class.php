<?php
/**
 * Include the required database-independent parent classes.
 */
require_once (dirname(dirname(__FILE__)) . '/phone.class.php');

/**
 * Represents a Phone number.
 * @see phone.map.inc.php
 * @package sample.sqlite
 */
class Phone_sqlite extends Phone {}
