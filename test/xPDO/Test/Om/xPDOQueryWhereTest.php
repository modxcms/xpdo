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
use xPDO\xPDO;

/**
 * Tests for xPDOExpression support in xPDOQuery WHERE conditions (parseConditions).
 *
 * These tests construct SELECT and UPDATE queries with xPDOExpression values in
 * where() calls and inspect the generated SQL to verify:
 *   - xPDOExpression values are inlined verbatim (not quoted, not dropped)
 *   - A WHERE clause is always present when where() is called with an expression
 *   - Mixed scalar and expression conditions both appear correctly
 *
 * @package xPDO\Test\Om
 */
class xPDOQueryWhereTest extends TestCase
{
    /**
     * @before
     */
    public function setUpFixtures()
    {
        parent::setUpFixtures();
        try {
            $this->xpdo->getManager();
            $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Person');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * @after
     */
    public function tearDownFixtures()
    {
        try {
            $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Person');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        parent::tearDownFixtures();
    }

    /**
     * An xPDOExpression value in where() must be inlined verbatim in the WHERE
     * clause — not quoted as a string literal.
     *
     * Before the fix, the condition was silently dropped because xPDOExpression
     * fails is_scalar(), is_array(), and $val === null checks.
     */
    public function testWhereExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where(['first_name' => new xPDOExpression("'active'")]);
        $query->construct();

        $sql = $query->toSQL();

        // Column name is escaped by the driver; the expression value must be inlined verbatim
        $this->assertStringContainsString("= 'active'", $sql,
            'An xPDOExpression in where() must be inlined verbatim. SQL was: ' . $sql);

        // Must NOT be doubly-quoted as a string literal wrapping the expression
        $this->assertStringNotContainsString("= \"'active'\"", $sql,
            'An xPDOExpression in where() must NOT be re-quoted. SQL was: ' . $sql);
    }

    /**
     * After calling where() with an xPDOExpression value, the generated SQL must
     * contain a WHERE clause. Before the fix, the condition was silently dropped,
     * resulting in a full-table query with no WHERE clause at all.
     */
    public function testWhereExpressionIsNotSilentlyDropped()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where(['security_level' => new xPDOExpression('0')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('WHERE', $sql,
            'A where() call with an xPDOExpression must produce a WHERE clause. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression wrapping a SQL function call (NOW()) must appear verbatim
     * in the WHERE clause without quoting.
     */
    public function testWhereExpressionWithRawSqlFragment()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where(['dob' => new xPDOExpression('NOW()')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('NOW()', $sql,
            'An xPDOExpression wrapping NOW() must appear verbatim in the WHERE clause. SQL was: ' . $sql);

        $this->assertStringNotContainsString("'NOW()'", $sql,
            'An xPDOExpression wrapping NOW() must NOT be quoted in the WHERE clause. SQL was: ' . $sql);
    }

    /**
     * A where() call mixing a plain scalar value and an xPDOExpression value must
     * produce both conditions correctly in the WHERE clause.
     */
    public function testWhereConditionWithMixedScalarAndExpression()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where([
            'first_name' => 'John',
            'security_level' => new xPDOExpression('security_level + 1'),
        ]);
        $query->construct();

        $sql = $query->toSQL();

        // The scalar value must appear as a bound parameter placeholder
        $this->assertStringContainsString('first_name', $sql,
            'The scalar condition must appear in the WHERE clause. SQL was: ' . $sql);

        // The expression must be inlined verbatim
        $this->assertStringContainsString('security_level + 1', $sql,
            'The xPDOExpression condition must be inlined verbatim in the WHERE clause. SQL was: ' . $sql);

        // The expression must NOT be quoted
        $this->assertStringNotContainsString("'security_level + 1'", $sql,
            'The xPDOExpression condition must NOT be quoted. SQL was: ' . $sql);
    }

    /**
     * An operator in the key (e.g. 'field:!=') must be respected when the value is
     * an xPDOExpression. Before the fix, the expression branch was reached only
     * after the scalar/array/null gate, meaning operators with expression values
     * were never extracted — the condition was silently dropped.
     *
     * This also exercises the operator-extraction path inside the xPDOExpression
     * branch, which was not covered by prior tests.
     */
    public function testWhereExpressionOperatorSyntax()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where(['first_name:!=' => new xPDOExpression('other_field')]);
        $query->construct();

        $sql = $query->toSQL();

        // The != operator must be present in the generated SQL
        $this->assertStringContainsString('!=', $sql,
            'The != operator from key syntax must appear in the WHERE clause. SQL was: ' . $sql);

        // The expression value must be inlined verbatim
        $this->assertStringContainsString('other_field', $sql,
            'The xPDOExpression value must be inlined verbatim. SQL was: ' . $sql);

        // Must not be quoted
        $this->assertStringNotContainsString("'other_field'", $sql,
            'The xPDOExpression value must NOT be quoted. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression passed as a positional (integer-keyed) where() entry must
     * be inlined verbatim in the WHERE clause.
     *
     * Before this fix, the is_int($key) branch only handled arrays and raw strings
     * that pass isConditionalClause(). An xPDOExpression object is neither, so it
     * fell through to the error log and was silently dropped.
     *
     * `$query->where([new xPDOExpression("1 = 1")])` must produce a WHERE clause
     * containing `1 = 1`.
     */
    public function testWherePositionalExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where([new xPDOExpression('1 = 1')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('WHERE', $sql,
            'A positional xPDOExpression must produce a WHERE clause. SQL was: ' . $sql);

        $this->assertStringContainsString('1 = 1', $sql,
            'A positional xPDOExpression must be inlined verbatim. SQL was: ' . $sql);
    }

    /**
     * A positional xPDOExpression combined with a second condition via AND must
     * produce both conditions correctly in the WHERE clause.
     *
     * This exercises the conjunction forwarding path: the expression is injected
     * with the outer $conjunction so AND/OR logic is preserved.
     */
    public function testWherePositionalExpressionWithConjunction()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where([new xPDOExpression('1 = 1')]);
        $query->andCondition(['first_name' => 'John']);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('1 = 1', $sql,
            'The positional xPDOExpression must appear in the WHERE clause. SQL was: ' . $sql);

        $this->assertStringContainsString('first_name', $sql,
            'The AND scalar condition must also appear in the WHERE clause. SQL was: ' . $sql);
    }

    /**
     * A bare xPDOExpression (not wrapped in an array) passed directly to where()
     * must be inlined verbatim in the generated WHERE clause.
     *
     * Before this fix, a bare xPDOExpression object passed the is_array() check
     * (false), failed isConditionalClause() (which only accepts strings), failed
     * the PK type checks, and fell into the final else branch which set
     * $result->sql to the object itself. In PHP 8 that causes a TypeError when
     * buildConditionalClause() attempts string concatenation because xPDOExpression
     * has no __toString(). The condition was effectively broken / unusable.
     *
     * `$query->where(new xPDOExpression("1 = 1"))` must produce a WHERE clause
     * containing the literal `1 = 1`.
     */
    public function testWhereDirectExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->where(new xPDOExpression('1 = 1'));
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('WHERE', $sql,
            'A bare xPDOExpression passed directly to where() must produce a WHERE clause. SQL was: ' . $sql);

        $this->assertStringContainsString('1 = 1', $sql,
            'A bare xPDOExpression passed directly to where() must be inlined verbatim. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression value passed to having() must be inlined verbatim in the
     * HAVING clause and must not be silently dropped.
     *
     * The having() method delegates to where() with the same parseConditions()
     * path; this test confirms the fix covers HAVING as well as WHERE.
     */
    public function testHavingExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->select(['COUNT(*) AS cnt', 'security_level']);
        $query->groupby('security_level');
        $query->having(['security_level:>=' => new xPDOExpression('5')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('HAVING', $sql,
            'A having() call with an xPDOExpression must produce a HAVING clause. SQL was: ' . $sql);

        $this->assertStringContainsString('5', $sql,
            'The xPDOExpression value must be inlined verbatim in the HAVING clause. SQL was: ' . $sql);

        // Must not be quoted as a string literal
        $this->assertStringNotContainsString("'5'", $sql,
            'The xPDOExpression value in HAVING must NOT be quoted. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression passed to orCondition() must be inlined verbatim in the
     * WHERE clause using an OR conjunction.
     *
     * orCondition() delegates to where() with SQL_OR conjunction. This test
     * confirms the OR conjunction is preserved and the expression value is
     * inlined verbatim — not quoted, not dropped.
     */
    public function testOrConditionExpressionPreservesOrConjunction()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        // Establish a baseline WHERE condition first so the OR has something to attach to
        $query->where(['security_level' => 1]);
        $query->orCondition(new xPDOExpression("status = 'archived'"));
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('WHERE', $sql,
            'A where() + orCondition() call must produce a WHERE clause. SQL was: ' . $sql);

        $this->assertStringContainsString('OR', $sql,
            'orCondition() must produce an OR conjunction in the WHERE clause. SQL was: ' . $sql);

        $this->assertStringContainsString("status = 'archived'", $sql,
            'The xPDOExpression in orCondition() must be inlined verbatim. SQL was: ' . $sql);
    }
}
