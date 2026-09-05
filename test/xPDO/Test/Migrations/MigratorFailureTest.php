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

use xPDO\Migrations\Exception\MigrationException;
use xPDO\Migrations\MigratorResult;

class MigratorFailureTest extends MigrationTestCase
{
    public function testFailedUpDoesNotRecordAndStopsBatch()
    {
        $ok = '20260102120000_OkOne';
        $bad = '20260102120100_BadTwo';
        $later = '20260102120200_LaterThree';

        $this->writeMigration($ok);
        $this->writeMigration($bad, 'throw new \\RuntimeException("boom");');
        $this->writeMigration($later);

        $migrator = $this->makeMigrator();

        try {
            $migrator->migrate();
            $this->fail('Expected MigrationException');
        } catch (MigrationException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
            $this->assertStringContainsString('20260102120100_BadTwo', $e->getMessage());
            $this->assertStringContainsString('up', $e->getMessage());
        }

        $status = $migrator->status();
        $this->assertSame(MigratorResult::REPOSITORY_OK, $status->repositoryState);
        $this->assertSame([$ok], $status->applied);
        $this->assertSame([$bad, $later], $status->pending);
    }
}
