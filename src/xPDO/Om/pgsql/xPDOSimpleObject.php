<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\pgsql;


class xPDOSimpleObject extends \xPDO\Om\xPDOSimpleObject
{
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
