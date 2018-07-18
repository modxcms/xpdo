<?php

namespace xPDO\Console\Command;

use xPDO\Helpers\ConsoleUserInteractionHandler;
use xPDO\Helpers\EmptyUserInteractionHandler;
use xPDO\Migrations\Migrator;
use xPDO\xPDO;
use xPDO\xPDOException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;


/**
 * Class BaseCommand
 * @package xPDO\Console\Command
 */
abstract class BaseCommand extends Command
{
    /** @var xPDO */
    protected $xpdo;

    /** @var \xPDO\Migrations\Migrator */
    protected $migrator;

    /** @var \xPDO\Helpers\ConsoleUserInteractionHandler */
    protected $consoleUserInteractionHandler;

    /** \Symfony\Component\Console\Input\InputInterface $input */
    protected $input;

    /** \Symfony\Component\Console\Output\OutputInterface $output */
    protected $output;

    protected $startTime;

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->startTime = microtime(true);
        $this->input = $input;
        $this->output = $output;

        $this->consoleUserInteractionHandler = new ConsoleUserInteractionHandler($input, $output);
        $this->consoleUserInteractionHandler->setCommandObject($this);

        try {
            $config = $input->getOption('config');
        } catch (InvalidArgumentException $exception) {
            $config = [];
        }
        $properties = $this->loadConfig($output, $config);
        if ($properties === false) {
            $output->writeln('fatal: no valid configuration file could be loaded');
            return;
        }

        try {
            $platform = strtolower($input->getArgument('platform'));
        } catch (InvalidArgumentException $exception) {
            try {
                $platform = strtolower($input->getOption('platform'));
            } catch (InvalidArgumentException $exception) {
                $platform = $properties['xpdo_driver'];
            }
        }

        if (!in_array($platform, self::$platforms)) {
            $output->writeln("fatal: no valid platform specified");
            return;
        }

        try {
            $this->xpdo = xPDO::getInstance('generator', $properties["{$platform}_array_options"]);
        } catch (xPDOException $exception) {
            $output->writeln('Error: ' . $exception->getMessage());
            exit();
        }
    }

    /**
     * @param array $config
     * @return Migrator
     */
    protected function getMigrator($config=[])
    {
        if (defined('XPDO_MIGRATION_DIR')) {
            $config['xpdo_migration_dir'] = XPDO_MIGRATION_DIR;
        }
        if (defined('XPDO_SEEDS_DIR')) {
            $config['xpdo_seeds_dir'] = XPDO_SEEDS_DIR;
        }

        $this->migrator = new Migrator(
            $this->xpdo,
            $this->consoleUserInteractionHandler,
            $config
        );

        return $this->migrator;
    }

    /**
     * @return xPDO
     * @throws \xPDO\xPDOException
     */
    public static function loadXPDO()
    {
        $properties = self::loadConfig(new ConsoleOutput());
        $platform = $properties['xpdo_driver'];

        return xPDO::getInstance('generator', $properties["{$platform}_array_options"]);
    }

    /**
     * @return bool
     * @throws \xPDO\xPDOException
     */
    public static function isXPDOMigrationsInstalled()
    {
        $migrator = new Migrator(self::loadXPDO(), new EmptyUserInteractionHandler(), []);
        return $migrator->isXPDOMigrationsInstalled();
    }

    /**
     * @return bool
     * @throws \xPDO\xPDOException
     */
    public static function isXPDOMigrationsRequireUpdate()
    {
        $migrator = new Migrator(self::loadXPDO(), new EmptyUserInteractionHandler(), []);
        return $migrator->requireUpdate();
    }



    /**
     * @return string
     */
    public function getRunStats()
    {
        $curTime = microtime(true);
        $duration = $curTime - $this->startTime;

        $output = 'Time: ' . number_format($duration * 1000, 0) . 'ms | ';
        $output .= 'Memory Usage: ' . $this->convertBytes(memory_get_usage(false)) . ' | ';
        $output .= 'Peak Memory Usage: ' . $this->convertBytes(memory_get_peak_usage(false));
        return $output;
    }

    /**
     * @param $bytes
     * @return string
     */
    protected function convertBytes($bytes)
    {
        $unit = array('b','kb','mb','gb','tb','pb');
        return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[$i];
    }
}
