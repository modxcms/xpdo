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

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use xPDO\xPDO;

/**
 * PSR-3 adapter that forwards to xPDO::log without using FATAL.
 */
/**
 * @internal Not a stable extension API. Prefer Migrator / Migration / MigrationContext / MigrationConfig.
 */
final class MigrationPsrLogger extends AbstractLogger
{
    /** @var xPDO */
    private $xpdo;

    public function __construct(xPDO $xpdo)
    {
        $this->xpdo = $xpdo;
    }

    public function log($level, $message, array $context = []): void
    {
        $map = [
            LogLevel::DEBUG => xPDO::LOG_LEVEL_DEBUG,
            LogLevel::INFO => xPDO::LOG_LEVEL_INFO,
            LogLevel::NOTICE => xPDO::LOG_LEVEL_INFO,
            LogLevel::WARNING => xPDO::LOG_LEVEL_WARN,
            LogLevel::ERROR => xPDO::LOG_LEVEL_ERROR,
            LogLevel::CRITICAL => xPDO::LOG_LEVEL_ERROR,
            LogLevel::ALERT => xPDO::LOG_LEVEL_ERROR,
            LogLevel::EMERGENCY => xPDO::LOG_LEVEL_ERROR,
        ];
        $xpdoLevel = $map[$level] ?? xPDO::LOG_LEVEL_INFO;
        $this->xpdo->log($xpdoLevel, (string) $message);
    }
}
