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
            ->setDescription('Writes schema')
            ->addArgument(
                'platform',
                InputArgument::REQUIRED,
                'Platform'
            )
            ->addArgument(
                'schema_file',
                InputArgument::REQUIRED,
                'Path to schema'
            )
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Target Path'
            )
            ->addOption(
                'config',
                'C',
                InputOption::VALUE_REQUIRED,
                'Path to config file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('write-schema command not yet implemented');
    }
}