<?php

namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MigrationUpdate extends BaseCommand
{
    protected $exe_type = 'install';

    /**
     * @see https://symfony.com/doc/current/console.html
     * namespace:project/extra(-sub-part) verb (GET/POST/DELETE) --options
     */
    protected function configure()
    {
        $this->exe_type = 'update';
        $this
            ->setFullName('mig-update')
            ->setDescription('An update to Migrations is required')
            ->addArgument(
                'update',
                InputArgument::OPTIONAL,
                'Set to argument value to Y to update.',
                'Y'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getMigrator();

        if ($this->migrator->requireUpdate()) {
            $this->migrator->update();
        } else {
            $output->writeln('Noting to do!');
        }
    }
}