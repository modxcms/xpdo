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

use xPDO\Migrations\Exception\MigrationLockedException;
use xPDO\Migrations\MigrationLock;
use xPDO\Migrations\Migrator;

class MigratorConcurrencyTest extends MigrationTestCase
{
    public function testMigrateFailsWhenLockHeldOnSecondSession()
    {
        $this->writeMigration('20260303120000_Locked');
        $config = $this->makeConfig();
        $secondary = $this->makeSecondaryXpdo();
        $lock = new MigrationLock($secondary, $config);
        $lock->acquire();

        try {
            $migrator = new Migrator($this->xpdo, $config);
            $this->expectException(MigrationLockedException::class);
            $migrator->migrate();
        } finally {
            $lock->release(true);
            $secondary->pdo = null;
        }
    }
}
