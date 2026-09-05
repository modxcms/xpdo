<?php
namespace xPDO\Console;

use xPDO\Console\Command\Migrate;
use xPDO\Console\Command\MigrateCreate;
use xPDO\Console\Command\MigrateRollback;
use xPDO\Console\Command\MigrateStatus;
use xPDO\Console\Command\ParseSchema;
use xPDO\Console\Command\WriteSchema;

class Application extends \Symfony\Component\Console\Application
{
    protected static $name = 'xPDO Console';
    protected static $version = '1.0.0';

    public function __construct(){
        parent::__construct(self::$name, self::$version);
    }

    public function loadCommands()
    {
        $this->add(new ParseSchema());
        $this->add(new WriteSchema());
        $this->add(new MigrateCreate());
        $this->add(new MigrateStatus());
        $this->add(new Migrate());
        $this->add(new MigrateRollback());
    }
}
