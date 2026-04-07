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

use xPDO\Om\xPDOExpression;
use xPDO\TestCase;

/**
 * Tests for the xPDOExpression value object and the xPDO::expression() factory method.
 *
 * @package xPDO\Test\Om
 */
class xPDOExpressionTest extends TestCase
{
    /**
     * Verify that xPDO::expression() returns an xPDOExpression instance.
     */
    public function testExpressionFactoryReturnsObject()
    {
        $expr = $this->xpdo->expression('NOW()');
        $this->assertInstanceOf(xPDOExpression::class, $expr,
            'xPDO::expression() must return an instance of xPDOExpression');
    }

    /**
     * Verify that xPDOExpression::getExpression() returns the raw string passed
     * to the constructor without modification.
     */
    public function testExpressionGetExpressionReturnsString()
    {
        $raw = 'counter + 1';
        $expr = new xPDOExpression($raw);
        $this->assertSame($raw, $expr->getExpression(),
            'xPDOExpression::getExpression() must return the exact string supplied to the constructor');
    }
}
