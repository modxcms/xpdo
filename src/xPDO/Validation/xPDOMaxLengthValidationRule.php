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
 * A rule specifying the max length of a field value.
 *
 * @package xPDO\Validation
 */
class xPDOMaxLengthValidationRule extends xPDOValidationRule {
    public function isValid($value, array $options = array()) {
        $result= parent :: isValid($value, $options);
        $maxLength= isset($options['value']) ? intval($options['value']) : 0;
        $result= ($maxLength > 0 && is_string($value) && strlen($value) <= $maxLength);
        if ($result === false) {
            $this->validator->addMessage($this->field, $this->name, $this->message);
        }
        return $result;
    }
}
