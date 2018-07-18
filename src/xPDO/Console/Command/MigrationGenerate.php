<?php

namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use xPDO\xPDO;
use xPDO\xPDOException;
use xPDO\Migrations\Migrator;

class MigrationGenerate extends BaseCommand
{

    /**
     * @see https://symfony.com/doc/current/console.html
     *
     */
    protected function configure()
    {
        $this
            ->setFullName('mig-generate')
            ->setDescription('Generate an xPDO Data migration file')
            ->addOption(
                'name',
                'N',
                InputOption::VALUE_OPTIONAL,
                'The name of the migration file to generate, will be prefixed with date(\'Y_m_d_His\').'
            )
            ->addOption(
                'description',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Optional description of this migration file',
                ''
            )
            ->addOption(
                'ver',
                'R',
                InputOption::VALUE_OPTIONAL,
                'Optional set a version number for the generated migration file. Example: 1.0.0',
                ''
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Server type, will only run migration with same type. Possible master, staging, dev and local',
                'master'
            )
            ->addOption(
                'log',
                'l',
                InputOption::VALUE_OPTIONAL,
                '[1/0] Log created migration file to DB.',
                '1'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = (string)$input->getOption('name');
        $description = (string)$input->getOption('description');
        $server_type = (string)$input->getOption('type');
        $version = (string)$input->getOption('ver');
        $log = (bool)$input->getOption('log');

        $this->getMigrator();

        $this->migrator->createBlankMigrationClassFile($name, $description, $version, $server_type, $log);
    }
}