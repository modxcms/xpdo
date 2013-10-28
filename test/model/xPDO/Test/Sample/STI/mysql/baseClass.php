<?php
namespace xPDO\Test\Sample\STI\mysql;

use xPDO\xPDO;

class baseClass extends \xPDO\Test\Sample\STI\baseClass
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample\\STI',
        'version' => '3.0',
        'table' => 'sti_objects',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'inherit' => 'single',
        'fields' => 
        array (
            'field1' => 0,
            'field2' => '',
            'date_modified' => 'CURRENT_TIMESTAMP',
            'fkey' => NULL,
            'class_key' => 'baseClass',
        ),
        'fieldMeta' => 
        array (
            'field1' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '2',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'field2' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'date_modified' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'attributes' => 'ON UPDATE CURRENT_TIMESTAMP',
            ),
            'fkey' => 
            array (
                'dbtype' => 'int',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => true,
                'index' => 'fk',
            ),
            'class_key' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => 'baseClass',
            ),
        ),
        'indexes' => 
        array (
            'fkey' => 
            array (
                'alias' => 'fkey',
                'primary' => false,
                'unique' => false,
                'columns' => 
                array (
                    'fkey' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'composites' => 
        array (
            'relMany' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\STI\\relClassMany',
                'local' => 'id',
                'foreign' => 'fkey',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'relOne' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\STI\\relClassOne',
                'local' => 'fkey',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}
