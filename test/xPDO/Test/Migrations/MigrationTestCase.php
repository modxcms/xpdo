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

use xPDO\Migrations\MigrationConfig;
use xPDO\Migrations\Migrator;
use xPDO\TestCase;

abstract class MigrationTestCase extends TestCase
{
    /** @var string */
    protected $migrationsDir;

    /** @var string */
    protected $migrationsTable;

    /** @var string */
    protected $migrationsNamespace = 'xPDO\\Test\\Migrations\\Fixture';

    /**
     * @before
     */
    public function setUpMigrationFixtures()
    {
        $this->migrationsDir = sys_get_temp_dir() . '/xpdo_migrations_' . uniqid('', true);
        mkdir($this->migrationsDir, 0777, true);
        $this->migrationsTable = 'xpdo_mig_' . substr(md5(uniqid('', true)), 0, 8);
    }

    /**
     * @after
     */
    public function tearDownMigrationFixtures()
    {
        if ($this->xpdo && $this->migrationsTable) {
            $this->dropSideTable();
            try {
                $escaped = $this->xpdo->escape($this->migrationsTable);
                $this->xpdo->{'exec'}("DROP TABLE IF EXISTS {$escaped}");
            } catch (\Throwable $e) {
                // ignore
            }
        }
        if ($this->migrationsDir && is_dir($this->migrationsDir)) {
            foreach (glob($this->migrationsDir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->migrationsDir);
        }
    }

    protected function makeConfig(array $overrides = []): MigrationConfig
    {
        return MigrationConfig::fromArray(array_merge([
            'migrations_path' => $this->migrationsDir,
            'migrations_namespace' => $this->migrationsNamespace,
            'migrations_table' => $this->migrationsTable,
            'transaction_policy' => MigrationConfig::POLICY_NON_TRANSACTIONAL,
        ], $overrides));
    }

    protected function makeMigrator(array $configOverrides = []): Migrator
    {
        return new Migrator($this->xpdo, $this->makeConfig($configOverrides));
    }

    /**
     * Second xPDO instance with its own PDO session (same DSN/options).
     */
    protected function makeSecondaryXpdo(): \xPDO\xPDO
    {
        $driver = self::$properties['xpdo_driver'];
        $options = self::$properties["{$driver}_array_options"];
        $name = 'migrations_secondary_' . str_replace('.', '', uniqid('', true));
        $xpdo = \xPDO\xPDO::getInstance($name, $options);
        $this->assertInstanceOf(\xPDO\xPDO::class, $xpdo);
        $xpdo->setLogLevel(\xPDO\xPDO::LOG_LEVEL_ERROR);
        $xpdo->setLogTarget('ECHO');
        return $xpdo;
    }

    protected function writeMigration(
        string $name,
        string $upBody = '',
        string $downBody = '',
        ?bool $transactional = null
    ): string {
        $class = 'M' . $name;
        $upBody = $upBody !== '' ? $upBody : '// no-op';
        $downBody = $downBody !== '' ? $downBody : '// no-op';
        $txnMethod = '';
        if ($transactional !== null) {
            $val = $transactional ? 'true' : 'false';
            $txnMethod = <<<PHP

    public function isTransactional(): ?bool
    {
        return {$val};
    }
PHP;
        }
        $code = <<<PHP
<?php
namespace {$this->migrationsNamespace};

use xPDO\\Migrations\\Migration;
use xPDO\\Migrations\\MigrationContext;

class {$class} extends Migration
{
    public function up(MigrationContext \$context): void
    {
        {$upBody}
    }

    public function down(MigrationContext \$context): void
    {
        {$downBody}
    }
{$txnMethod}
}
PHP;
        $file = $this->migrationsDir . '/' . $name . '.php';
        file_put_contents($file, $code);
        return $file;
    }

    protected function sideTable(): string
    {
        return $this->migrationsTable . '_side';
    }

    protected function ensureSideTable(): void
    {
        $table = $this->xpdo->escape($this->sideTable());
        $dbtype = $this->xpdo->getOption('dbtype');
        if ($dbtype === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS {$table} (id INTEGER PRIMARY KEY, note VARCHAR(64) NOT NULL)";
        } elseif ($dbtype === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$table} (id INTEGER PRIMARY KEY, note TEXT NOT NULL)";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS {$table} (id INT NOT NULL PRIMARY KEY, note VARCHAR(64) NOT NULL) DEFAULT CHARSET=utf8mb4";
        }
        $this->xpdo->{'exec'}($sql);
    }

    protected function dropSideTable(): void
    {
        if (!$this->xpdo) {
            return;
        }
        $table = $this->xpdo->escape($this->sideTable());
        try {
            $this->xpdo->{'exec'}("DROP TABLE IF EXISTS {$table}");
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
