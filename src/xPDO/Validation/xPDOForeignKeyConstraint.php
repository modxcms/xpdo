<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Validation;

/**
 * Validate a foreign key constraint refers to an existing object.
 *
 * @package xPDO\Validation
 */
class xPDOForeignKeyConstraint extends xPDOValidationRule {
    public function isValid($value, array $options = array()) {
        if (!isset($options['alias'])) return false;
        parent :: isValid($value, $options);
        $result= false;
        $obj=& $this->validator->object;
        $xpdo=& $obj->xpdo;

        $fkdef= $obj->getFKDefinition($options['alias']);
        if (isset ($obj->_relatedObjects[$options['alias']])) {
            if (!is_object($obj->_relatedObjects[$options['alias']])) {
                $result= false;
            }
        }

        $criteria= array ($fkdef['foreign'] => $obj->get($fkdef['local']));
        if (isset($fkdef['criteria']['foreign'])) {
            $criteria= array($fkdef['criteria']['foreign'], $criteria);
        }
        if ($object= $xpdo->getObject($fkdef['class'], $criteria)) {
            $result= ($object !== null);
        }
        if ($result === false) {
            $this->validator->addMessage($this->field, $this->name, $this->message);
        }
        return $result;
    }
}
