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
 * Tests for xPDOExpression support in xPDOQuery join() conditions.
 *
 * The join() $conditions argument flows through condition() -> parseConditions(),
 * which already handles xPDOExpression in all code paths. These tests confirm
 * that passing an xPDOExpression as the join condition results in the expression
 * being inlined verbatim in the JOIN ON clause — not quoted, not dropped.
 *
 * @package xPDO\Test\Om
 */
class xPDOQueryJoinTest extends TestCase
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
            $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\BloodType');
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
            $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\BloodType');
            $this->xpdo->manager->removeObjectContainer('xPDO\\Test\\Sample\\Person');
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        parent::tearDownFixtures();
    }

    /**
     * An xPDOExpression passed as the $conditions argument to leftJoin() must be
     * inlined verbatim in the JOIN ON clause — not quoted, not dropped.
     *
     * The join() method delegates $conditions to condition() -> parseConditions(),
     * which handles bare xPDOExpression objects via the `instanceof xPDOExpression`
     * branch. This test confirms the full path from leftJoin() through to the
     * generated SQL.
     */
    public function testJoinConditionExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->leftJoin(
            'xPDO\\Test\\Sample\\BloodType',
            'BloodType',
            new xPDOExpression('BloodType.type = Person.blood_type')
        );
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('JOIN', $sql,
            'A leftJoin() call must produce a JOIN clause. SQL was: ' . $sql);

        $this->assertStringContainsString('BloodType.type = Person.blood_type', $sql,
            'An xPDOExpression in leftJoin() conditions must be inlined verbatim in the ON clause. SQL was: ' . $sql);
    }

    /**
     * An xPDOExpression passed as an associative-key condition to leftJoin() must
     * be inlined verbatim in the JOIN ON clause without quoting the expression value.
     *
     * This exercises the associative array code path in parseConditions() where
     * the value is an xPDOExpression.
     */
    public function testJoinConditionArrayExpressionIsInlinedVerbatim()
    {
        $query = $this->xpdo->newQuery('xPDO\\Test\\Sample\\Person');
        $query->leftJoin(
            'xPDO\\Test\\Sample\\BloodType',
            'BloodType',
            ['BloodType.type' => new xPDOExpression('Person.blood_type')]
        );
        $query->construct();

        $sql = $query->toSQL();

        $this->assertStringContainsString('JOIN', $sql,
            'A leftJoin() call must produce a JOIN clause. SQL was: ' . $sql);

        $this->assertStringContainsString('Person.blood_type', $sql,
            'An xPDOExpression value in an associative join condition must be inlined verbatim. SQL was: ' . $sql);

        // Must not be quoted as a string literal
        $this->assertStringNotContainsString("'Person.blood_type'", $sql,
            'An xPDOExpression in a join condition must NOT be quoted. SQL was: ' . $sql);
    }
}
