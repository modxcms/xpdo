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
use xPDO\xPDO;

final class MigrationContext
{
    /** @var xPDO */
    private $xpdo;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $migrationName;

    /** @var MigrationConfig */
    private $config;

    public function __construct(xPDO $xpdo, LoggerInterface $logger, string $migrationName, MigrationConfig $config)
    {
        $this->xpdo = $xpdo;
        $this->logger = $logger;
        $this->migrationName = $migrationName;
        $this->config = $config;
    }

    public function getXpdo(): xPDO
    {
        return $this->xpdo;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getMigrationName(): string
    {
        return $this->migrationName;
    }

    public function getConfig(): MigrationConfig
    {
        return $this->config;
    }
}
