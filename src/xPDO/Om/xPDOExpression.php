<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om;

/**
 * A value object wrapping a raw SQL expression for use in xPDOQuery.
 *
 * Pass an instance of this class wherever xPDOQuery accepts a column value
 * or a SELECT column entry when you need a verbatim SQL fragment — for example,
 * a function call (NOW(), CURRENT_TIMESTAMP), an arithmetic expression
 * (counter + 1), or an aggregate with alias (COUNT(*) AS total). Valid contexts
 * include SET, SELECT, WHERE, HAVING, GROUP BY, and ORDER BY clauses.
 *
 * xPDOExpression is intentionally restricted to the query layer. Do NOT pass
 * it to xPDOObject::set() or xPDOObject::save(); PDO parameterised bindings
 * will treat it as a string literal there.
 *
 * SECURITY CONTRACT: The expression string is embedded verbatim into the
 * generated SQL without any quoting, escaping, or parameterisation. The caller
 * is solely responsible for ensuring the expression is safe. User-supplied
 * input must NEVER be passed directly to this class or to the xPDO::expression()
 * factory. Violating this contract will result in SQL injection vulnerabilities.
 *
 * @package xPDO\Om
 */
final class xPDOExpression
{
    private string $expression;

    /**
     * @param string $expression A trusted, developer-controlled SQL fragment.
     *                           MUST NOT contain unsanitised user input.
     * @psalm-taint-sink sql $expression
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }
}
