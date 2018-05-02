<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Sample;


use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOSimpleObject;

class SecureObject extends xPDOSimpleObject
{
    public static function _loadInstance(& $xpdo, $className, $criteria, $row)
    {
        $instance = xPDOObject::_loadInstance($xpdo, $className, $criteria, $row);
        if ($instance instanceof SecureObject && !$instance->get('public')) {
            $instance = null;
        }

        return $instance;
    }
}
