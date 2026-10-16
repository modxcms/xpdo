<?php

namespace xPDO\Test\Validation;

use xPDO\TestCase;
use xPDO\Validation\xPDOMinValueValidationRule;
use xPDO\Validation\xPDOMaxValueValidationRule;
use xPDO\Validation\xPDOValidator;

class xPDOValidationMinMaxTest extends TestCase
{
    public function testMinValueRuleReturnsBoolean()
    {
        $validator = $this->createMock(xPDOValidator::class);
        $rule = new xPDOMinValueValidationRule($validator, 'rating', 'MinValue', 'Rating must be at least 1');

        $this->assertTrue($rule->isValid(5, ['value' => 1]));
        $this->assertTrue($rule->isValid(1, ['value' => 1]));
        $this->assertFalse($rule->isValid(0, ['value' => 1]));
    }

    public function testMaxValueRuleReturnsBoolean()
    {
        $validator = $this->createMock(xPDOValidator::class);
        $rule = new xPDOMaxValueValidationRule($validator, 'rating', 'MaxValue', 'Rating must be at most 10');

        $this->assertTrue($rule->isValid(5, ['value' => 10]));
        $this->assertTrue($rule->isValid(10, ['value' => 10]));
        $this->assertFalse($rule->isValid(11, ['value' => 10]));
    }
}
