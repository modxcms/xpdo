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
 * A rule specifying the maximum numeric value of a field value.
 *
 * @package xPDO\Validation
 */
class xPDOMaxValueValidationRule extends xPDOValidationRule {
    public function isValid($value, array $options = array()) {
        $result= parent :: isValid($value, $options);
        $maxValue= isset($options['value']) ? intval($options['value']) : 0;
        $result= ($value <= $maxValue);
        if ($result === false) {
            $this->validator->addMessage($this->field, $this->name, $this->message);
        }
    }
}
