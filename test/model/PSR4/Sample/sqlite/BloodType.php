<?php
namespace xPDO\Test\Sample\sqlite;

use xPDO\xPDO;

class BloodType extends \xPDO\Test\Sample\BloodType
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'blood_types',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'fields' => 
        array (
            'type' => NULL,
            'description' => NULL,
        ),
        'fieldMeta' => 
        array (
            'type' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
            ),
            'description' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
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
                    'type' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'Person' => 
            array (
                'class' => 'xPDO\\Test\\Sample\\Person',
                'local' => 'type',
                'foreign' => 'blood_type',
                'cardinality' => 'many',
                'owner' => 'foreign',
            ),
        ),
    );

}
