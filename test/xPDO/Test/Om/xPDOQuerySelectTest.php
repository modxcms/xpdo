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
 * Tests for xPDOExpression passthrough in the SELECT column list.
 *
 * These tests construct SELECT queries and inspect the generated SQL to verify:
 *   - Plain column names are wrapped in the driver's identifier quoting
 *   - xPDOExpression values are passed through verbatim, bypassing the quoting regex
 *
 * @package xPDO\Test\Om
 */
class xPDOQuerySelectTest extends TestCase
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
     * A plain column name passed to select() must be wrapped in identifier
     * quoting characters in the resulting SQL.
     */
    public function testSelectPlainColumnIsQuoted()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->select(['first_name']);
        $query->construct();

        $sql = $query->toSQL();
        $escapeOpen = $this->xpdo->_escapeCharOpen;

        $this->assertStringContainsString($escapeOpen . 'first_name' . $this->xpdo->_escapeCharClose, $sql,
            'A plain column name must be quoted as an identifier in SELECT. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression passed to select() must appear verbatim in the SELECT
     * column list without any identifier quoting applied.
     */
    public function testSelectExpressionIsNotQuoted()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->select([new xPDOExpression('COUNT(*)')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('COUNT(*)', $sql,
            'An xPDOExpression must appear verbatim in SELECT. SQL was: ' . $sql);

        // Must not be wrapped in identifier quotes
        $escapeOpen = $this->xpdo->_escapeCharOpen;
        $escapeClose = $this->xpdo->_escapeCharClose;
        $this->assertStringNotContainsString($escapeOpen . 'COUNT(*)', $sql,
            'An xPDOExpression must NOT be identifier-quoted in SELECT. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression containing an alias (e.g. COUNT(*) AS total) must pass
     * through to the SELECT column list verbatim.
     */
    public function testSelectExpressionWithAlias()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->select([new xPDOExpression('COUNT(*) AS total')]);
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('COUNT(*) AS total', $sql,
            'An xPDOExpression with AS alias must appear verbatim in SELECT. SQL was: ' . $sql);
    }

    /**
     * A mix of plain column names and xPDOExpression values must both be
     * represented correctly in the SELECT column list.
     */
    public function testSelectMixedColumnsAndExpressions()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->select([
            'first_name',
            new xPDOExpression('COUNT(*) AS cnt'),
        ]);
        $query->construct();

        $sql = $query->toSQL();
        $escapeOpen = $this->xpdo->_escapeCharOpen;
        $escapeClose = $this->xpdo->_escapeCharClose;

        $this->assertStringContainsString($escapeOpen . 'first_name' . $escapeClose, $sql,
            'Plain column must be identifier-quoted in mixed SELECT. SQL was: ' . $sql);

        $this->assertStringContainsString('COUNT(*) AS cnt', $sql,
            'xPDOExpression must appear verbatim in mixed SELECT. SQL was: ' . $sql);
    }
}
