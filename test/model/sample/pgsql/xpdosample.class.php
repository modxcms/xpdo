<?php
require_once (dirname(dirname(__FILE__)) . '/xpdosample.class.php');
class xPDOSample_pgsql extends xPDOSample {
    public static function callTest() {
        return 'xPDOSample';
    }
}