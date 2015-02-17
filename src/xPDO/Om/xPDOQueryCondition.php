<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om;

/**
 * Abstracts individual query conditions used in xPDOQuery instances.
 *
 * @package xPDO\Om
 */
class xPDOQueryCondition {
    /**
     * @var string The SQL string for the condition.
     */
    public $sql = '';
    /**
     * @var array An array of value/parameter bindings for the condition.
     */
    public $binding = array();
    /**
     * @var string The conjunction identifying how the condition is related to the previous condition(s).
     */
    public $conjunction = xPDOQuery::SQL_AND;

    /**
     * The constructor for creating an xPDOQueryCondition instance.
     *
     * @param array $properties An array of properties representing the condition.
     */
    public function __construct(array $properties) {
        if (isset($properties['sql'])) $this->sql = $properties['sql'];
        if (isset($properties['binding'])) $this->binding = $properties['binding'];
        if (isset($properties['conjunction'])) $this->conjunction = $properties['conjunction'];
    }
}
