<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use xPDO\xPDO;

$properties['xpdo_test_path'] = dirname(__FILE__) . '/';

/* mysql */
$properties['mysql_string_dsn_test']= 'mysql:host=localhost;dbname=xpdotest;charset=utf8';
$properties['mysql_string_dsn_nodb']= 'mysql:host=localhost;charset=utf8';
$properties['mysql_string_dsn_error']= 'mysql:host= nonesuchhost;dbname=nonesuchdb';
$properties['mysql_string_username']= '';
$properties['mysql_string_password']= '';
$properties['mysql_array_driverOptions']= array();
$properties['mysql_array_options']= array(
    xPDO::OPT_CACHE_PATH => $properties['xpdo_test_path'] .'cache/',
    xPDO::OPT_HYDRATE_FIELDS => true,
    xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    xPDO::OPT_CONN_INIT => array(xPDO::OPT_CONN_MUTABLE => true),
    xPDO::OPT_CONNECTIONS => array(
        array(
            'dsn' => $properties['mysql_string_dsn_test'],
            'username' => $properties['mysql_string_username'],
            'password' => $properties['mysql_string_password'],
            'options' => array(
                xPDO::OPT_CONN_MUTABLE => true,
            ),
            'driverOptions' => $properties['mysql_array_driverOptions'],
        ),
    ),
);

/* sqlite */
$properties['sqlite_string_dsn_test']= 'sqlite:' . $properties['xpdo_test_path'] . 'db/xpdotest';
$properties['sqlite_string_dsn_nodb']= 'sqlite::memory:';
$properties['sqlite_string_dsn_error']= 'sqlite:db/';
$properties['sqlite_string_username']= '';
$properties['sqlite_string_password']= '';
$properties['sqlite_array_driverOptions']= array();
$properties['sqlite_array_options']= array(
    xPDO::OPT_CACHE_PATH => $properties['xpdo_test_path'] . 'cache/',
    xPDO::OPT_HYDRATE_FIELDS => true,
    xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    xPDO::OPT_CONN_INIT => array(xPDO::OPT_CONN_MUTABLE => true),
    xPDO::OPT_CONNECTIONS => array(
        array(
            'dsn' => $properties['sqlite_string_dsn_test'],
            'username' => $properties['sqlite_string_username'],
            'password' => $properties['sqlite_string_password'],
            'options' => array(
                xPDO::OPT_CONN_MUTABLE => true,
            ),
            'driverOptions' => $properties['sqlite_array_driverOptions'],
        ),
    ),
);

/* sqlsrv */
$properties['sqlsrv_string_dsn_test']= 'sqlsrv:server=(local);database=xpdotest';
$properties['sqlsrv_string_dsn_nodb']= 'sqlsrv:server=(local)';
$properties['sqlsrv_string_dsn_error']= 'sqlsrv:server=xyz;123';
$properties['sqlsrv_string_username']= '';
$properties['sqlsrv_string_password']= '';
$properties['sqlsrv_array_driverOptions']= array(/*PDO::SQLSRV_ATTR_DIRECT_QUERY => false*/);
$properties['sqlsrv_array_options']= array(
    xPDO::OPT_CACHE_PATH => $properties['xpdo_test_path'] . 'cache/',
    xPDO::OPT_HYDRATE_FIELDS => true,
    xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    xPDO::OPT_CONNECTIONS => array(
        array(
            'dsn' => $properties['sqlsrv_string_dsn_test'],
            'username' => $properties['sqlsrv_string_username'],
            'password' => $properties['sqlsrv_string_password'],
            'options' => array(
                xPDO::OPT_CONN_MUTABLE => true,
            ),
            'driverOptions' => $properties['sqlsrv_array_driverOptions'],
        ),
    ),
);

/* pgsql */
$properties['pgsql_string_dsn_test']= 'pgsql:host=localhost;port=5432;dbname=xpdotest';
$properties['pgsql_string_dsn_nodb']= 'pgsql:host=localhost';
$properties['pgsql_string_dsn_error']= 'pgsql:host=localhos;123';
$properties['pgsql_string_username']= '';
$properties['pgsql_string_password']= '';
$properties['pgsql_array_driverOptions']= array();
$properties['pgsql_array_options']= array(
    xPDO::OPT_CACHE_PATH => $properties['xpdo_test_path'] . 'cache/',
    xPDO::OPT_HYDRATE_FIELDS => true,
    xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    xPDO::OPT_CONNECTIONS => array(
        array(
            'dsn' => $properties['pgsql_string_dsn_test'],
            'username' => $properties['pgsql_string_username'],
            'password' => $properties['pgsql_string_password'],
            'options' => array(
                xPDO::OPT_CONN_MUTABLE => true,
            ),
            'driverOptions' => $properties['pgsql_array_driverOptions'],
        ),
    ),
);


/* PHPUnit test config */
$properties['xpdo_driver']= getenv('TEST_DRIVER');
$properties['logLevel']= xPDO::LOG_LEVEL_INFO;
$properties['logTarget']= php_sapi_name() === 'cli' ? 'ECHO' : 'HTML';
//$properties['debug']= -1;

return $properties;
