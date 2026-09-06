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

use xPDO\xPDOException;

/**
 * Base exception for the migrations subsystem.
 *
 * Catch this for any migrator/CLI failure. Prefer more specific subclasses when
 * you need to branch on lock, config, invalid files, or irreversible down().
 *
 * @package xPDO\Migrations\Exception
 */
class MigrationException extends xPDOException
{
}
