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
 * Provides a wincache-powered xPDOCache implementation.
 *
 * This requires the wincache extension for PHP, version 1.1.0 or later. Earlier versions
 * did not have user cache methods.
 *
 * @package xPDO\Cache
 */
class xPDOWinCache extends xPDOCache {
    public function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        if (function_exists('wincache_ucache_info')) {
            $this->initialized = true;
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDOWinCache[{$this->key}]: Error creating wincache provider; xPDOWinCache requires the PHP wincache extension, version 1.1.0 or later.");
        }
    }

    public function add($key, $var, $expire= 0, $options= array()) {
        $added= wincache_ucache_add(
            $this->getCacheKey($key),
            $var,
            $expire
        );
        return $added;
    }

    public function set($key, $var, $expire= 0, $options= array()) {
        $set= wincache_ucache_set(
            $this->getCacheKey($key),
            $var,
            $expire
        );
        return $set;
    }

    public function replace($key, $var, $expire= 0, $options= array()) {
        $replaced = false;
        if (wincache_ucache_exists($key)) {
            $replaced= wincache_ucache_set(
                $this->getCacheKey($key),
                $var,
                $expire
            );
        }
        return $replaced;
    }

    public function delete($key, $options= array()) {
        $deleted = false;
        if ($this->getOption(xPDO::OPT_CACHE_MULTIPLE_OBJECT_DELETE, $options, false)) {
            $deleted= $this->flush($options);
        } else {
            $deleted= wincache_ucache_delete($this->getCacheKey($key));
        }

        return $deleted;
    }

    public function get($key, $options= array()) {
        $value= wincache_ucache_get($this->getCacheKey($key));
        return $value;
    }

    public function flush($options= array()) {
        return wincache_ucache_clear();
    }
}
