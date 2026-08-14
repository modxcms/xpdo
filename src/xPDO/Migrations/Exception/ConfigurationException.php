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
 * Thrown for invalid migration configuration.
 *
 * Typical cases: missing/invalid migrations_path or namespace, remote path
 * wrappers, bad migrations_table / lock name, unsupported transaction_policy.
 *
 * @package xPDO\Migrations\Exception
 */
class ConfigurationException extends MigrationException
{
}
