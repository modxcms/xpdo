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
 * Provides an integer primary key column which uses MySQL's native
 * auto_increment primary key generation facilities.
 *
 * @see xPDOSimpleObject
 * @package xpdo
 * @subpackage om.mysql
 */
$xpdo_meta_map['xPDO\\Om\\xPDOSimpleObject'] = array(
    'table' => null,
    'fields' => array(
        'id' => null,
    ),
    'fieldMeta' => array(
        'id' => array(
            'dbtype' => 'INTEGER',
            'phptype' => 'integer',
            'null' => false,
            'index' => 'pk',
            'generated' => 'native',
            'attributes' => 'unsigned',
        )
    ),
    'indexes' => array(
        'PRIMARY' =>
        array(
            'alias' => 'PRIMARY',
            'primary' => true,
            'unique' => true,
            'type' => 'BTREE',
            'columns' =>
            array(
                'id' =>
                array(
                    'length' => '',
                    'collation' => 'A',
                    'null' => false,
                ),
            ),
        )
    )
);
