<?php
namespace xPDO\Test\Sample\pgsql;

use xPDO\xPDO;

class xPDOSample extends \xPDO\Test\Sample\xPDOSample
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'xpdosample',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'parent' => 0,
            'unique_varchar' => NULL,
            'varchar' => NULL,
            'text' => NULL,
            'timestamp' => 'CURRENT_TIMESTAMP',
            'unix_timestamp' => 0,
            'date_time' => NULL,
            'date' => NULL,
            'enum' => NULL,
            'password' => NULL,
            'integer' => NULL,
            'float' => 1.0123,
            'boolean' => NULL,
        ),
        'fieldMeta' => 
        array (
            'parent' => 
            array (
                'dbtype' => 'integer',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'unique_varchar' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'index' => 'unique',
            ),
            'varchar' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
            ),
            'text' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'timestamp' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'attributes' => 'ON UPDATE CURRENT_TIMESTAMP',
            ),
            'unix_timestamp' => 
            array (
                'dbtype' => 'integer',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'date_time' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'date' => 
            array (
                'dbtype' => 'date',
                'phptype' => 'date',
                'null' => true,
            ),
            'enum' => 
            array (
                'dbtype' => 'varchar',
                'attributes' => 'CHECK ("enum" IN(\'\',\'T\',\'F\'))',
                'phptype' => 'string',
                'null' => false,
            ),
            'password' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
            ),
            'integer' => 
            array (
                'dbtype' => 'integer',
                'phptype' => 'integer',
                'null' => false,
            ),
            'float' => 
            array (
                'dbtype' => 'decimal',
                'precision' => '10,5',
                'phptype' => 'float',
                'null' => false,
                'default' => 1.0123,
            ),
            'boolean' => 
            array (
                'dbtype' => 'smallint',
                'phptype' => 'boolean',
                'null' => false,
            ),
        ),
        'indexes' => 
        array (
            'unique_varchar' => 
            array (
                'alias' => 'unique_varchar',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'unique_varchar' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );
}
