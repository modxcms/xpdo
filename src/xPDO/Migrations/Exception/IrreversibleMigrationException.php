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
 * Thrown from down() when the migration cannot be reversed.
 *
 * The ledger row stays applied. Fix the migration or restore the file, then
 * re-run migrate-rollback.
 *
 * @package xPDO\Migrations\Exception
 */
class IrreversibleMigrationException extends MigrationException
{
}
