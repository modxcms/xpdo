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

final class Migrate extends Command
{
    use MigrationConsoleTrait;

    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Apply pending xPDO migrations')
            ->addOption(
                'step',
                null,
                InputOption::VALUE_REQUIRED,
                'Apply at most N pending migrations'
            )
        ;
        $this->addMigrationOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
            $result = $migrator->migrate($step);
        } catch (Throwable $e) {
            $this->writeThrowable($output, $e);
            return Command::FAILURE;
        }

        if ($result->applied === []) {
            $output->writeln('Nothing to migrate.');
        } else {
            $output->writeln('Applied batch ' . ($result->batch ?? '?') . ':');
            $this->writeResultLines($output, $result->applied);
        }
        if ($result->pending !== []) {
            $output->writeln('Still pending:');
            $this->writeResultLines($output, $result->pending);
        }
        return Command::SUCCESS;
    }
}
