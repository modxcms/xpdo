<?php

namespace xPDO\Console\Command;

use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    protected static $platforms = [
        'mysql', 
        'sqlite', 
        'sqlsrv'
    ];
    
    protected function loadConfig(OutputInterface $output, $config = null)
    {
        if (empty($config) || !is_readable($config)) {
            $config = false;
            $locations = array(
                dirname(__DIR__) . '/test/properties.inc.php',
                getcwd() . '/test/properties.inc.php',
                getcwd() . '/properties.inc.php',
            );
            foreach ($locations as $location) {
                if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("no config specified; looking for {$location}");
                }
                if (is_readable($location)) {
                    $config = $location;
                    break;
                }
            }
        }
        if (!empty($config) && is_readable($config)) {
            $properties = require $config;

            if (!is_array($properties)) {
                return false;
            }

            if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("using config from {$config}");
            }
            
            return $properties;
        }

        return false;
    }
}