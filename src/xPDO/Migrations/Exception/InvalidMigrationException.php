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
 * Thrown when a migration file or class cannot be used.
 *
 * Typical cases: bad name pattern, unreadable file, missing FQCN after load,
 * class does not extend Migration, duplicate discovered name.
 *
 * @package xPDO\Migrations\Exception
 */
class InvalidMigrationException extends MigrationException
{
}
