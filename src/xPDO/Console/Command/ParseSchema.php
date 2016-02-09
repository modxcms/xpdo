<?php
namespace xPDO\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use xPDO\xPDO;

final class ParseSchema extends Command
{
    protected function configure()
    {
        $this
            ->setName('parse-schema')
            ->setDescription('Parses schema')
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
            ->addOption(
                'compile',
                'c',
                InputOption::VALUE_NONE,
                'Compile'
            )
            ->addOption(
                'update',
                null,
                InputOption::VALUE_REQUIRED,
                'Update'
            )
            ->addOption(
                'regen',
                null,
                InputOption::VALUE_REQUIRED,
                'Regen'
            )
            ->addOption(
                'psr4',
                null,
                InputOption::VALUE_NONE,
                'PSR4'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $properties = $this->loadConfig($output, $input->getOption('config'));
        if ($properties === false) {
            $output->writeln('fatal: no valid configuration file could be loaded');
            return;
        }
        
        $platform = strtolower($input->getArgument('platform'));
        if (!in_array($platform, self::$platforms)) {
            $output->writeln("fatal: no valid platform specified");
            return;
        }

        $schema = $input->getArgument('schema_file');
        if (!is_readable($schema)) {
            $output->writeln("fatal: no valid schema provided");
            return;
        }

        $withNamespace = (intval($input->getOption('psr4')) == 1) ? 0 : 1;

        $update = $input->getOption('update');
        $update = $update === null ? 0 : (int)$update;

        $regen = $input->getOption('regen');
        $regen = $regen === null ? 0 : (int)$regen;

        $xpdo = xPDO::getInstance('generator', $properties["{$platform}_array_options"]);
        $xpdo->setLogLevel(xPDO::LOG_LEVEL_INFO);
        $xpdo->setLogTarget(PHP_SAPI === 'cli' ? 'ECHO' : 'HTML');

        $generator = $xpdo->getManager()->getGenerator();
        $generator->parseSchema(
            $schema,
            $input->getArgument('path'),
            array(
                'compile' => $input->getOption('compile'),
                'update' => $update,
                'regenerate' => $regen,
                'withNamespace' => $withNamespace,
            )
        );
    }
}