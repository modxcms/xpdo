<?php
namespace xPDO\Test\Sample\STI\pgsql;

use xPDO\xPDO;

class relClassMany extends \xPDO\Test\Sample\STI\relClassMany
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample\\STI',
        'version' => '3.0',
        'table' => 'sti_related_many',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'field1' => NULL,
            'field2' => NULL,
            'date_modified' => 'CURRENT_TIMESTAMP',
            'fkey' => NULL,
        ),
        'fieldMeta' => 
        array (
            'field1' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '200',
                'phptype' => 'string',
                'null' => false,
            ),
            'field2' => 
            array (
                'dbtype' => 'smallint',
                'phptype' => 'boolean',
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
            'fkey' => 
            array (
                'dbtype' => 'integer',
                'phptype' => 'integer',
                'null' => true,
                'index' => 'fk',
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
        'aggregates' => 
        array (
            'relParent' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\STI\\baseClass',
                'local' => 'fkey',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}
