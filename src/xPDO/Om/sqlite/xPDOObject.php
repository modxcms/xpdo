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
 * Implements extensions to the base xPDOObject class for SQLite.
 *
 * {@inheritdoc}
 *
 * @package xPDO\Om\sqlite
 */
class xPDOObject extends \xPDO\Om\xPDOObject {
    public static $metaMap = array(
        'table' => null
    );
}
