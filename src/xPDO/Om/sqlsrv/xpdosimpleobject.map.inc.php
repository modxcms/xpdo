<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Metadata map for the xPDOSimpleObject class.
 *
 * Provides an integer primary key column which uses sqlsrv native
 * integer primary key generation facilities.
 *
 * @see xPDOSimpleObject
 * @package xpdo
 * @subpackage om.sqlsrv
 */
$xpdo_meta_map['xPDOSimpleObject'] = array (
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
