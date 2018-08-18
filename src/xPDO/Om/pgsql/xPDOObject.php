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


use xPDO\Om\xPDOCriteria;

class xPDOObject extends \xPDO\Om\xPDOObject
{
    public static $metaMap = array(
        'table' => null,
    );

    protected function getGeneratedKey()
    {
        $column = $this->xpdo->getPK($this->_class);
        $tableName = $this->xpdo->literal($this->xpdo->getTableName($this->_class));
        $query = new xPDOCriteria($this->xpdo, "SELECT currval('{$tableName}_{$column}_seq'::regclass)");
        $sequence = (int)$this->xpdo->getValue($query->prepare());
        if (!$sequence) {
            return false;
        }

        return $sequence;
    }
}
