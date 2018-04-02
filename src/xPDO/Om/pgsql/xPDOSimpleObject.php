<?php

namespace xPDO\Om\pgsql;

/**
 * A base pgsql class defining an auto-incremented integer primary key.
 *
 * @package xPDO\Om\pgsql
 */
class xPDOSimpleObject extends \xPDO\Om\xPDOSimpleObject {
    public static $metaMap = array(
        'table' => null,
        'fields' => array(
            'id' => null,
        ),
        'fieldMeta' => array(
            'id' => array(
                'dbtype' => 'SERIAL',
                'phptype' => 'integer',
                'null' => false,
                'index' => 'pk',
                'generated' => 'native'
            )
        ),
        'indexes' => array(
            'PRIMARY' => array(
                'columns' => array(
                    'id' => array(),
                ),
                'primary' => true,
                'unique' => true
            )
        )
    );
}
