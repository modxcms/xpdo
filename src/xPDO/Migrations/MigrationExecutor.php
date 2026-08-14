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
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Throwable;
use xPDO\Migrations\Exception\IrreversibleMigrationException;
use xPDO\Migrations\Exception\MigrationException;
use xPDO\xPDO;

/**
 * @internal Not a stable extension API. Prefer Migrator / Migration / MigrationContext / MigrationConfig.
 */
class MigrationExecutor
{
    /** @var xPDO */
    private $xpdo;

    /** @var MigrationConfig */
    private $config;

    /** @var MigrationRepository */
    private $repository;

    /** @var LoggerInterface */
    private $logger;

    /** @var callable|null */
    private $ensurePinnedConnection;

    public function __construct(
        xPDO $xpdo,
        MigrationConfig $config,
        MigrationRepository $repository,
        LoggerInterface $logger,
        ?callable $ensurePinnedConnection = null
    ) {
        $this->xpdo = $xpdo;
        $this->config = $config;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->ensurePinnedConnection = $ensurePinnedConnection;
    }

    public function applyUp(Migration $migration, string $name, int $batch): void
    {
        $context = new MigrationContext($this->xpdo, $this->logger, $name, $this->config);
        $transactional = $this->resolveTransactional($migration);

        $this->runInPolicy($transactional, $name, 'up', function () use ($migration, $context, $name, $batch) {
            $migration->up($context);
            // Ledger write must use the same pinned connection as the migration run.
            $this->restorePinnedConnection();
            $this->repository->insert($name, $batch, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        });
    }

    public function applyDown(Migration $migration, string $name): void
    {
        $context = new MigrationContext($this->xpdo, $this->logger, $name, $this->config);
        $transactional = $this->resolveTransactional($migration);

        $this->runInPolicy($transactional, $name, 'down', function () use ($migration, $context, $name) {
            $migration->down($context);
            $this->restorePinnedConnection();
            $this->repository->delete($name);
        });
    }

    private function restorePinnedConnection(): void
    {
        if ($this->ensurePinnedConnection !== null) {
            ($this->ensurePinnedConnection)();
        }
    }

    private function resolveTransactional(Migration $migration): bool
    {
        $override = $migration->isTransactional();
        if ($override !== null) {
            return $override;
        }
        return $this->config->isTransactionalDefault();
    }

    private function runInPolicy(bool $transactional, string $name, string $direction, callable $fn): void
    {
        if (!$this->xpdo->connect(null, [xPDO::OPT_CONN_MUTABLE => true])) {
            throw new MigrationException('Could not obtain a mutable database connection');
        }

        $alreadyInTxn = $this->xpdo->pdo && $this->xpdo->pdo->inTransaction();
        $opened = false;

        if ($transactional && !$alreadyInTxn) {
            if ($this->xpdo->beginTransaction() === false) {
                throw new MigrationException(
                    "Could not begin transaction before migration {$name} ({$direction})"
                );
            }
            $opened = true;
        }

        try {
            $fn();
            if ($opened) {
                if ($this->xpdo->commit() === false) {
                    throw new MigrationException(
                        "Could not commit transaction after migration {$name} ({$direction})"
                    );
                }
            }
        } catch (IrreversibleMigrationException $e) {
            if ($opened && $this->xpdo->pdo && $this->xpdo->pdo->inTransaction()) {
                $this->xpdo->rollBack();
            }
            throw $e;
        } catch (MigrationException $e) {
            if ($opened && $this->xpdo->pdo && $this->xpdo->pdo->inTransaction()) {
                $this->xpdo->rollBack();
            }
            throw $e;
        } catch (Throwable $e) {
            if ($opened && $this->xpdo->pdo && $this->xpdo->pdo->inTransaction()) {
                $this->xpdo->rollBack();
            }
            throw new MigrationException(
                "Migration {$name} failed during {$direction}: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }
}
