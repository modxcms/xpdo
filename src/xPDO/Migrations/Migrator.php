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

use Psr\Log\LoggerInterface;
use xPDO\Migrations\Exception\MigrationException;
use xPDO\xPDO;
use xPDO\xPDOConnection;

/**
 * Public entry point for applying, inspecting, creating, and rolling back migrations.
 *
 * Stable surface for consumers: this class, Migration, MigrationContext, MigrationConfig,
 * and MigratorResult DTOs returned by these methods.
 */
class Migrator
{
    /** @var xPDO */
    private $xpdo;

    /** @var MigrationConfig */
    private $config;

    /** @var MigrationRepository */
    private $repository;

    /** @var MigrationDiscoverer */
    private $discoverer;

    /** @var MigrationExecutor */
    private $executor;

    /** @var MigrationLock */
    private $lock;

    /** @var MigrationGenerator */
    private $generator;

    /** @var LoggerInterface */
    private $logger;

    /** @var mixed */
    private $previousAutoCreate;

    /** @var xPDOConnection|null */
    private $pinnedConnection = null;

    /** @var \PDO|null */
    private $pinnedPdo = null;

    public function __construct(xPDO $xpdo, MigrationConfig $config, ?LoggerInterface $logger = null)
    {
        $this->xpdo = $xpdo;
        $this->config = $config;
        $this->logger = $logger ?: $this->resolveLogger($xpdo);
        $this->repository = new MigrationRepository($xpdo, $config);
        $this->discoverer = new MigrationDiscoverer($config);
        $this->executor = new MigrationExecutor(
            $xpdo,
            $config,
            $this->repository,
            $this->logger,
            function (): void {
                $this->restorePinnedConnection();
            }
        );
        $this->lock = new MigrationLock($xpdo, $config);
        $this->generator = new MigrationGenerator($config);
    }

    /**
     * Inspect applied / pending / orphaned versions. Does not create the ledger table.
     */
    public function status(): MigratorResult
    {
        if (!$this->repository->exists()) {
            return new MigratorResult(MigratorResult::REPOSITORY_MISSING);
        }

        $result = new MigratorResult(MigratorResult::REPOSITORY_OK);
        $result->applied = $this->repository->fetchAppliedVersions();
        $discovered = $this->discoverer->discover();
        $appliedMap = array_fill_keys($result->applied, true);
        $discoveredMap = [];
        foreach ($discovered as $item) {
            $discoveredMap[$item['name']] = true;
            if (!isset($appliedMap[$item['name']])) {
                $result->pending[] = $item['name'];
            }
        }
        foreach ($result->applied as $version) {
            if (!isset($discoveredMap[$version])) {
                $result->orphaned[] = $version;
            }
        }
        return $result;
    }

    /**
     * Apply pending migrations in ascending name order. Optional $step caps how many.
     * Failed up() is not recorded; later pending in the same run are not attempted.
     */
    public function migrate(?int $step = null): MigratorResult
    {
        $this->beginPinnedRun();
        $this->lock->acquire();
        try {
            return $this->withAutoCreateDisabled(function () use ($step) {
                $this->repository->ensure();
                $discovered = $this->discoverer->discover();
                $applied = array_fill_keys($this->repository->fetchAppliedVersions(), true);

                $pending = [];
                foreach ($discovered as $item) {
                    if (!isset($applied[$item['name']])) {
                        $pending[] = $item;
                    }
                }

                $result = new MigratorResult(MigratorResult::REPOSITORY_OK);
                if ($pending === []) {
                    $result->applied = [];
                    $result->pending = [];
                    return $result;
                }

                if ($step !== null) {
                    if ($step < 1) {
                        throw new MigrationException('step must be a positive integer');
                    }
                    $pending = array_slice($pending, 0, $step);
                }

                $batch = $this->repository->nextBatch();
                $result->batch = $batch;
                $appliedNow = [];

                foreach ($pending as $item) {
                    $this->restorePinnedConnection();
                    $migration = $this->discoverer->load($item['name']);
                    $this->executor->applyUp($migration, $item['name'], $batch);
                    $appliedNow[] = $item['name'];
                }

                $result->applied = $appliedNow;
                $status = $this->status();
                $result->pending = $status->pending;
                return $result;
            });
        } finally {
            $this->lock->release();
            $this->endPinnedRun();
        }
    }

