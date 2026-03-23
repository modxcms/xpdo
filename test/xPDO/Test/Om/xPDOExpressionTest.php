<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Om;

use ReflectionClass;
use xPDO\Om\xPDOExpression;
use xPDO\TestCase;

/**
 * Tests for the xPDOExpression value object.
 *
 * @package xPDO\Test\Om
 */
class xPDOExpressionTest extends TestCase
{
    /**
     * Test that the expression stores and returns the raw SQL string.
     */
    public function testExpressionStoresValue()
    {
        $expr = new xPDOExpression('NOW()');
        $this->assertEquals('NOW()', $expr->getExpression());
    }

    /**
     * Test that __toString() returns the raw SQL string.
     */
    public function testExpressionToString()
    {
        $expr = new xPDOExpression('NOW()');
        $this->assertEquals('NOW()', (string)$expr);
    }

    /**
     * Test that xPDOExpression is declared final to prevent subclassing.
     */
    public function testExpressionIsFinal()
    {
        $ref = new ReflectionClass(xPDOExpression::class);
        $this->assertTrue($ref->isFinal());
    }

    /**
     * Test the xPDO::expression() factory method returns an xPDOExpression.
     */
    public function testXpdoFactoryMethod()
    {
        $expr = $this->xpdo->expression('CURRENT_TIMESTAMP');
        $this->assertInstanceOf(xPDOExpression::class, $expr);
        $this->assertEquals('CURRENT_TIMESTAMP', $expr->getExpression());
    }
}
