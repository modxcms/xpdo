<?php
namespace xPDO\Test\Sample\mysql;

use xPDO\xPDO;
class Item extends \xPDO\Test\Sample\Item
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'items',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => '',
            'color' => 'green',
            'description' => NULL,
            'date_modified' => 'CURRENT_TIMESTAMP',
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
                'index' => 'fk',
            ),
            'color' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => 'green',
                'index' => 'fk',
            ),
            'description' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'date_modified' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'attributes' => 'ON UPDATE CURRENT_TIMESTAMP',
            ),
        ),
    );
}
