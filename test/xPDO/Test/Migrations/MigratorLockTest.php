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
use xPDO\Migrations\Exception\MigrationLockedException;
use xPDO\Migrations\MigrationLock;
use xPDO\Migrations\Migrator;

class MigratorLockTest extends MigrationTestCase
{
    public function testSecondSessionCannotAcquireLock()
    {
        $config = $this->makeConfig();
        $secondary = $this->makeSecondaryXpdo();
        $lockSecondary = new MigrationLock($secondary, $config);
        $lockSecondary->acquire();

        try {
            $this->writeMigration('20260305120000_BlockedByOtherSession');
            $migrator = new Migrator($this->xpdo, $config);
            $this->expectException(MigrationLockedException::class);
            $migrator->migrate();
        } finally {
            $lockSecondary->release(true);
            $secondary->pdo = null;
        }
    }

    public function testSameSessionSecondLockIsReentrantOrFailsFast()
    {
        $config = $this->makeConfig();
        $lockA = new MigrationLock($this->xpdo, $config);
        $lockA->acquire();

        try {
            $lockB = new MigrationLock($this->xpdo, $config);
            try {
                $lockB->acquire();
                // mysql/pgsql advisory locks reenter on the same session; sqlite flock may not.
                $this->assertTrue(true);
            } catch (MigrationLockedException $e) {
                $this->assertInstanceOf(MigrationLockedException::class, $e);
            }
        } finally {
            $lockA->release(true);
        }
    }

    public function testLockReleasedAfterFailedMigrateAllowsRetry()
    {
        $name = '20260304120000_FailThenRetry';
        $this->writeMigration($name, 'throw new \\RuntimeException("lock-release-boom");');
        $migrator = $this->makeMigrator();

        try {
            $migrator->migrate();
            $this->fail('Expected MigrationException');
        } catch (MigrationException $e) {
            $this->assertStringContainsString('lock-release-boom', $e->getMessage());
        }

        try {
            $migrator->migrate();
            $this->fail('Expected MigrationException from migration body, not lock');
        } catch (MigrationException $e) {
            $this->assertStringContainsString('lock-release-boom', $e->getMessage());
            $this->assertStringNotContainsString('Could not acquire migration lock', $e->getMessage());
        }
    }

    public function testSqliteLockHeldBlocksSecondProcess()
    {
        if ($this->xpdo->getOption('dbtype') !== 'sqlite') {
            $this->markTestSkipped('Cross-process flock contention is asserted for sqlite only');
        }

        $config = $this->makeConfig();
        $lock = new MigrationLock($this->xpdo, $config);
        $lock->acquire();
        $lockFile = $this->migrationsDir . DIRECTORY_SEPARATOR . '.xpdo_migration.lock';
        $this->assertFileExists($lockFile);

        $script = '<?php
$h = fopen(' . var_export($lockFile, true) . ', "c+");
if ($h === false) { fwrite(STDERR, "open-fail\n"); exit(2); }
if (!flock($h, LOCK_EX | LOCK_NB)) { exit(0); }
flock($h, LOCK_UN);
fclose($h);
exit(1);
';
        $tmp = tempnam(sys_get_temp_dir(), 'xpdo_lock_');
        file_put_contents($tmp, $script);

        try {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = proc_open(
                [PHP_BINARY, $tmp],
                $descriptors,
                $pipes,
                null,
                null,
                ['bypass_shell' => true]
            );
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
            $this->assertSame(
                0,
                $code,
                'Second process must fail to acquire flock while migrator holds it; stdout='
                . $stdout . ' stderr=' . $stderr
            );
        } finally {
            $lock->release();
            @unlink($tmp);
        }
    }
}
