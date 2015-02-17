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
 * Provides a memcache-powered xPDOCache implementation.
 *
 * This requires the memcache extension for PHP.
 *
 * @package xPDO\Cache
 */
class xPDOMemCache extends xPDOCache {
    protected $memcache = null;

    public function __construct(& $xpdo, $options = array()) {
        parent :: __construct($xpdo, $options);
        if (class_exists('Memcache', true)) {
            $this->memcache= new Memcache();
            if ($this->memcache) {
                $servers = explode(',', $this->getOption($this->key . '_memcached_server', $options, $this->getOption('memcached_server', $options, 'localhost:11211')));
                foreach ($servers as $server) {
                    $server = explode(':', $server);
                    $this->memcache->addServer($server[0], (integer) $server[1]);
                }
                $compressThreshold = $this->getOption($this->key . '_memcached_compress_threshold', $options, $this->getOption('memcached_compress_threshold', array(), '20000:0.2'));
                if (!empty($compressThreshold)) {
                    $threshold = explode(':', $compressThreshold);
                    if (count($threshold) == 2) {
                        $minValue = (integer) $threshold[0];
                        $minSaving = (float) $threshold[1];
                        if ($minSaving >= 0 && $minSaving <= 1) {
                            $this->memcache->setCompressThreshold($minValue, $minSaving);
                        }
                    }
                }
                $this->initialized = true;
            } else {
                $this->memcache = null;
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDOMemCache[{$this->key}]: Error creating memcache provider for server(s): " . $this->getOption($this->key . '_memcached_server', $options, $this->getOption('memcached_server', $options, 'localhost:11211')));
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "xPDOMemCache[{$this->key}]: Error creating memcache provider; xPDOMemCache requires the PHP memcache extension.");
        }
    }

    public function add($key, $var, $expire= 0, $options= array()) {
        $added= $this->memcache->add(
            $this->getCacheKey($key),
            $var,
            $this->getOption($this->key . xPDO::OPT_CACHE_COMPRESS, $options, $this->getOption(xPDO::OPT_CACHE_COMPRESS, $options, false)),
            $expire
        );
        return $added;
    }

    public function set($key, $var, $expire= 0, $options= array()) {
        $set= $this->memcache->set(
            $this->getCacheKey($key),
            $var,
            $this->getOption($this->key . xPDO::OPT_CACHE_COMPRESS, $options, $this->getOption(xPDO::OPT_CACHE_COMPRESS, $options, false)),
            $expire
        );
        return $set;
    }

    public function replace($key, $var, $expire= 0, $options= array()) {
        $replaced= $this->memcache->replace(
            $this->getCacheKey($key),
            $var,
            $this->getOption($this->key . xPDO::OPT_CACHE_COMPRESS, $options, $this->getOption(xPDO::OPT_CACHE_COMPRESS, $options, false)),
            $expire
        );
        return $replaced;
    }

    public function delete($key, $options= array()) {
        if (!isset($options['multiple_object_delete']) || empty($options['multiple_object_delete'])) {
            $deleted= $this->memcache->delete($this->getCacheKey($key));
        } else {
            $deleted= $this->flush($options);
        }
        return $deleted;
    }

    public function get($key, $options= array()) {
        $value= $this->memcache->get($this->getCacheKey($key));
        return $value;
    }

    public function flush($options= array()) {
        return $this->memcache->flush();
    }
}
