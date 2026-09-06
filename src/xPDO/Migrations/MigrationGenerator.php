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

use DateTimeImmutable;
use DateTimeZone;
use xPDO\Migrations\Exception\ConfigurationException;

/**
 * @internal Not a stable extension API. Prefer Migrator / Migration / MigrationContext / MigrationConfig.
 */
class MigrationGenerator
{
    /** @var MigrationConfig */
    private $config;

    public function __construct(MigrationConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Create a new migration stub. Returns the migration name.
     *
     * @throws ConfigurationException
     */
    public function create(string $description): string
    {
        $description = $this->normalizeDescription($description);
        $name = $this->nextUniqueName($description);
        $class = 'M' . $name;
        $namespace = $this->config->getMigrationsNamespace();
        $path = $this->config->getMigrationsPath() . DIRECTORY_SEPARATOR . $name . '.php';
        $contents = $this->renderStub($namespace, $class);

        $handle = @fopen($path, 'x');
        if ($handle === false) {
            throw new ConfigurationException("Migration file already exists or could not be created: {$path}");
        }
        try {
            if (fwrite($handle, $contents) === false) {
                throw new ConfigurationException("Could not write migration file: {$path}");
            }
        } finally {
            fclose($handle);
        }

        return $name;
    }

    private function normalizeDescription(string $description): string
    {
        $description = preg_replace('/[^A-Za-z0-9]+/', '', $description) ?? '';
        if ($description === '' || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $description)) {
            throw new ConfigurationException(
                'Migration description must start with a letter and contain only letters and digits'
            );
        }
        return $description;
    }

    private function nextUniqueName(string $description): string
    {
        $dir = $this->config->getMigrationsPath();
        $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        for ($i = 0; $i < 1000; $i++) {
            $ts = $dt->modify('+' . $i . ' seconds')->format('YmdHis');
            $name = $ts . '_' . $description;
            if (strlen($name) > 191) {
                throw new ConfigurationException('Migration name exceeds 191 characters');
            }
            $path = $dir . DIRECTORY_SEPARATOR . $name . '.php';
            if (!file_exists($path)) {
                return $name;
            }
        }
        throw new ConfigurationException('Could not allocate a unique migration timestamp');
    }

    private function renderStub(string $namespace, string $class): string
    {
        return <<<PHP
<?php
/**
 * Auto-generated xPDO migration.
 */

namespace {$namespace};

use xPDO\Migrations\Exception\IrreversibleMigrationException;
use xPDO\Migrations\Migration;
use xPDO\Migrations\MigrationContext;

class {$class} extends Migration
{
    public function up(MigrationContext \$context): void
    {
        // Put schema/data changes here (\$context->getXpdo()).
        // Global default is non_transactional: MySQL DDL is not atomic.
    }

    public function down(MigrationContext \$context): void
    {
        // Reverse up(), or leave this throw for irreversible migrations.
        throw new IrreversibleMigrationException('down() is not implemented for {$class}');
    }
}

PHP;
    }
}
