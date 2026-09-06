<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use xPDO\Migrations\Exception\ConfigurationException;
use xPDO\Migrations\MigrationConfig;
use xPDO\Migrations\Migrator;
use xPDO\xPDO;
use xPDO\xPDOException;

trait MigrationConsoleTrait
{
    protected function addMigrationOptions(): void
    {
        $this
            ->addOption(
                'config',
                'C',
                InputOption::VALUE_REQUIRED,
                'A path to a config file'
            )
            ->addOption(
                'platform',
                null,
                InputOption::VALUE_REQUIRED,
                'PDO platform (mysql, sqlite, pgsql, sqlsrv); defaults to xpdo_driver from config'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'Override migrations_path'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'Override migrations_namespace'
            )
            ->addOption(
                'table',
                null,
                InputOption::VALUE_REQUIRED,
                'Override migrations_table'
            )
        ;
    }

    /**
     * @return Migrator|null
     */
    protected function createMigrator(InputInterface $input, OutputInterface $output)
    {
        $properties = $this->loadConfig($output, $input->getOption('config'));
        if ($properties === false) {
            $output->writeln('fatal: no valid configuration file could be loaded');
            return null;
        }

        $platform = $input->getOption('platform');
        if ($platform === null || $platform === '') {
            $platform = $properties['xpdo_driver'] ?? null;
        }
        $platform = is_string($platform) ? strtolower($platform) : '';
        if ($platform === '' || !in_array($platform, self::$platforms, true)) {
            $output->writeln('fatal: no valid platform specified (use --platform or xpdo_driver in config)');
            return null;
        }

        $optionsKey = "{$platform}_array_options";
        if (empty($properties[$optionsKey]) || !is_array($properties[$optionsKey])) {
            $output->writeln("fatal: config is missing {$optionsKey}");
            return null;
        }

        try {
            $xpdo = xPDO::getInstance('migrations_' . $platform, $properties[$optionsKey]);
        } catch (xPDOException $e) {
            $output->writeln('fatal: ' . $e->getMessage());
            return null;
        }

        $path = $input->getOption('path');
        if ($path === null || $path === '') {
            $path = $properties['migrations_path'] ?? null;
        }
        $namespace = $input->getOption('namespace');
        if ($namespace === null || $namespace === '') {
            $namespace = $properties['migrations_namespace'] ?? null;
        }
        $table = $input->getOption('table');
        if ($table === null || $table === '') {
            $table = $properties['migrations_table'] ?? null;
        }

        $configOptions = [
            'migrations_path' => $path,
            'migrations_namespace' => $namespace,
        ];
        if ($table !== null && $table !== '') {
            $configOptions['migrations_table'] = $table;
        }
        if (!empty($properties['migrations_lock_name'])) {
            $configOptions['migrations_lock_name'] = $properties['migrations_lock_name'];
        }
        if (!empty($properties['transaction_policy'])) {
            $configOptions['transaction_policy'] = $properties['transaction_policy'];
        }

        try {
            $config = MigrationConfig::fromArray($configOptions);
        } catch (ConfigurationException $e) {
            $output->writeln('fatal: ' . $e->getMessage());
            return null;
        }

        return new Migrator($xpdo, $config);
    }

    /**
     * @return int|null null when option omitted
     */
    protected function parseStepOption(InputInterface $input, OutputInterface $output): ?int
    {
        $raw = $input->getOption('step');
        if ($raw === null || $raw === '') {
            return null;
        }
        if (!is_numeric($raw) || (string)(int)$raw !== (string)$raw || (int)$raw < 1) {
            $output->writeln('fatal: --step must be a positive integer');
            return 0;
        }
        return (int)$raw;
    }

    protected function writeThrowable(OutputInterface $output, Throwable $e): void
    {
        $output->writeln('fatal: ' . $e->getMessage());
        $previous = $e->getPrevious();
        while ($previous !== null) {
            $output->writeln('caused by: ' . $previous->getMessage());
            $previous = $previous->getPrevious();
        }
        if ($output->isVerbose()) {
            $output->writeln($e->getTraceAsString());
        }
    }

    protected function writeResultLines(OutputInterface $output, iterable $items, string $emptyLabel = '(none)'): void
    {
        $items = is_array($items) ? $items : iterator_to_array($items);
        if ($items === []) {
            $output->writeln('  ' . $emptyLabel);
            return;
        }
        foreach ($items as $item) {
            $output->writeln('  - ' . $item);
        }
    }
}
