<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\sqlite;

/**
 * A base sqlite class defining an auto-generated integer primary key.
 *
 * @package xPDO\Om\sqlite
 */
class xPDOSimpleObject extends \xPDO\Om\xPDOSimpleObject
{
    public static $metaMap = array(
        'table' => null,
        'fields' => array (
            'id' => null,
        ),
        'fieldMeta' => array (
            'id' => array(
                'dbtype' => 'INTEGER',
                'phptype' => 'integer',
                'null' => false,
                'index' => 'pk',
                'generated' => 'native',
            )
        ),
        'indexes' => array (
            'PRIMARY' => array (
                'columns' => array(
                    'id' => array()
                ),
                'primary' => true,
                'unique' => true
            )
        )
    );
}
