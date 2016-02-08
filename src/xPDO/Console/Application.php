<?php
namespace xPDO\Console;

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
    }
}