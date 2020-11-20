<?php
namespace xPDO\Console;

use xPDO\Console\Command\BaseCommand;
use xPDO\Console\Command\MigrationGenerate;
use xPDO\Console\Command\Migrate;
use xPDO\Console\Command\MigrationInstall;
use xPDO\Console\Command\ParseSchema;
use xPDO\Console\Command\MigrationUpdate;
use xPDO\Console\Command\WriteSchema;
use xPDO\xPDOException;

class Application extends \Symfony\Component\Console\Application
{
    protected static $name = 'xPDO Console';
    protected static $version = '1.0.0';

    public function __construct(){
        parent::__construct(self::$name, self::$version);
    }

    public function loadCommands()
    {
        // allow user to set in config a namespace to console commands, example adding in xpdo: xpdo:write-schema
        $console_namespace = 'xpdo';
        if (defined('XPDO_CONSOLE_COMMAND_NAMESPACE')) {
            $console_namespace = XPDO_CONSOLE_COMMAND_NAMESPACE;
        }
        $this->add((new ParseSchema())->setNameSpace($console_namespace));
        $this->add((new WriteSchema())->setNamespace($console_namespace));

        try {
            $installed = BaseCommand::isXPDOMigrationsInstalled();
            if (!$installed) {
                $this->add((new MigrationInstall())->setNamespace($console_namespace));

            } elseif (BaseCommand::isXPDOMigrationsRequireUpdate()) {
                $this->add((new MigrationUpdate())->setNamespace($console_namespace));

            } else {
                // run migration
                $this->add((new Migrate())->setNamespace($console_namespace));
                $this->add((new MigrationGenerate())->setNamespace($console_namespace));
            }

        } catch (xPDOException $exception) {
            echo $exception->getMessage().PHP_EOL;
        }
    }
}