<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO;


use ArrayAccess;

class xPDOMap implements ArrayAccess
{
    /**
     * @var array An object/relational map by class.
     */
    private $map;
    /**
     * @var xPDO The xPDO instance that owns this map.
     */
    private $xpdo;

    public function __construct(xPDO &$xpdo)
    {
        $this->map = [];
        $this->xpdo =& $xpdo;
    }

    public function offsetExists($offset): bool
    {
        if (!isset($this->map[$offset])) {
            $this->_checkClass($offset);
        }
        return isset($this->map[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!isset($this->map[$offset])) {
            $this->_checkClass($offset);
        }
        return $this->map[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->map[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->map[$offset]);
    }

    private function _checkClass($class)
    {
        $driverClass = $this->xpdo->getDriverClass($class);
        if ($driverClass !== false && isset($driverClass::$metaMap)) {
            $this->map[$class] = $driverClass::$metaMap;
        }
    }
}
