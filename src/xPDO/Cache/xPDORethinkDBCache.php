<?php
/*
 * This file is part of xPDO.
 * 
 * Copyright (c) Elizabeth Southwell <
 *
 * xPDO is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * xPDO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

namespace xPDO\Cache;

use xPDO\xPDO;

/**
 * Provides a RethinkDB-powered xPDOCache implementation.
 *
 * @package xPDO\Cache
 */

class xPDORethinkDBCache extends xPDOCache {
    protected $conn = null;

    protected $table = null;

    public function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        $host = $this->getOption('rethinkdb_host', $options, '127.0.0.1');
        $port = $this->getOption('rethinkdb_port', $options, '28015');
        $database = $this->getOption('rethinkdb_database', $options, 'test');
        $table = $this->getOption('rethinkdb_table', $options, 'cache');

        $this->conn = r\connect($host, $port, $database);

        $this->table = r\table($table);

        $this->initialized = true;
    }

    public function add($key, $var, $expire= 0, $options= array()) {
        try {
            // TODO: Although I do store an expires date, I have not added the simple check below to actually ensure cache expiration. 
            $this->table->insert(array(
                'id' => $this->getCacheKey($key),
                'content' => json_encode($var),
                'expires' => time() + $expire
            ), array('conflict' => "replace"))->run($this->conn);
            return true;
        } catch (Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDORethinkDB[{$this->key}]: Error adding cache item with key {$key}. {$e->getMessage()}");
        }
        return false;
    }

    public function set($key, $var, $expire= 0, $options= array()) {
        return $this->add($key, $var, $expire, $options);
    }

    public function replace($key, $var, $expire= 0, $options= array()) {
        return $this->add($key, $var, $expire, $options);
    }

    public function delete($key, $options= array()) {
        if (!isset($options['multiple_object_delete']) || empty($options['multiple_object_delete'])) {
            try {
                $this->table->get($this->getCacheKey($key))->delete()->run($this->conn);
                return true;
            } catch (Exception $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDORethinkDB[{$this->key}]: Error deleting cache item with key {$key}. {$e->getMessage()}");
            }
        } else {
            try {
                // TODO: Clear the cache with a wildcard correctly, instead of just flushing the entire thing.
                $this->flush($options);
                return true;
            } catch (Exception $e) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDORethinkDB[{$this->key}]: Error flushing cache due to delete request for key {$key}. {$e->getMessage()}");
            }
        }
        return false;
    }
    public function get($key, $options= array()) {
        try {
            return json_decode($this->table->get($this->getCacheKey($key))->run($this->conn)['content'], true);
        } catch (Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_WARN, "xPDORethinkDB[{$this->key}]: Cache item with key {$key} does not exist. {$e->getMessage()}");
        }
        return null;
    }
    public function flush($options= array()) {
        try {
            // TODO: Flush the specific partition instead of everything.
            $this->table->delete()->run($this->conn);
            return true;
        } catch (Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDORethinkDB[{$this->key}]: Error flushing cache partition. {$e->getMessage()}");
        }
        return false;
    }
}