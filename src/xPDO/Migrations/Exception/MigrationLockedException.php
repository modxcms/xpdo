<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Migrations\Exception;

/**
 * Thrown when migrate/rollback cannot acquire the exclusive migration lock.
 *
 * MVP is fail-fast: another process holds the lock. Retry after that run ends.
 *
 * @package xPDO\Migrations\Exception
 */
class MigrationLockedException extends MigrationException
{
}
