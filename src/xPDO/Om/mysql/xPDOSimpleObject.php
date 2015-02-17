<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\mysql;

/**
 * A base mysql class defining an auto-incremented integer primary key.
 *
 * @package xPDO\Om\mysql
 */
class xPDOSimpleObject extends \xPDO\Om\xPDOSimpleObject
{
    public static $metaMap = array(
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
}
