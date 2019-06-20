<?php
namespace xPDO\Test\Sample\mysql;

use xPDO\xPDO;

class Phone extends \xPDO\Test\Sample\Phone
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'phone',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'type' => '',
            'number' => NULL,
            'date_modified' => 'CURRENT_TIMESTAMP',
        ),
        'fieldMeta' => 
        array (
            'type' => 
            array (
                'dbtype' => 'enum',
                'precision' => '\'\',\'home\',\'work\',\'mobile\'',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'number' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '20',
                'phptype' => 'string',
                'null' => false,
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
        'composites' => 
        array (
            'PersonPhone' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\PersonPhone',
                'local' => 'id',
                'foreign' => 'phone',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );

}
