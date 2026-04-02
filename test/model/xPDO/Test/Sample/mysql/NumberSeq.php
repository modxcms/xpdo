<?php
namespace xPDO\Test\Sample\mysql;

use xPDO\xPDO;

class NumberSeq extends \xPDO\Test\Sample\NumberSeq
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'number_seq',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'fields' => 
        array (
            'level' => NULL,
            'number' => NULL,
        ),
        'fieldMeta' => 
        array (
            'level' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '1',
                'phptype' => 'string',
                'null' => false,
                'index' => 'pk',
            ),
            'number' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'index' => 'pk',
                'generated' => 'native',
            ),
        ),
        'indexes' => 
        array (
            'PRIMARY' => 
            array (
                'alias' => 'PRIMARY',
                'primary' => true,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'number' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'level' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
