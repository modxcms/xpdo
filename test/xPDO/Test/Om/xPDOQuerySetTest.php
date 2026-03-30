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
 * Tests for xPDOQuery::set() string quoting and xPDOExpression passthrough.
 *
 * These tests construct UPDATE queries and inspect the generated SQL to verify:
 *   - Plain PHP strings with SQL keywords are always quoted (PARAM_STR path)
 *   - xPDOExpression values are inlined verbatim without quoting
 *
 * @package xPDO\Test\Om
 */
class xPDOQuerySetTest extends TestCase
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
     * A plain string value containing the SQL keyword IN must be quoted in the
     * SET clause, not left as bare SQL. Before the fix, isConditionalClause()
     * detected " IN " inside the value string and left $type = null, causing
     * the value to be inlined unquoted.
     */
    public function testSetPlainStringWithSqlKeywordIsQuoted()
    {
        $suspiciousValue = 'something IN list';

        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->command('UPDATE');
        $query->set(['first_name' => $suspiciousValue]);
        $query->construct();

        $sql = $query->toSQL();

        // The value must appear quoted in the SQL, not as bare SQL keyword soup
        $this->assertStringContainsString("'something IN list'", $sql,
            'A plain string containing SQL keyword IN must be quoted in the SET clause. SQL was: ' . $sql);
    }

    /**
     * A plain string value containing the SQL keyword LIKE must be quoted in
     * the SET clause, not treated as a conditional expression.
     */
    public function testSetPlainStringWithLikeKeywordIsQuoted()
    {
        $suspiciousValue = 'something LIKE pattern';

        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->command('UPDATE');
        $query->set(['first_name' => $suspiciousValue]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString("'something LIKE pattern'", $sql,
            'A plain string containing SQL keyword LIKE must be quoted in the SET clause. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression wrapping a SQL expression must be inlined verbatim in
     * the SET clause without quoting.
     */
    public function testSetExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->command('UPDATE');
        $query->set(['security_level' => new xPDOExpression('security_level + 1')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('security_level + 1', $sql,
            'An xPDOExpression must be inlined verbatim in the SET clause. SQL was: ' . $sql);

        // It must NOT be quoted
        $this->assertStringNotContainsString("'security_level + 1'", $sql,
            'An xPDOExpression must NOT be quoted in the SET clause. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression wrapping NOW() must appear verbatim in the SET clause.
     */
    public function testSetExpressionWithNowFunction()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->command('UPDATE');
        $query->set(['dob' => new xPDOExpression('NOW()')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('NOW()', $sql,
            'An xPDOExpression wrapping NOW() must appear verbatim in SET clause. SQL was: ' . $sql);

        $this->assertStringNotContainsString("'NOW()'", $sql,
            'An xPDOExpression wrapping NOW() must NOT be quoted. SQL was: ' . $sql);
    }
}
