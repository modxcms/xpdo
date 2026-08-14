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

use DateTimeImmutable;
use DateTimeZone;
use xPDO\Migrations\Exception\ConfigurationException;
use xPDO\Migrations\MigrationGenerator;

class MigrationGeneratorUniquenessTest extends MigrationTestCase
{
    public function testCreateWritesStubWithExpectedClassName()
    {
        $generator = new MigrationGenerator($this->makeConfig());
        $name = $generator->create('CreateWidgetTable');

        $this->assertMatchesRegularExpression('/^[0-9]{14}_CreateWidgetTable$/', $name);
        $path = $this->migrationsDir . '/' . $name . '.php';
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertStringContainsString('namespace ' . $this->migrationsNamespace . ';', $contents);
        $this->assertStringContainsString('class M' . $name . ' extends Migration', $contents);
    }

    public function testCreateSkipsOccupiedTimestampInsteadOfOverwrite()
    {
        $generator = new MigrationGenerator($this->makeConfig());
        $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $occupied = $dt->format('YmdHis') . '_SameSecond';
        file_put_contents($this->migrationsDir . '/' . $occupied . '.php', "<?php // occupied\n");

        $name = $generator->create('SameSecond');
        $this->assertNotSame($occupied, $name);
        $this->assertMatchesRegularExpression('/^[0-9]{14}_SameSecond$/', $name);
        $this->assertFileExists($this->migrationsDir . '/' . $name . '.php');
        $this->assertSame(
            "<?php // occupied\n",
            file_get_contents($this->migrationsDir . '/' . $occupied . '.php')
        );
    }

    public function testCreateRejectsInvalidDescription()
    {
        $generator = new MigrationGenerator($this->makeConfig());
        $this->expectException(ConfigurationException::class);
        $generator->create('123Invalid');
    }
}
