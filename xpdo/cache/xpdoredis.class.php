<?php
/*
 * Copyright 2010-2014 by MODX, LLC.
 *
 * This file is part of xPDO.
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

/**
 * Provides a redis-powered xPDOCache implementation.
 *
 * This requires PHP 5.3.0 or above
 *
 * @package xpdo
 * @subpackage cache
 */

class xPDORedis extends xPDOCache {
    protected $redis = null;

    public function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        require_once __DIR__ . '/predis/lib/Predis/Autoloader.php';
        Predis\Autoloader::register();

        if (class_exists('\\Predis\\Client', true)) {
            $server = $this->getOption($this->key . '_redis_server', $options, $this->getOption('redis_server', $options, '127.0.0.1:6379'));

            if (substr($server,0,7) !== 'unix://' && substr($server,0,7) !== 'http://') {
                $server = 'tcp://' . $server;
            }

            $this->redis = new Predis\Client($server);
            $this->initialized = true;
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDORedis[{$this->key}]: Error creating redis provider.");
        }
    }

    public function add($key, $var, $expire= 0, $options= array()) {
        $added = $this->redis->set(
            $this->getCacheKey($key),
            serialize($var)
        );
        if($expire > 0) $expire = $this->redis->expire($this->getCacheKey($key), $expire);
        return $added;
    }

    public function set($key, $var, $expire= 0, $options= array()) {
        $set = $this->redis->set(
            $this->getCacheKey($key),
            serialize($var)
        );
        if($expire > 0) $expire = $this->redis->expire($this->getCacheKey($key), $expire);
        return $set;
    }

    public function replace($key, $var, $expire= 0, $options= array()) {
        $replaced = $this->redis->set(
            $this->getCacheKey($key),
            serialize($var)
        );
        if($expire > 0) $expire = $this->redis->expire($this->getCacheKey($key), $expire);
        return $replaced;
    }

    public function delete($key, $options= array()) {
        if (!isset($options['multiple_object_delete']) || empty($options['multiple_object_delete'])) {
            $deleted = $this->redis->delete($this->getCacheKey($key));
        } else {
            $deleted = $this->redis->flushdb();
        }
        return $deleted;
    }

    public function get($key, $options= array()) {
        return unserialize($this->redis->get($this->getCacheKey($key)));
    }

    public function flush($options= array()) {
        return $this->redis->flushdb();
    }
}