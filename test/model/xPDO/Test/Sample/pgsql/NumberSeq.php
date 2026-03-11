<?php
namespace xPDO\Test\Sample\pgsql;

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
            ),
            'number' =>
            array (
                'dbtype' => 'integer',
                'phptype' => 'integer',
                'null' => false,
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
                    'level' =>
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'number' =>
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
