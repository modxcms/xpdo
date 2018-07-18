<?php
namespace xPDO\Migrations\Model\mysql;

use xPDO\xPDO;

class XPDOMigrations extends \xPDO\Migrations\Model\XPDOMigrations
{

    public static $metaMap = array (
        'package' => 'xPDO\\Migrations\\Model',
        'version' => '3.0',
        'table' => 'xpdo_migrations',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'version' => NULL,
            'type' => 'master',
            'description' => NULL,
            'status' => 'ready',
            'author' => NULL,
            'created_at' => 'CURRENT_TIMESTAMP',
            'processed_at' => NULL,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
            ),
            'version' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '32',
                'phptype' => 'string',
                'null' => true,
            ),
            'type' => 
            array (
                'dbtype' => 'set',
                'precision' => '\'master\',\'stagging\',\'dev\',\'local\'',
                'phptype' => 'string',
                'null' => false,
                'default' => 'master',
            ),
            'description' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'status' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '16',
                'phptype' => 'string',
                'null' => false,
                'default' => 'ready',
            ),
            'author' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => true,
            ),
            'created_at' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ),
            'processed_at' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => true,
            ),
        ),
    );
}
