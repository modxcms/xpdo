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

final class MigrateRollback extends Command
{
    use MigrationConsoleTrait;

    protected function configure()
    {
        $this
            ->setName('migrate-rollback')
            ->setDescription('Roll back the last xPDO migration batch (or --step within that batch)')
            ->addOption(
                'step',
                null,
                InputOption::VALUE_REQUIRED,
                'Roll back at most N migrations from the last batch (descending by version)'
            )
        ;
        $this->addMigrationOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrator = $this->createMigrator($input, $output);
        if ($migrator === null) {
            return Command::FAILURE;
        }

        $step = $this->parseStepOption($input, $output);
        if ($step === 0) {
            return Command::FAILURE;
        }

        try {
            $result = $migrator->rollback($step);
        } catch (Throwable $e) {
            $this->writeThrowable($output, $e);
            return Command::FAILURE;
        }

        if ($result->rolledBack === []) {
            $output->writeln('Nothing to roll back.');
        } else {
            $output->writeln('Rolled back batch ' . ($result->batch ?? '?') . ':');
            $this->writeResultLines($output, $result->rolledBack);
        }
        return Command::SUCCESS;
    }
}
