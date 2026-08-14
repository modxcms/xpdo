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
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use xPDO\Migrations\MigratorResult;

final class MigrateStatus extends Command
{
    use MigrationConsoleTrait;

    protected function configure()
    {
        $this
            ->setName('migrate-status')
            ->setDescription('Show applied and pending xPDO migrations (does not create the ledger)')
        ;
        $this->addMigrationOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrator = $this->createMigrator($input, $output);
        if ($migrator === null) {
            return Command::FAILURE;
        }

        try {
            $result = $migrator->status();
        } catch (Throwable $e) {
            $this->writeThrowable($output, $e);
            return Command::FAILURE;
        }

        if ($result->repositoryState === MigratorResult::REPOSITORY_MISSING) {
            $output->writeln('Repository: missing');
            $output->writeln('Ledger table does not exist yet. Applied/pending are unknown until migrate runs.');
            return Command::SUCCESS;
        }

        $output->writeln('Repository: ok');
        $output->writeln('Applied:');
        $this->writeResultLines($output, $result->applied);
        $output->writeln('Pending:');
        $this->writeResultLines($output, $result->pending);
        if ($result->orphaned !== []) {
            $output->writeln('Orphaned (ledger rows without migration files):');
            $this->writeResultLines($output, $result->orphaned);
            $output->writeln(
                'warning: restore missing migration files before migrate/rollback'
            );
        }
        return Command::SUCCESS;
    }
}
