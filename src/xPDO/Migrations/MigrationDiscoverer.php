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

use DirectoryIterator;
use xPDO\Migrations\Exception\InvalidMigrationException;

/**
 * @internal Not a stable extension API. Prefer Migrator / Migration / MigrationContext / MigrationConfig.
 */
class MigrationDiscoverer
{
    public const NAME_PATTERN = '/^[0-9]{14}_[A-Za-z][A-Za-z0-9]*$/';

    /** @var MigrationConfig */
    private $config;

    public function __construct(MigrationConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return array<int, array{name:string,class:string,file:string}>
     */
    public function discover(): array
    {
        $path = $this->config->getMigrationsPath();
        $found = [];
        $seen = [];

        foreach (new DirectoryIterator($path) as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $basename = $file->getBasename('.php');
            if (!preg_match(self::NAME_PATTERN, $basename)) {
                continue;
            }
            if (isset($seen[$basename])) {
                throw new InvalidMigrationException("Duplicate migration name: {$basename}");
            }
            $seen[$basename] = true;
            $class = $this->classNameFor($basename);
            $found[] = [
                'name' => $basename,
                'class' => $class,
                'file' => $file->getPathname(),
            ];
        }

        usort($found, static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $found;
    }

    public function load(string $name): Migration
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidMigrationException("Invalid migration name: {$name}");
        }
        $file = $this->config->getMigrationsPath() . DIRECTORY_SEPARATOR . $name . '.php';
        if (!is_readable($file)) {
            throw new InvalidMigrationException("Migration file not readable: {$file}");
        }

        require_once $file;
        $class = $this->classNameFor($name);
        if (!class_exists($class)) {
            throw new InvalidMigrationException("Migration class not found: {$class}");
        }
        $instance = new $class();
        if (!$instance instanceof Migration) {
            throw new InvalidMigrationException("Migration class must extend Migration: {$class}");
        }
        return $instance;
    }

    public function classNameFor(string $migrationName): string
    {
        return $this->config->getMigrationsNamespace() . '\\M' . $migrationName;
    }
}
