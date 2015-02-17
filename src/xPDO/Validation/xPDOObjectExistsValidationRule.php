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
 * A rule validating if an object exists.
 *
 * @package xPDO\Validation
 */
class xPDOObjectExistsValidationRule extends xPDOValidationRule {
    public function isValid($value, array $options = array()) {
        if (!isset($options['pk']) || !isset($options['className'])) return false;

        $result= parent :: isValid($value, $options);
        $xpdo =& $this->validator->object->xpdo;

        $obj = $xpdo->getObject($options['className'],$options['pk']);
        $result = ($obj !== null);
        if ($result === false) {
            $this->validator->addMessage($this->field, $this->name, $this->message);
        }
        return $result;
    }
}
