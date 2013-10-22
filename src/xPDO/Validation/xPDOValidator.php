<?php
/**
 * This file is part of the xpdo package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Validation;

use xPDO\xPDO;
use xPDO\Om\xPDOObject;

/**
 * The base validation service class.
 *
 * Extend this class to customize the validation process.
 *
 * @package xPDO\Validation
 */
class xPDOValidator {
    /** @var xPDOObject */
    public $object = null;
    public $results = array();
    public $messages = array();

    public function __construct(& $object) {
        $this->object = & $object;
        $this->object->_loadValidation(true);
    }

    /**
     * Executes validation against the object attached to this validator.
     *
     * @param array $parameters A collection of parameters.
     * @return boolean Either true or false indicating valid or invalid.
     */
    public function validate(array $parameters = array()) {
        $validated= false;
        $this->reset();
        $stopOnFail= isset($parameters['stopOnFail']) && $parameters['stopOnFail']
                ? true
                : false;
        $stopOnRuleFail= isset($parameters['stopOnRuleFail']) && $parameters['stopOnRuleFail']
                ? true
                : false;
        if (!empty($this->object->_validationRules)) {
            foreach ($this->object->_validationRules as $column => $rules) {
                $this->results[$column]= $this->object->isValidated($column);
                if (!$this->results[$column]) {
                    $columnResults= array();
                    foreach ($rules as $ruleName => $rule) {
                        $result= false;
                        if (is_array($rule['parameters'])) $rule['parameters']['column'] = $column;
                        switch ($rule['type']) {
                            case 'callable':
                                $callable= $rule['rule'];
                                if (is_callable($callable)) {
                                     $result= call_user_func_array($callable, array($this->object->_fields[$column],$rule['parameters']));
                                    if (!$result) $this->addMessage($column, $ruleName, isset($rule['parameters']['message']) ? $rule['parameters']['message'] : $ruleName . ' failed');
                                } else {
                                    $this->object->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Validation function {$callable} is not a valid callable function.");
                                }
                                break;
                            case 'preg_match':
                                $result= (boolean) preg_match($rule['rule'], $this->object->_fields[$column]);
                                if (!$result) $this->addMessage($column, $ruleName, isset($rule['parameters']['message']) ? $rule['parameters']['message'] : $ruleName . ' failed');
                                if ($this->object->xpdo->getDebug() === true)
                                    $this->object->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "preg_match validation against {$rule['rule']} resulted in " . print_r($result, 1));
                                break;
                            case 'xPDOValidationRule':
                                if ($ruleClass= $this->object->xpdo->loadClass($rule['rule'], '', false, true)) {
                                    if ($ruleObject= new $ruleClass($this, $column, $ruleName)) {
                                        $callable= array($ruleObject, 'isValid');
                                        if (is_callable($callable)) {
                                            $callableParams= array($this->object->_fields[$column], $rule['parameters']);
                                            $result= call_user_func_array($callable, $callableParams);
                                        } else {
                                            $this->object->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Validation rule class {$rule['rule']} does not have an isValid() method.");
                                        }
                                    }
                                } else {
                                    $this->object->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not load validation rule class: {$rule['rule']}");
                                }
                                break;
                            default:
                                $this->object->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Unsupported validation rule: " . print_r($rule, true));
                                break;
                        }
                        $columnResults[$ruleName]= $result;
                        if (!$result && $stopOnRuleFail) {
                            break;
                        }
                    }
                    $this->results[$column]= !in_array(false, $columnResults, true) ? true : false;
                    if (!$this->results[$column] && $stopOnFail) {
                        break;
                    }
                }
                if ($this->results[$column]) {
                    $this->object->_validated[$column]= $column;
                }
            }
            if (empty($this->results) || !in_array(false, $this->results, true)) {
                $validated = true;
                if ($this->object->xpdo->getDebug() === true)
                    $this->object->xpdo->log(xPDO::LOG_LEVEL_WARN, "Validation succeeded: " . print_r($this->results, true));
            } elseif ($this->object->xpdo->getDebug() === true) {
                $this->object->xpdo->log(xPDO::LOG_LEVEL_WARN, "Validation failed: " . print_r($this->results, true));
            }
        } else {
            if ($this->object->xpdo->getDebug() === true) $this->object->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Validation called but no rules were found.");
            $validated = true;
        }
        return $validated;
    }

    /**
     * Add a validation message to the stack.
     *
     * @param string $field The name of the field the message relates to.
     * @param string $name The name of the rule the message relates to.
     * @param mixed $message An optional message; the name of the rule is used
     * if no message is specified.
     */
    public function addMessage($field, $name, $message= null) {
        if (empty($message)) $message= $name;
        array_push($this->messages, array(
            'field' => $field,
            'name' => $name,
            'message' => $message,
        ));
    }

    /**
     * Indicates validation messages were generated by validate().
     *
     * @return boolean True if messages were generated.
     */
    public function hasMessages() {
        return (count($this->messages) > 0);
    }

    /**
     * Get the validation messages generated by validate().
     *
     * @return array An array of validation messages.
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * Get the validation results generated by validate().
     *
     * @return array An array of boolean validation results.
     */
    public function getResults() {
        return $this->results;
    }

    /**
     * Reset the validation results and messages.
     */
    public function reset() {
        $this->results= array();
        $this->messages= array();
    }
}
