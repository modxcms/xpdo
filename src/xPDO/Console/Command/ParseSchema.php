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
            ->setDescription('Parse an XML schema and generate xPDO model classes from it')
            ->addArgument(
                'platform',
                InputArgument::REQUIRED,
                'The PDO platform being targeted, e.g. mysql, sqlite, etc.'
            )
            ->addArgument(
                'schema_file',
                InputArgument::REQUIRED,
                'A path to a file containing the XML schema'
            )
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The target path to generate the model classes'
            )
            ->addOption(
                'config',
                'C',
                InputOption::VALUE_REQUIRED,
                'A path to a config file'
            )
            ->addOption(
                'compile',
                'c',
                InputOption::VALUE_NONE,
                'Compile all classes into one file'
            )
            ->addOption(
                'update',
                null,
                InputOption::VALUE_REQUIRED,
                'Update generated model classes'
            )
            ->addOption(
                'regen',
                null,
                InputOption::VALUE_REQUIRED,
                'Regenerate model classes'
            )
            ->addOption(
                'psr4',
                null,
                InputOption::VALUE_REQUIRED,
                'Enable PSR-4 autoloading support with provided namespace prefix; default is PSR-0'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platform = strtolower($input->getArgument('platform'));
        if (!in_array($platform, self::$platforms)) {
            $output->writeln("fatal: no valid platform specified");
            return;
        }

        $properties = $this->loadConfig($output, $input->getOption('config'));
        if ($properties === false) {
            $output->writeln('fatal: no valid configuration file could be loaded');
            return;
        }

        $schema = $input->getArgument('schema_file');
        if (!is_readable($schema)) {
            $output->writeln("fatal: no valid schema provided");
            return;
        }

        $namespacePrefix = $input->getOption('psr4');
        $namespacePrefix = empty($namespacePrefix) ? '' : $namespacePrefix;

        $update = $input->getOption('update');
        $update = $update === null ? 0 : (int)$update;

        $regen = $input->getOption('regen');
        $regen = $regen === null ? 0 : (int)$regen;

        $xpdo = xPDO::getInstance('generator', $properties["{$platform}_array_options"]);

        $generator = $xpdo->getManager()->getGenerator();
        $generator->parseSchema(
            $schema,
            $input->getArgument('path'),
            array(
                'compile' => $input->getOption('compile'),
                'update' => $update,
                'regenerate' => $regen,
                'namespacePrefix' => $namespacePrefix,
            )
        );
    }
}
