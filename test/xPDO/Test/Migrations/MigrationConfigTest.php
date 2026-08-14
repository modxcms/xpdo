<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Migrations;

use xPDO\Migrations\Exception\ConfigurationException;
use xPDO\Migrations\MigrationConfig;

class MigrationConfigTest extends MigrationTestCase
{
    public function testInvalidTableNameRejected()
    {
        $this->expectException(ConfigurationException::class);
        $this->makeConfig(['migrations_table' => 'xpdo_migrations;drop']);
    }

    public function testRemotePathRejected()
    {
        $this->expectException(ConfigurationException::class);
        MigrationConfig::fromArray([
            'migrations_path' => 'phar://archive.phar/migrations',
            'migrations_namespace' => 'App\\Migrations',
        ]);
    }
}
