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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class MigrateCreate extends Command
{
    use MigrationConsoleTrait;

    protected function configure()
    {
        $this
            ->setName('migrate-create')
            ->setDescription('Generate a new xPDO migration class skeleton')
            ->addArgument(
                'description',
                InputArgument::REQUIRED,
                'Migration description (letters/digits; used after the timestamp prefix)'
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

        try {
            $result = $migrator->create((string) $input->getArgument('description'));
        } catch (Throwable $e) {
            $this->writeThrowable($output, $e);
            return Command::FAILURE;
        }

        foreach ($result->messages as $message) {
            $output->writeln($message);
        }
        if ($result->created !== null) {
            $output->writeln('Created migration: ' . $result->created);
        }
        return Command::SUCCESS;
    }
}
