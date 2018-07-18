<?php

namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MigrationInstall extends BaseCommand
{
    /**
     * @see https://symfony.com/doc/current/console.html
     * namespace:project/extra(-sub-part) verb (GET/POST/DELETE) --options
     */
    protected function configure()
    {
        $this
            ->setFullName('mig-install')
            ->setDescription('Please install Migrations');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getMigrator();

        $output->writeln('Install ... ');
        $this->migrator->install('up', true);
    }
}