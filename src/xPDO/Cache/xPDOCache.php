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
 * An abstract class that defines the methods a cache provider must implement.
 *
 * @package xPDO\Cache
 */
abstract class xPDOCache {
    /** @var xPDO */
    public $xpdo= null;
    protected $options= array();
    protected $key= '';
    protected $initialized= false;

    public function __construct(& $xpdo, $options = array()) {
        $this->xpdo= & $xpdo;
        $this->options= $options;
        $this->key = $this->getOption(xPDO::OPT_CACHE_KEY, $options, 'default');
    }

    /**
     * Indicates if this xPDOCache instance has been properly initialized.
     *
     * @return boolean true if the implementation was initialized successfully.
     */
    public function isInitialized() {
        return (boolean) $this->initialized;
    }

    /**
     * Get an option from supplied options, the cache options, or the xpdo config.
     *
     * @param string $key Unique identifier for the option.
     * @param array $options A set of explicit options to override those from xPDO or the xPDOCache
     * implementation.
     * @param mixed $default An optional default value to return if no value is found.
     * @return mixed The value of the option.
     */
    public function getOption($key, $options = array(), $default = null) {
        $option = $default;
        if (is_array($key)) {
            if (!is_array($option)) {
                $default= $option;
                $option= array();
            }
            foreach ($key as $k) {
                $option[$k]= $this->getOption($k, $options, $default);
            }
        } elseif (is_string($key) && !empty($key)) {
            if (is_array($options) && !empty($options) && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (is_array($this->options) && !empty($this->options) && array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } else {
                $option = $this->xpdo->cacheManager->getOption($key, null, $default);
            }
        }
        return $option;
    }

    /**
     * Get the actual cache key the implementation will use.
     *
     * @param string $key The identifier the application uses.
     * @param array $options Additional options for the operation.
     * @return string The identifier with any implementation specific prefixes or other
     * transformations applied.
     */
    public function getCacheKey($key, $options = array()) {
        $prefix = $this->getOption('cache_prefix', $options);
        if (!empty($prefix)) $key = $prefix . $key;
        $key = str_replace('\\', '/', $key);
        return $this->key . '/' . $key;
    }

    /**
     * Adds a value to the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param mixed $var A reference to the PHP variable representing the item.
     * @param integer $expire The amount of seconds for the variable to expire in.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    abstract public function add($key, $var, $expire= 0, $options= array());

    /**
     * Sets a value in the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param mixed $var A reference to the PHP variable representing the item.
     * @param integer $expire The amount of seconds for the variable to expire in.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    abstract public function set($key, $var, $expire= 0, $options= array());

    /**
     * Replaces a value in the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being set.
     * @param mixed $var A reference to the PHP variable representing the item.
     * @param integer $expire The amount of seconds for the variable to expire in.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    abstract public function replace($key, $var, $expire= 0, $options= array());

    /**
     * Deletes a value from the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item being deleted.
     * @param array $options Additional options for the operation.
     * @return boolean True if successful
     */
    abstract public function delete($key, $options= array());

    /**
     * Gets a value from the cache.
     *
     * @access public
     * @param string $key A unique key identifying the item to fetch.
     * @param array $options Additional options for the operation.
     * @return mixed The value retrieved from the cache.
     */
    public function get($key, $options= array()) {}

    /**
     * Flush all values from the cache.
     *
     * @access public
     * @param array $options Additional options for the operation.
     * @return boolean True if successful.
     */
    abstract public function flush($options= array());
}
