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

use xPDO\Migrations\MigratorResult;

class MigratorHappyPathTest extends MigrationTestCase
{
    public function testEmptyMigrationSetSucceeds()
    {
        $migrator = $this->makeMigrator();
        $result = $migrator->migrate();
        $this->assertSame(MigratorResult::REPOSITORY_OK, $result->repositoryState);
        $this->assertSame([], $result->applied);
        $this->assertSame([], $result->pending);
        $this->assertNull($result->batch);
    }

    public function testSingleMigrationMigrate()
    {
        $name = '20260101115900_OnlyOne';
        $this->writeMigration($name);
        $migrator = $this->makeMigrator();
        $result = $migrator->migrate();
        $this->assertSame([$name], $result->applied);
        $this->assertSame(1, $result->batch);
        $this->assertSame([$name], $migrator->status()->applied);
    }

    public function testDiscoverPendingMigrateAndStatus()
    {
        $m1 = '20260101120000_CreateAlpha';
        $m2 = '20260101120100_CreateBeta';
        $this->writeMigration($m1);
        $this->writeMigration($m2);

        $migrator = $this->makeMigrator();

        $beforeEnsure = $migrator->status();
        $this->assertSame(MigratorResult::REPOSITORY_MISSING, $beforeEnsure->repositoryState);

        $result = $migrator->migrate();
        $this->assertSame(MigratorResult::REPOSITORY_OK, $result->repositoryState);
        $this->assertSame([$m1, $m2], $result->applied);
        $this->assertSame([], $result->pending);
        $this->assertSame(1, $result->batch);

        $status = $migrator->status();
        $this->assertSame(MigratorResult::REPOSITORY_OK, $status->repositoryState);
        $this->assertSame([$m1, $m2], $status->applied);
        $this->assertSame([], $status->pending);

        $again = $migrator->migrate();
        $this->assertSame([], $again->applied);
        $this->assertSame([], $again->pending);
    }

    public function testMigrateStepAppliesSubset()
    {
        $m1 = '20260101121000_StepA';
        $m2 = '20260101121100_StepB';
        $this->writeMigration($m1);
        $this->writeMigration($m2);
        $migrator = $this->makeMigrator();

        $result = $migrator->migrate(1);
        $this->assertSame([$m1], $result->applied);
        $this->assertSame([$m2], $result->pending);
    }

    public function testMigrateKeepsPinnedPdoIdentity()
    {
        $this->writeMigration('20260101122000_Pin');
        $this->assertTrue($this->xpdo->connect(null, [\xPDO\xPDO::OPT_CONN_MUTABLE => true]));
        $pdoBefore = $this->xpdo->pdo;
        $this->assertInstanceOf(\PDO::class, $pdoBefore);

        $this->makeMigrator()->migrate();

        $this->assertSame($pdoBefore, $this->xpdo->pdo);
    }
}
