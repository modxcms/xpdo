<?php
namespace xPDO\Test\Sample\STI\pgsql;

use xPDO\xPDO;

class derivedClass2 extends \xPDO\Test\Sample\STI\derivedClass2
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample\\STI',
        'version' => '3.0',
        'extends' => 'xPDO\\Test\\Sample\\STI\\derivedClass',
        'fields' => 
        array (
            'class_key' => 'xPDO\\Test\\Sample\\STI\\derivedClass2',
            'field3' => '',
        ),
        'fieldMeta' => 
        array (
            'class_key' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => 'xPDO\\Test\\Sample\\STI\\derivedClass2',
            ),
            'field3' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '32',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
        ),
    );

}
