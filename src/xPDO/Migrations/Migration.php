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

/**
 * Base class for user migration files. Extend and implement up()/down().
 *
 * Identity is the migration filename stem: YYYYMMDDHHMMSS_Description
 * (see MigrationDiscoverer::NAME_PATTERN). Class name is M{that stem} in the
 * configured namespace.
 */
abstract class Migration
{
    abstract public function up(MigrationContext $context): void;

    abstract public function down(MigrationContext $context): void;

    /**
     * @return bool|null true/false override; null = use global config
     */
    public function isTransactional(): ?bool
    {
        return null;
    }
}