    /**
     * Write a new migration stub on disk. Does not require a live database connection.
     */
    public function create(string $description): MigratorResult
    {
        $name = $this->generator->create($description);
        // File generation must not require a live database connection.
        $result = new MigratorResult(MigratorResult::REPOSITORY_MISSING);
        $result->created = $name;
        $result->messages[] = 'Created ' . $this->config->getMigrationsPath() . DIRECTORY_SEPARATOR . $name . '.php';
        return $result;
    }

    /**
     * Reverse rows with batch = MAX(batch), highest version first. Optional $step caps count.
     * Failed down() leaves the ledger row in place.
     */
    public function rollback(?int $step = null): MigratorResult
    {
        $this->beginPinnedRun();
        $this->lock->acquire();
        try {
            return $this->withAutoCreateDisabled(function () use ($step) {
                $this->repository->ensure();

                $result = new MigratorResult(MigratorResult::REPOSITORY_OK);
                $maxBatch = $this->repository->maxBatch();
                if ($maxBatch < 1) {
                    $status = $this->status();
                    $result->applied = $status->applied;
                    $result->pending = $status->pending;
                    return $result;
                }

                $rows = $this->repository->fetchByBatch($maxBatch);
                if ($step !== null) {
                    if ($step < 1) {
                        throw new MigrationException('step must be a positive integer');
                    }
                    $rows = array_slice($rows, 0, $step);
                }

                $result->batch = $maxBatch;
                $rolled = [];
                foreach ($rows as $row) {
                    $this->restorePinnedConnection();
                    $name = $row['version'];
                    $migration = $this->discoverer->load($name);
                    $this->executor->applyDown($migration, $name);
                    $rolled[] = $name;
                }

                $result->rolledBack = $rolled;
                $status = $this->status();
                $result->applied = $status->applied;
                $result->pending = $status->pending;
                return $result;
            });
        } finally {
            $this->lock->release();
            $this->endPinnedRun();
        }
    }

    private function beginPinnedRun(): void
    {
        if (!$this->xpdo->connect(null, [xPDO::OPT_CONN_MUTABLE => true])) {
            throw new MigrationException('Could not obtain a mutable database connection');
        }
        $this->pinnedConnection = $this->xpdo->connection;
        $this->pinnedPdo = $this->xpdo->pdo;
        if (!($this->pinnedConnection instanceof xPDOConnection) || !($this->pinnedPdo instanceof \PDO)) {
            throw new MigrationException('Could not pin a mutable PDO connection for migrations');
        }
    }

    private function restorePinnedConnection(): void
    {
        if ($this->pinnedConnection instanceof xPDOConnection) {
            $this->xpdo->connection = $this->pinnedConnection;
            $this->xpdo->pdo = $this->pinnedPdo;
        }
    }

    private function endPinnedRun(): void
    {
        $this->restorePinnedConnection();
        $this->pinnedConnection = null;
        $this->pinnedPdo = null;
    }

    private function withAutoCreateDisabled(callable $fn)
    {
        $this->previousAutoCreate = $this->xpdo->getOption(xPDO::OPT_AUTO_CREATE_TABLES);
        $this->xpdo->setOption(xPDO::OPT_AUTO_CREATE_TABLES, false);
        try {
            return $fn();
        } finally {
            $this->xpdo->setOption(xPDO::OPT_AUTO_CREATE_TABLES, $this->previousAutoCreate);
        }
    }

    private function resolveLogger(xPDO $xpdo): LoggerInterface
    {
        if ($xpdo->logger instanceof LoggerInterface && !($xpdo->logger instanceof \xPDO\Logging\xPDOLogger)) {
            return $xpdo->logger;
        }
        return new MigrationPsrLogger($xpdo);
    }
}
