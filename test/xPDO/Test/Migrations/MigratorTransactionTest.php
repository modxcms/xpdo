<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Migrations;

use PDO;
use xPDO\Migrations\Exception\MigrationException;
use xPDO\Migrations\MigrationConfig;

class MigratorTransactionTest extends MigrationTestCase
{
    public function testTransactionalFailureRollsBackSideEffectsAndDoesNotRecord()
    {
        $this->ensureSideTable();
        $table = $this->sideTable();
        $name = '20260301120000_TxnFail';

        $this->writeMigration(
            $name,
            '$xpdo = $context->getXpdo();'
            . '$stmt = $xpdo->prepare("INSERT INTO " . $xpdo->escape(' . var_export($table, true) . ') . " (id, note) VALUES (1, ?)");'
            . '$stmt->execute(["before-fail"]);'
            . 'throw new \\RuntimeException("txn-boom");',
            '',
            true
        );

        $migrator = $this->makeMigrator([
            'transaction_policy' => MigrationConfig::POLICY_TRANSACTIONAL,
        ]);

        try {
            $migrator->migrate();
            $this->fail('Expected MigrationException');
        } catch (MigrationException $e) {
            $this->assertStringContainsString('txn-boom', $e->getMessage());
            $this->assertStringContainsString($name, $e->getMessage());
        }

        $status = $migrator->status();
        $this->assertSame([], $status->applied);
        $this->assertSame([$name], $status->pending);

        $escaped = $this->xpdo->escape($table);
        $stmt = $this->xpdo->query("SELECT COUNT(*) AS c FROM {$escaped}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(0, (int) $row['c']);

        // Driver caveat: MySQL DDL often implicit-commits; this case uses DML INSERT only.
        $this->assertContains($this->xpdo->getOption('dbtype'), ['sqlite', 'mysql', 'pgsql', 'sqlsrv']);
    }

    public function testTransactionalSuccessCommitsSideEffectAndLedger()
    {
        $this->ensureSideTable();
        $table = $this->sideTable();
        $name = '20260302120000_TxnOk';

        $this->writeMigration(
            $name,
            '$xpdo = $context->getXpdo();'
            . '$stmt = $xpdo->prepare("INSERT INTO " . $xpdo->escape(' . var_export($table, true) . ') . " (id, note) VALUES (1, ?)");'
            . '$stmt->execute(["ok"]);',
            '$xpdo = $context->getXpdo();'
            . '$xpdo->{\'exec\'}("DELETE FROM " . $xpdo->escape(' . var_export($table, true) . '));',
            true
        );

        $migrator = $this->makeMigrator([
            'transaction_policy' => MigrationConfig::POLICY_TRANSACTIONAL,
        ]);
        $migrator->migrate();

        $this->assertSame([$name], $migrator->status()->applied);
        $escaped = $this->xpdo->escape($table);
        $stmt = $this->xpdo->query("SELECT note FROM {$escaped} WHERE id = 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('ok', $row['note']);
    }
}
