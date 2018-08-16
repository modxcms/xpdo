<?php
namespace xPDO\Test\Sample\pgsql;

use xPDO\xPDO;

class SecureItem extends \xPDO\Test\Sample\SecureItem
{

    public static $metaMap = array (
        'package' => 'xPDO\\Test\\Sample',
        'version' => '3.0',
        'table' => 'secure_items',
        'extends' => 'xPDO\\Test\\Sample\\SecureObject',
        'fields' => 
        array (
            'name' => NULL,
            'public' => 1,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
            ),
            'public' => 
            array (
                'dbtype' => 'smallint',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 1,
            ),
        ),
    );
}
