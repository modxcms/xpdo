<?php

namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MigrationUninstall extends BaseCommand
{
    /**
     * @see https://symfony.com/doc/current/console.html
     * namespace:project/extra(-sub-part) verb (GET/POST/DELETE) --options
     */
    protected function configure()
    {
        $this
            ->setFullName('mig-uninstall')
            ->setDescription('Uninstall Migrations');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getMigrator();
        $this->migrator->install('down', true);
    }
}