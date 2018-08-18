<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$tstart= microtime(true);

require __DIR__ . '/../../bootstrap.php';

use xPDO\xPDO;

$properties = include __DIR__ . '/../../properties.inc.php';

$dbtypes = array('mysql','pgsql', 'sqlite'/*, 'sqlsrv'*/);

foreach ($dbtypes as $dbtype) {
    $xpdo= new xPDO($properties["{$dbtype}_string_dsn_test"], $properties["{$dbtype}_string_username"], $properties["{$dbtype}_string_password"], $properties["{$dbtype}_array_options"], $properties["{$dbtype}_array_driverOptions"]);
    $xpdo->setPackage('Sample', $properties['xpdo_test_path'] . 'model/PSR4/');
//    $xpdo->setPackage('xPDO\Test\Sample', $properties['xpdo_test_path'] . 'model/');
//    $xpdo->setPackage('sample', $properties['xpdo_test_path'] . 'model/');
    $xpdo->setLogTarget($properties['logTarget']);
    $xpdo->setLogLevel($properties['logLevel']);
//    $xpdo->setDebug(true);

    $xpdo->getManager();
    $xpdo->manager->getGenerator();

    //Use this to create a schema from an existing database
    #$xml= $xpdo->manager->generator->writeSchema(XPDO_CORE_PATH . '../model/schema/sample.' . $dbtype . '.schema.xml', 'sample', 'xPDOObject', '');

    //Use this to generate classes and maps from a schema
    // NOTE: by default, only maps are overwritten; delete class files if you want to regenerate classes
    $xpdo->manager->generator->parseSchema($properties['xpdo_test_path'] . 'model/schema/xPDO.Test.Sample.' . $dbtype . '.schema.xml', $properties['xpdo_test_path'] . 'model/PSR4/', ['namespacePrefix' => 'xPDO\\Test\\', 'update' => 1]);
//    $xpdo->manager->generator->parseSchema($properties['xpdo_test_path'] . 'model/schema/xPDO.Test.Sample.' . $dbtype . '.schema.xml', $properties['xpdo_test_path'] . 'model/', ['update' => 1]);
//    $xpdo->manager->generator->parseSchema($properties['xpdo_test_path'] . 'model/schema/sample.' . $dbtype . '.schema.xml', $properties['xpdo_test_path'] . 'model/');

    $xpdo->manager->generator->parseSchema($properties['xpdo_test_path'] . 'model/schema/xPDO.Test.Sample.STI.' . $dbtype . '.schema.xml', $properties['xpdo_test_path'] . 'model/PSR4/', ['namespacePrefix' => 'xPDO\\Test\\', 'update' => 1]);
//    $xpdo->manager->generator->parseSchema($properties['xpdo_test_path'] . 'model/schema/xPDO.Test.Sample.STI.' . $dbtype . '.schema.xml', $properties['xpdo_test_path'] . 'model/', ['update' => 1]);
//    $xpdo->manager->generator->parseSchema($properties['xpdo_test_path'] . 'model/schema/sample.sti.' . $dbtype . '.schema.xml', $properties['xpdo_test_path'] . 'model/');

    unset($xpdo);
}

$tend= microtime(true);
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

echo "\nExecution time: {$totalTime}\n";

exit();
