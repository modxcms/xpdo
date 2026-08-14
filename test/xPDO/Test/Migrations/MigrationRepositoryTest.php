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

use DateTimeImmutable;
use DateTimeZone;
use xPDO\Migrations\MigrationRepository;
use xPDO\Migrations\MigratorResult;

class MigrationRepositoryTest extends MigrationTestCase
{
    public function testEnsureInsertQueryBatchAndPendingViaStatus()
    {
        $repo = new MigrationRepository($this->xpdo, $this->makeConfig());
        $this->assertFalse($repo->exists());

        $repo->ensure();
        $this->assertTrue($repo->exists());
        $this->assertSame(0, $repo->maxBatch());
        $this->assertSame(1, $repo->nextBatch());
        $this->assertSame([], $repo->fetchAppliedVersions());

        $v1 = '20260401120000_Alpha';
        $v2 = '20260401120100_Beta';
        $this->writeMigration($v1);
        $this->writeMigration($v2);

        $at = new DateTimeImmutable('2026-04-01 12:00:00', new DateTimeZone('UTC'));
        $batch = $repo->nextBatch();
        $this->assertSame(1, $batch);
        $repo->insert($v1, $batch, $at);
        $repo->insert($v2, $batch, $at->modify('+1 minute'));

        $this->assertSame([$v1, $v2], $repo->fetchAppliedVersions());
        $this->assertSame(1, $repo->maxBatch());
        $this->assertSame(2, $repo->nextBatch());

        $rows = $repo->fetchByBatch(1);
        $this->assertCount(2, $rows);
        $this->assertSame($v2, $rows[0]['version']);
        $this->assertSame($v1, $rows[1]['version']);
        $this->assertSame(1, $rows[0]['batch']);

        $migrator = $this->makeMigrator();
        $status = $migrator->status();
        $this->assertSame(MigratorResult::REPOSITORY_OK, $status->repositoryState);
        $this->assertSame([$v1, $v2], $status->applied);
        $this->assertSame([], $status->pending);

        $v3 = '20260401120200_Gamma';
        $this->writeMigration($v3);
        $status = $migrator->status();
        $this->assertSame([$v3], $status->pending);

        $repo->delete($v2);
        $this->assertSame([$v1], $repo->fetchAppliedVersions());
        $status = $migrator->status();
        $this->assertSame([$v1], $status->applied);
        $this->assertSame([$v2, $v3], $status->pending);
    }

    public function testStatusReportsOrphanedLedgerRows()
    {
        $repo = new MigrationRepository($this->xpdo, $this->makeConfig());
        $repo->ensure();
        $orphan = '20260402120000_Orphan';
        $repo->insert($orphan, 1, new DateTimeImmutable('now', new DateTimeZone('UTC')));

        $status = $this->makeMigrator()->status();
        $this->assertSame([$orphan], $status->applied);
        $this->assertSame([$orphan], $status->orphaned);
        $this->assertSame([], $status->pending);
    }

    public function testDuplicateVersionInsertFails()
    {
        $repo = new MigrationRepository($this->xpdo, $this->makeConfig());
        $repo->ensure();
        $version = '20260403120000_Dup';
        $at = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $repo->insert($version, 1, $at);

        $this->expectException(\xPDO\Migrations\Exception\MigrationException::class);
        $repo->insert($version, 2, $at);
    }
}
