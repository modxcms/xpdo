<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Cache;

use xPDO\xPDO;

/**
 * Provides an APC-powered xPDOCache implementation.
 *
 * This requires the APC extension for PHP, version 3.1.4 or later. Earlier versions
 * did not have all the necessary user cache methods.
 *
 * @package xPDO\Cache
 */
class xPDOAPCCache extends xPDOCache {
    public function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        if (function_exists('apc_exists')) {
            $this->initialized = true;
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDOAPCCache[{$this->key}]: Error creating APC cache provider; xPDOAPCCache requires the APC extension for PHP, version 2.0.0 or later.");
        }
    }

    public function add($key, $var, $expire= 0, $options= array()) {
        $added= apc_add(
            $this->getCacheKey($key),
            $var,
            $expire
        );
        return $added;
    }

    public function set($key, $var, $expire= 0, $options= array()) {
        $set= apc_store(
            $this->getCacheKey($key),
            $var,
            $expire
        );
        return $set;
    }

    public function replace($key, $var, $expire= 0, $options= array()) {
        $replaced = false;
        if (apc_exists($key)) {
            $replaced= apc_store(
                $this->getCacheKey($key),
                $var,
                $expire
            );
        }
        return $replaced;
    }

    public function delete($key, $options= array()) {
        $deleted = false;
        if (!isset($options['multiple_object_delete']) || empty($options['multiple_object_delete'])) {
            $deleted= apc_delete($this->getCacheKey($key));
        } elseif (class_exists('APCIterator', true)) {
            $iterator = new APCIterator('user', '/^' . str_replace('/', '\/', $this->getCacheKey($key)) . '/', APC_ITER_KEY);
            if ($iterator) {
                $deleted = apc_delete($iterator);
            }
        }
        return $deleted;
    }

    public function get($key, $options= array()) {
        $value= apc_fetch($this->getCacheKey($key));
        return $value;
    }

    public function flush($options= array()) {
        $flushed = false;
        if (class_exists('APCIterator', true) && $this->getOption('flush_by_key', $options, true) && !empty($this->key)) {
            $iterator = new APCIterator('user', '/^' . str_replace('/', '\/', $this->key) . '\//', APC_ITER_KEY);
            if ($iterator) {
                $flushed = apc_delete($iterator);
            }
        } else {
            $flushed = apc_clear_cache('user');
        }
        return $flushed;
    }
}
