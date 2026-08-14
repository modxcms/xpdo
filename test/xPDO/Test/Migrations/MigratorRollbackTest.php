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

use xPDO\Migrations\Exception\IrreversibleMigrationException;
use xPDO\Migrations\Exception\MigrationException;
use xPDO\Migrations\MigratorResult;

class MigratorRollbackTest extends MigrationTestCase
{
    public function testSuccessfulRollbackRestoresPending()
    {
        $m1 = '20260201120000_One';
        $m2 = '20260201120100_Two';
        $this->writeMigration($m1);
        $this->writeMigration($m2);

        $migrator = $this->makeMigrator();
        $migrator->migrate();

        $rolled = $migrator->rollback();
        $this->assertSame([$m2, $m1], $rolled->rolledBack);
        $this->assertSame(1, $rolled->batch);

        $status = $migrator->status();
        $this->assertSame([], $status->applied);
        $this->assertSame([$m1, $m2], $status->pending);
    }

    public function testRollbackOrderingIsDescendingWithinBatch()
    {
        $names = [
            '20260202120000_A',
            '20260202120100_B',
            '20260202120200_C',
        ];
        $orderFile = $this->migrationsDir . '/down_order.log';
        foreach ($names as $name) {
            $this->writeMigration(
                $name,
                '',
                'file_put_contents(' . var_export($orderFile, true) . ', ' . var_export($name, true) . ' . PHP_EOL, FILE_APPEND);'
            );
        }

        $migrator = $this->makeMigrator();
        $migrator->migrate();
        $migrator->rollback();

        $lines = array_values(array_filter(array_map('trim', file($orderFile))));
        $this->assertSame(array_reverse($names), $lines);
    }

    public function testRollbackStepLimitsToOne()
    {
        $m1 = '20260203120000_One';
        $m2 = '20260203120100_Two';
        $this->writeMigration($m1);
        $this->writeMigration($m2);
        $migrator = $this->makeMigrator();
        $migrator->migrate();

        $rolled = $migrator->rollback(1);
        $this->assertSame([$m2], $rolled->rolledBack);

        $status = $migrator->status();
        $this->assertSame([$m1], $status->applied);
        $this->assertSame([$m2], $status->pending);
    }

    public function testFailedRollbackKeepsLedgerRow()
    {
        $m1 = '20260204120000_Ok';
        $m2 = '20260204120100_BadDown';
        $this->writeMigration($m1);
        $this->writeMigration($m2, '', 'throw new \\RuntimeException("down-boom");');

        $migrator = $this->makeMigrator();
        $migrator->migrate();

        try {
            $migrator->rollback();
            $this->fail('Expected MigrationException');
        } catch (MigrationException $e) {
            $this->assertStringContainsString('20260204120100_BadDown', $e->getMessage());
            $this->assertStringContainsString('down', $e->getMessage());
            $this->assertStringContainsString('down-boom', $e->getMessage());
        }

        $status = $migrator->status();
        $this->assertSame([$m1, $m2], $status->applied);
        $this->assertSame([], $status->pending);
    }

    public function testIrreversibleDownAbortsWithoutRemovingRow()
    {
        $name = '20260205120000_Forever';
        $this->writeMigration(
            $name,
            '',
            'throw new \\xPDO\\Migrations\\Exception\\IrreversibleMigrationException("nope");'
        );
        $migrator = $this->makeMigrator();
        $migrator->migrate();

        try {
            $migrator->rollback();
            $this->fail('Expected IrreversibleMigrationException');
        } catch (IrreversibleMigrationException $e) {
            $this->assertStringContainsString('nope', $e->getMessage());
        }

        $status = $migrator->status();
        $this->assertSame([$name], $status->applied);
    }

    public function testPartialBatchRollback()
    {
        $ok = '20260206120000_Ok';
        $bad = '20260206120100_Bad';
        $this->writeMigration($ok);
        $this->writeMigration($bad, 'throw new \\RuntimeException("up-fail");');

        $migrator = $this->makeMigrator();
        try {
            $migrator->migrate();
        } catch (MigrationException $e) {
            $this->assertStringContainsString('up-fail', $e->getMessage());
        }

        $status = $migrator->status();
        $this->assertSame([$ok], $status->applied);

        $rolled = $migrator->rollback();
        $this->assertSame([$ok], $rolled->rolledBack);
        $this->assertSame([], $migrator->status()->applied);
    }

    public function testRollbackFailsWhenMigrationFileMissing()
    {
        $name = '20260207120000_Gone';
        $file = $this->writeMigration($name);
        $migrator = $this->makeMigrator();
        $migrator->migrate();
        $this->assertFileExists($file);
        unlink($file);

        try {
            $migrator->rollback();
            $this->fail('Expected MigrationException for missing migration file');
        } catch (MigrationException $e) {
            $this->assertStringContainsString($name, $e->getMessage());
        }

        $status = $migrator->status();
        $this->assertSame([$name], $status->applied);
        $this->assertSame([$name], $status->orphaned);
    }

    public function testEmptyRollbackSucceeds()
    {
        $migrator = $this->makeMigrator();
        $result = $migrator->rollback();
        $this->assertSame([], $result->rolledBack);
        $this->assertSame([], $result->applied);
    }
}
