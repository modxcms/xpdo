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
 * The base validation rule class.
 *
 * @package xPDO\Validation
 */
class xPDOValidationRule {
    public $validator = null;
    public $field = '';
    public $name = '';
    public $message = '';

    /**
     * Construct a new xPDOValidationRule instance.
     *
     * @param xPDOValidator &$validator A reference to the xPDOValidator executing this rule.
     * @param mixed $field The field being validated.
     * @param mixed $name The identifying name of the validation rule.
     * @param string $message An optional message for rule failure.
     * @return xPDOValidationRule The rule instance.
     */
    public function __construct(& $validator, $field, $name, $message= '') {
        $this->validator = & $validator;
        $this->field = $field;
        $this->name = $name;
        $this->message = (!empty($message) && $message !== '0' ? $message : $name);
    }

    /**
     * The public method for executing a validation rule.
     *
     * Extend this method to provide a reusable validation rule in your xPDOValidator instance.
     *
     * @param mixed $value The value of the field being validated.
     * @param array $options Any options expected by the rule.
     * @return boolean True if the validation rule was passed, otherwise false.
     */
    public function isValid($value, array $options = array()) {
        if (isset($options['message'])) {
            $this->setMessage($options['message']);
        }
        return true;
    }

    /**
     * Set the failure message for the rule.
     *
     * @param string $message A message intended to convey the reason for rule failure.
     */
    public function setMessage($message= '') {
        if (!empty($message) && $message !== '0') {
            $this->message= $message;
        }
    }
}
