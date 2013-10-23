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
 * A rule specifying the min length of a field value.
 *
 * @package xPDO\Validation
 */
class xPDOMinLengthValidationRule extends xPDOValidationRule {
    public function isValid($value, array $options = array()) {
        $result= parent :: isValid($value, $options);
        $minLength= isset($options['value']) ? intval($options['value']) : 0;
        $result= (is_string($value) && strlen($value) >= $minLength);
        if ($result === false) {
            $this->validator->addMessage($this->field, $this->name, $this->message);
        }
        return $result;
    }
}
