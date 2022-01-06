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
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use xPDO\Exception\Container\ContainerException;
use xPDO\Exception\Container\NotFoundException;

/**
 * Implements a minimal container for service location.
 *
 * @package xPDO
 */
class xPDOContainer implements ContainerInterface, ArrayAccess
{
    private $entries = array();

    /**
     * Add an entry to the container with the specified identifier.
     *
     * @param string $id The identifier for the entry.
     * @param mixed  $entry The entry to add.
     */
    public function add(string $id, $entry)
    {
        $this->offsetSet($id, $entry);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id)
    {
        if ($this->has($id)) {
            try {
                return $this->offsetGet($id);
            } catch (Exception $e) {
                throw new ContainerException($e->getMessage(), $e->getCode(), $e);
            }
        }
        throw new NotFoundException("Dependency not found with key {$id}.");
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->offsetExists($id);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->entries);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->entries[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->entries[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->entries[$offset]);
    }
}
