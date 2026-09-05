<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Migrations;

use PDO;
use xPDO\Migrations\Exception\MigrationException;
use xPDO\Migrations\Exception\MigrationLockedException;
use xPDO\xPDO;

/**
 * @internal Not a stable extension API. Prefer Migrator / Migration / MigrationContext / MigrationConfig.
 */
class MigrationLock
{
    /** @var xPDO */
    private $xpdo;

    /** @var MigrationConfig */
    private $config;

    /** @var bool */
    private $held = false;

    /** @var string|null */
    private $mysqlKey;

    /** @var array{0:int,1:int}|null */
    private $pgsqlKeys;

    /** @var resource|null */
    private $sqliteLockHandle;

    public function __construct(xPDO $xpdo, MigrationConfig $config)
    {
        $this->xpdo = $xpdo;
        $this->config = $config;
    }

    public function acquire(): void
    {
        if ($this->held) {
            return;
        }
        if (!$this->xpdo->connect(null, [xPDO::OPT_CONN_MUTABLE => true])) {
            throw new MigrationException('Could not obtain a mutable database connection for locking');
        }

        $dbtype = $this->xpdo->getOption('dbtype');
        switch ($dbtype) {
            case 'mysql':
                $this->acquireMysql();
                break;
            case 'pgsql':
                $this->acquirePgsql();
                break;
            case 'sqlite':
                $this->acquireSqlite();
                break;
            default:
                throw new MigrationException("Migration locking is not supported for dbtype {$dbtype}");
        }
        $this->held = true;
    }

    public function release(): void
    {
        if (!$this->held) {
            return;
        }
        $dbtype = $this->xpdo->getOption('dbtype');
        try {
            switch ($dbtype) {
                case 'mysql':
                    if ($this->mysqlKey !== null && $this->xpdo->pdo) {
                        $stmt = $this->xpdo->prepare('SELECT RELEASE_LOCK(?)');
                        if ($stmt) {
                            $stmt->execute([$this->mysqlKey]);
                        }
                    }
                    break;
                case 'pgsql':
                    if ($this->pgsqlKeys !== null && $this->xpdo->pdo) {
                        $stmt = $this->xpdo->prepare('SELECT pg_advisory_unlock(?, ?)');
                        if ($stmt) {
                            $stmt->execute([$this->pgsqlKeys[0], $this->pgsqlKeys[1]]);
                        }
                    }
                    break;
                case 'sqlite':
                    if ($this->sqliteLockHandle) {
                        flock($this->sqliteLockHandle, LOCK_UN);
                        fclose($this->sqliteLockHandle);
                        $this->sqliteLockHandle = null;
                    }
                    break;
            }
        } finally {
            $this->held = false;
            $this->mysqlKey = null;
            $this->pgsqlKeys = null;
            $this->sqliteLockHandle = null;
        }
    }

    private function acquireMysql(): void
    {
        $key = substr(hash('sha256', $this->buildKey()), 0, 64);
        $stmt = $this->xpdo->prepare('SELECT GET_LOCK(?, 0)');
        if ($stmt === false || $stmt->execute([$key]) === false) {
            throw new MigrationException('GET_LOCK failed');
        }
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if (!$row || (int) $row[0] !== 1) {
            throw new MigrationLockedException('Could not acquire migration lock');
        }
        $this->mysqlKey = $key;
    }

    private function acquirePgsql(): void
    {
        $hash = hash('sha256', $this->buildKey(), true);
        $k1 = unpack('N', substr($hash, 0, 4))[1] & 0x7fffffff;
        $k2 = unpack('N', substr($hash, 4, 4))[1] & 0x7fffffff;
        $stmt = $this->xpdo->prepare('SELECT pg_try_advisory_lock(?, ?)');
        if ($stmt === false || $stmt->execute([$k1, $k2]) === false) {
            throw new MigrationException('pg_try_advisory_lock failed');
        }
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if (!$row || !($row[0] === true || $row[0] === 't' || (int) $row[0] === 1)) {
            throw new MigrationLockedException('Could not acquire migration lock');
        }
        $this->pgsqlKeys = [$k1, $k2];
    }

    private function acquireSqlite(): void
    {
        $dir = $this->config->getMigrationsPath();
        $file = $dir . DIRECTORY_SEPARATOR . '.xpdo_migration.lock';
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new MigrationException('Could not open sqlite migration lock file');
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new MigrationLockedException('Could not acquire migration lock');
        }
        $this->sqliteLockHandle = $handle;
    }

    private function buildKey(): string
    {
        $parts = [
            'xpdo_migrations',
            $this->config->getMigrationsTable(),
            (string) $this->xpdo->getOption('dbname', null, ''),
            (string) $this->xpdo->getOption('dsn', null, ''),
        ];
        if ($this->config->getMigrationsLockName()) {
            $parts[] = $this->config->getMigrationsLockName();
        }
        return implode('|', $parts);
    }
}
