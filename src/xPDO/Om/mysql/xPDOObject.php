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
 * Implements extensions to the base xPDOObject class for MySQL.
 *
 * {@inheritdoc}
 *
 * @package xPDO\Om\mysql
 */
class xPDOObject extends \xPDO\Om\xPDOObject
{
    public static $metaMap = array(
        'table' => null,
        'tableMeta' => array(
            'engine' => 'InnoDB'
        )
    );
}
