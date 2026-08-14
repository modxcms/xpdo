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

use xPDO\Migrations\Exception\ConfigurationException;

class MigrationConfig
{
    public const POLICY_NON_TRANSACTIONAL = 'non_transactional';
    public const POLICY_TRANSACTIONAL = 'transactional';

    /** @var string */
    private $migrationsPath;

    /** @var string */
    private $migrationsNamespace;

    /** @var string */
    private $migrationsTable;

    /** @var string|null */
    private $migrationsLockName;

    /** @var string */
    private $transactionPolicy;

    /**
     * @param array $options
     * @throws ConfigurationException
     */
    public function __construct(array $options)
    {
        if (empty($options['migrations_path']) || !is_string($options['migrations_path'])) {
            throw new ConfigurationException('migrations_path is required');
        }
        if (empty($options['migrations_namespace']) || !is_string($options['migrations_namespace'])) {
            throw new ConfigurationException('migrations_namespace is required');
        }

        $path = $options['migrations_path'];
        $this->assertLocalPath($path);
        $real = realpath($path);
        if ($real === false) {
            if (!is_dir($path) && !@mkdir($path, 0777, true) && !is_dir($path)) {
                throw new ConfigurationException("migrations_path is not a usable directory: {$path}");
            }
            $real = realpath($path);
            if ($real === false) {
                throw new ConfigurationException("migrations_path could not be resolved: {$path}");
            }
        }
        if (!is_dir($real)) {
            throw new ConfigurationException("migrations_path is not a directory: {$path}");
        }
        $this->migrationsPath = $real;

        $namespace = trim($options['migrations_namespace'], '\\');
        if ($namespace === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $namespace)) {
            throw new ConfigurationException('migrations_namespace is invalid');
        }
        $this->migrationsNamespace = $namespace;

        $table = array_key_exists('migrations_table', $options) && $options['migrations_table'] !== null && $options['migrations_table'] !== ''
            ? (string) $options['migrations_table']
            : 'xpdo_migrations';
        self::assertValidTableName($table);
        $this->migrationsTable = $table;

        $lockName = null;
        if (!empty($options['migrations_lock_name'])) {
            $lockName = (string) $options['migrations_lock_name'];
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $lockName)) {
                throw new ConfigurationException('migrations_lock_name is invalid');
            }
        }
        $this->migrationsLockName = $lockName;

        $policy = array_key_exists('transaction_policy', $options) && $options['transaction_policy'] !== null && $options['transaction_policy'] !== ''
            ? (string) $options['transaction_policy']
            : self::POLICY_NON_TRANSACTIONAL;
        if (!in_array($policy, [self::POLICY_NON_TRANSACTIONAL, self::POLICY_TRANSACTIONAL], true)) {
            throw new ConfigurationException('transaction_policy must be non_transactional or transactional');
        }
        $this->transactionPolicy = $policy;
    }

    /**
     * @param array $options
     * @return self
     * @throws ConfigurationException
     */
    public static function fromArray(array $options)
    {
        return new self($options);
    }

    /**
     * @param string $name
     * @throws ConfigurationException
     */
    public static function assertValidTableName($name)
    {
        if (!is_string($name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new ConfigurationException('Invalid SQL identifier for migrations table');
        }
    }

    /**
     * @param string $path
     * @throws ConfigurationException
     */
    private function assertLocalPath($path)
    {
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*)://#i', $path)) {
            throw new ConfigurationException('migrations_path must be a local filesystem path');
        }
    }

    public function getMigrationsPath()
    {
        return $this->migrationsPath;
    }

    public function getMigrationsNamespace()
    {
        return $this->migrationsNamespace;
    }

    public function getMigrationsTable()
    {
        return $this->migrationsTable;
    }

    /**
     * @return string|null
     */
    public function getMigrationsLockName()
    {
        return $this->migrationsLockName;
    }

    public function getTransactionPolicy()
    {
        return $this->transactionPolicy;
    }

    public function isTransactionalDefault()
    {
        return $this->transactionPolicy === self::POLICY_TRANSACTIONAL;
    }
}
