<?php

namespace xPDO\Test\Sample\STI\pgsql;

class derivedClass extends \xPDO\Test\Sample\STI\derivedClass
{
    public static $metaMap = array(
        'package' => 'xPDO\\Test\\Sample\\STI',
        'version' => '3.0',
        'extends' => 'xPDO\\Test\\Sample\\STI\\baseClass',
        'fields' => array(
            'class_key' => 'xPDO\\Test\\Sample\\STI\\derivedClass',
        ),
        'fieldMeta' => array(
            'class_key' => array(
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => 'xPDO\\Test\\Sample\\STI\\derivedClass',
            ),
        ),
    );
}
