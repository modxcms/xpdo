<?php
namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WriteSchema extends Command
{
    protected function configure()
    {
        $this
            ->setName('write-schema')
            ->setDescription("Generate an XML schema from existing database tables.")
            ->addArgument(
                'platform',
                InputArgument::REQUIRED,
                'The PDO platform being targeted, e.g. mysql, sqlite, etc.'
            )
            ->addArgument(
                'schema_file',
                InputArgument::REQUIRED,
                'The path and filename to generate the XML schema to'
            )
            ->addOption(
                'config',
                'C',
                InputOption::VALUE_REQUIRED,
                'A path to a config file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('write-schema command not yet implemented');
        return 1;
    }
}
