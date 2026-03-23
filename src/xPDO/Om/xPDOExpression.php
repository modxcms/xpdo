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
 * Wraps a raw SQL expression for use in query SET clauses, bypassing automatic quoting.
 *
 * Use this class to explicitly indicate that a value should be treated as a raw
 * SQL fragment rather than a literal string to be quoted. This is analogous to
 * Laravel's DB::raw().
 *
 * Plain PHP strings passed to xPDOQuery::set() or xPDO::updateCollection() are
 * always quoted/escaped for SQL injection safety. Wrap a value in xPDOExpression
 * only when you intentionally need raw SQL, such as:
 *
 * <code>
 * // Increment a counter column using a SQL expression
 * $xpdo->updateCollection('MyClass', [
 *     'view_count' => $xpdo->expression('view_count + 1'),
 * ]);
 *
 * // Use a SQL function as a SET value
 * $object->set('updated_at', $xpdo->expression('NOW()'));
 * $object->save();
 * </code>
 *
 * @package xPDO\Om
 */
final class xPDOExpression
{
    /** @var string The raw SQL expression string. */
    private string $expression;

    /**
     * @param string $expression The raw SQL expression to use verbatim.
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * Returns the raw SQL expression string.
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->expression;
    }
}
