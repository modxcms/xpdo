<?php
namespace xPDO\Test\Sample\sqlite;

use xPDO\xPDO;

class PersonPhone extends \xPDO\Test\Sample\PersonPhone
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'person_phone',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'fields' => 
        array (
            'person' => NULL,
            'phone' => NULL,
            'is_primary' => 0,
        ),
        'fieldMeta' => 
        array (
            'person' => 
            array (
                'dbtype' => 'int',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => false,
            ),
            'phone' => 
            array (
                'dbtype' => 'int',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => false,
            ),
            'is_primary' => 
            array (
                'dbtype' => 'int',
                'precision' => '1',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'PRIMARY' => 
            array (
                'alias' => 'PRIMARY',
                'primary' => true,
                'unique' => true,
                'columns' => 
                array (
                    'person' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'phone' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'composites' => 
        array (
            'Phone' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\Phone',
                'local' => 'phone',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
        'aggregates' => 
        array (
            'Person' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\Person',
                'local' => 'person',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
