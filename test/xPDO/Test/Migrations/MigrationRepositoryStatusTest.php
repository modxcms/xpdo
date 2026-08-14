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

use xPDO\Migrations\MigrationRepository;
use xPDO\Migrations\MigratorResult;

class MigrationRepositoryStatusTest extends MigrationTestCase
{
    public function testStatusDoesNotCreateRepository()
    {
        $migrator = $this->makeMigrator();
        $status = $migrator->status();
        $this->assertSame(MigratorResult::REPOSITORY_MISSING, $status->repositoryState);

        $repo = new MigrationRepository($this->xpdo, $this->makeConfig());
        $this->assertFalse($repo->exists());
    }
}
