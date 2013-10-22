<?php
/**
 * Include the required database-independent parent class.
 */
require_once (dirname(dirname(__FILE__)) . '/phone.class.php');

/**
 * Represents a Phone number.
 * @see phone.map.inc.php
 * @package sample.mysql
 */
class Phone_mysql extends Phone {}
