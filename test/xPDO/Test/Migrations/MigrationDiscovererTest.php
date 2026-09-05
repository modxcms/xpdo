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

use xPDO\Migrations\Exception\InvalidMigrationException;
use xPDO\Migrations\Migration;
use xPDO\Migrations\MigrationDiscoverer;

class MigrationDiscovererTest extends MigrationTestCase
{
    public function testValidMigrationLoads()
    {
        $name = '20260103110000_ValidOne';
        $this->writeMigration($name);
        $discoverer = new MigrationDiscoverer($this->makeConfig());
        $migration = $discoverer->load($name);
        $this->assertInstanceOf(Migration::class, $migration);
    }

    public function testInvalidClassFailsLoad()
    {
        $name = '20260103120000_Broken';
        $file = $this->migrationsDir . '/' . $name . '.php';
        file_put_contents($file, "<?php\nnamespace {$this->migrationsNamespace};\nclass M{$name} {}\n");

        $discoverer = new MigrationDiscoverer($this->makeConfig());
        $this->expectException(InvalidMigrationException::class);
        $discoverer->load($name);
    }

    public function testDiscoverIgnoresNonMatchingFilesAndOrdersByName()
    {
        file_put_contents($this->migrationsDir . '/readme.php', "<?php\n");
        file_put_contents($this->migrationsDir . '/20260103120100_bad-name.php', "<?php\n");
        $subdir = $this->migrationsDir . '/subdir';
        mkdir($subdir);
        file_put_contents($subdir . '/20260103120300_Nested.php', "<?php\n");

        $this->writeMigration('20260103120200_Second');
        $this->writeMigration('20260103120100_First');

        try {
            $discoverer = new MigrationDiscoverer($this->makeConfig());
            $found = $discoverer->discover();
            $this->assertSame(
                ['20260103120100_First', '20260103120200_Second'],
                array_column($found, 'name')
            );
        } finally {
            @unlink($subdir . '/20260103120300_Nested.php');
            @rmdir($subdir);
        }
    }

    public function testLoadMissingFileFails()
    {
        $discoverer = new MigrationDiscoverer($this->makeConfig());
        $this->expectException(InvalidMigrationException::class);
        $discoverer->load('20260103129900_Missing');
    }

    public function testLoadInvalidNameFails()
    {
        $discoverer = new MigrationDiscoverer($this->makeConfig());
        $this->expectException(InvalidMigrationException::class);
        $discoverer->load('not-a-valid-name');
    }
}
