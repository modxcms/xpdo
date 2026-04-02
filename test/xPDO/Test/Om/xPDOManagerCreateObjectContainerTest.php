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

use xPDO\TestCase;
use xPDO\xPDO;

/**
 * Regression test for issue #207:
 * xPDOManager->createObjectContainer() broken in MySQL 8+ (and other drivers)
 * when the table does not yet exist.
 *
 * Prior to the fix, calling createObjectContainer() on a non-existent table
 * would cause an uncaught PDOException on drivers that raise errors as
 * exceptions (ERRMODE_EXCEPTION). The fix wraps the existence probe in a
 * try/catch so that a missing table causes the method to proceed with
 * CREATE TABLE rather than propagating an exception.
 *
 * @package xPDO\Test\Om
 */
class xPDOManagerCreateObjectContainerTest extends TestCase
{
    /**
     * Set up for each test: ensure the test table does NOT exist beforehand
     * so we exercise the "table missing" code path in createObjectContainer().
     *
     * @before
     */
    public function setUpFixtures()
    {
        parent::setUpFixtures();
        $this->xpdo->getManager();
        // Drop the table if it already exists so each test starts clean.
        try {
            $this->xpdo->exec(
                'DROP TABLE IF EXISTS ' . $this->xpdo->getTableName('xPDO\\Test\\Sample\\Person')
            );
        } catch (\PDOException $e) {
            // Ignore; the table may not exist yet.
        }
    }

    /**
     * Tear down after each test: remove the created table.
     *
     * @after
     */
    public function tearDownFixtures()
    {
        try {
            $this->xpdo->exec(
                'DROP TABLE IF EXISTS ' . $this->xpdo->getTableName('xPDO\\Test\\Sample\\Person')
            );
        } catch (\PDOException $e) {
            // Ignore.
        }
        parent::tearDownFixtures();
    }

    /**
     * Verify createObjectContainer() returns true and does not throw when the
     * table does not yet exist.
     *
     * Regression: on MySQL 8+ (and any driver in ERRMODE_EXCEPTION mode) the
     * SELECT COUNT(*) existence probe threw an uncaught PDOException instead of
     * proceeding to CREATE TABLE.
     */
    public function testCreateObjectContainerOnNonExistentTableReturnsTrue()
    {
        $this->xpdo->getManager();

        // Force ERRMODE_EXCEPTION on the underlying PDO connection to reproduce
        // the MySQL 8+ behaviour that triggered the bug.
        if ($this->xpdo->pdo instanceof \PDO) {
            $this->xpdo->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        $threwException = false;
        $result = false;
        try {
            $result = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Person');
        } catch (\PDOException $e) {
            $threwException = true;
        }

        $this->assertFalse(
            $threwException,
            'createObjectContainer() must not propagate a PDOException when the table does not yet exist (#207).'
        );
        $this->assertTrue(
            $result,
            'createObjectContainer() must return true after successfully creating the table.'
        );
    }

    /**
     * Verify createObjectContainer() returns true (idempotent) when the table
     * already exists, and does not throw.
     */
    public function testCreateObjectContainerOnExistingTableReturnsTrue()
    {
        $this->xpdo->getManager();

        // Create the table first.
        $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Person');

        // Calling a second time must return true without exception.
        $threwException = false;
        $result = false;
        try {
            $result = $this->xpdo->manager->createObjectContainer('xPDO\\Test\\Sample\\Person');
        } catch (\PDOException $e) {
            $threwException = true;
        }

        $this->assertFalse(
            $threwException,
            'createObjectContainer() must not throw when called on an already-existing table.'
        );
        $this->assertTrue(
            $result,
            'createObjectContainer() must return true when the table already exists (idempotent).'
        );
    }
}
