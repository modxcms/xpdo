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

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PDO;
use xPDO\Migrations\Exception\MigrationException;
use xPDO\xPDO;

/**
 * @internal Not a stable extension API. Prefer Migrator / Migration / MigrationContext / MigrationConfig.
 */
class MigrationRepository
{
    /** @var xPDO */
    private $xpdo;

    /** @var MigrationConfig */
    private $config;

    public function __construct(xPDO $xpdo, MigrationConfig $config)
    {
        $this->xpdo = $xpdo;
        $this->config = $config;
    }

    public function exists(): bool
    {
        try {
            $this->pinConnection();
        } catch (\Throwable $e) {
            return false;
        }
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        $dbtype = $this->xpdo->getOption('dbtype');

        try {
            switch ($dbtype) {
                case 'sqlite':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=" . $this->xpdo->quote($this->config->getMigrationsTable());
                    $stmt = $this->xpdo->query($sql);
                    break;
                case 'pgsql':
                    $sql = "SELECT 1 FROM information_schema.tables WHERE table_name = " . $this->xpdo->quote($this->config->getMigrationsTable()) . " AND table_schema = current_schema()";
                    $stmt = $this->xpdo->query($sql);
                    break;
                case 'mysql':
                default:
                    $sql = "SELECT 1 FROM {$table} LIMIT 1";
                    $stmt = $this->xpdo->query($sql);
                    break;
            }
            if ($stmt === false) {
                return false;
            }
            if ($dbtype === 'sqlite' || $dbtype === 'pgsql') {
                $row = $stmt->fetch(PDO::FETCH_NUM);
                return !empty($row);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @throws MigrationException
     */
    public function ensure(): void
    {
        if ($this->exists()) {
            return;
        }
        $this->pinConnection();
        $dbtype = $this->xpdo->getOption('dbtype');
        $sql = $this->createTableSql($dbtype);
        if ($this->runWrite($sql) === false) {
            throw new MigrationException('Could not create migrations repository table: ' . json_encode($this->xpdo->errorInfo()));
        }
        if ($dbtype === 'sqlite' || $dbtype === 'pgsql') {
            $idx = 'CREATE INDEX IF NOT EXISTS ' . $this->quoteIdent($this->config->getMigrationsTable() . '_batch')
                . ' ON ' . $this->quoteIdent($this->config->getMigrationsTable()) . ' (batch)';
            $this->runWrite($idx);
        }
    }

    /**
     * @return string[]
     */
    public function fetchAppliedVersions(): array
    {
        $this->pinConnection();
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        $stmt = $this->xpdo->query("SELECT version FROM {$table} ORDER BY version ASC");
        if ($stmt === false) {
            throw new MigrationException('Could not read applied migrations');
        }
        $versions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $versions[] = $row['version'];
        }
        return $versions;
    }

    /**
     * @return array<int, array{version:string,batch:int,applied_at:string}>
     */
    public function fetchByBatch(int $batch): array
    {
        $this->pinConnection();
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        $stmt = $this->xpdo->prepare("SELECT version, batch, applied_at FROM {$table} WHERE batch = ? ORDER BY version DESC");
        if ($stmt === false || $stmt->execute([$batch]) === false) {
            throw new MigrationException('Could not read migrations for batch');
        }
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'version' => $row['version'],
                'batch' => (int) $row['batch'],
                'applied_at' => $row['applied_at'],
            ];
        }
        return $rows;
    }

    public function maxBatch(): int
    {
        $this->pinConnection();
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        $stmt = $this->xpdo->query("SELECT MAX(batch) AS m FROM {$table}");
        if ($stmt === false) {
            return 0;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['m'] === null) {
            return 0;
        }
        return (int) $row['m'];
    }

    public function nextBatch(): int
    {
        return $this->maxBatch() + 1;
    }

    public function insert(string $version, int $batch, DateTimeInterface $at): void
    {
        $this->pinConnection();
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        $utc = DateTimeImmutable::createFromInterface($at)->setTimezone(new DateTimeZone('UTC'));
        $appliedAt = $utc->format('Y-m-d H:i:s');
        $stmt = $this->xpdo->prepare("INSERT INTO {$table} (version, batch, applied_at) VALUES (?, ?, ?)");
        if ($stmt === false || $stmt->execute([$version, $batch, $appliedAt]) === false) {
            throw new MigrationException('Could not record migration ' . $version);
        }
    }

    public function delete(string $version): void
    {
        $this->pinConnection();
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        $stmt = $this->xpdo->prepare("DELETE FROM {$table} WHERE version = ?");
        if ($stmt === false || $stmt->execute([$version]) === false) {
            throw new MigrationException('Could not remove migration record ' . $version);
        }
    }

    /**
     * @return int|false
     */
    private function runWrite(string $sql)
    {
        return $this->xpdo->{'exec'}($sql);
    }

    private function createTableSql(string $dbtype): string
    {
        $table = $this->quoteIdent($this->config->getMigrationsTable());
        switch ($dbtype) {
            case 'pgsql':
                return "CREATE TABLE {$table} ("
                    . "id SERIAL PRIMARY KEY, "
                    . "version VARCHAR(191) NOT NULL UNIQUE, "
                    . "batch INTEGER NOT NULL, "
                    . "applied_at TIMESTAMP NOT NULL"
                    . ")";
            case 'sqlite':
                return "CREATE TABLE {$table} ("
                    . "id INTEGER PRIMARY KEY AUTOINCREMENT, "
                    . "version VARCHAR(191) NOT NULL UNIQUE, "
                    . "batch INTEGER NOT NULL, "
                    . "applied_at TEXT NOT NULL"
                    . ")";
            case 'mysql':
            default:
                $batchIndex = $this->quoteIdent($this->config->getMigrationsTable() . '_batch');
                return "CREATE TABLE {$table} ("
                    . "id INT NOT NULL AUTO_INCREMENT, "
                    . "version VARCHAR(191) NOT NULL, "
                    . "batch INT NOT NULL, "
                    . "applied_at DATETIME NOT NULL, "
                    . "PRIMARY KEY (id), "
                    . "UNIQUE KEY version (version), "
                    . "KEY {$batchIndex} (batch)"
                    . ") DEFAULT CHARSET=utf8mb4";
        }
    }

    private function quoteIdent(string $ident): string
    {
        MigrationConfig::assertValidTableName($ident);
        return $this->xpdo->escape($ident);
    }

    private function pinConnection(): void
    {
        if (!$this->xpdo->connect(null, [xPDO::OPT_CONN_MUTABLE => true])) {
            throw new MigrationException('Could not obtain a mutable database connection');
        }
    }
}
