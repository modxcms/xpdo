<?php

namespace xPDO\Migrations;

use xPDO\xPDO;

/**
 * Class Migration ~ is the base migration class
 * @package xPDO\Migrations
 */
abstract class Migration
{
    /** @var \xPDO\xPDO  */
    protected $xPDO;

    /** @var  Migrator */
    protected $migrator;

    /** @var string ~ a description of what this migration will do */
    protected $description = '';

    /** @var string ~ a version number if you choose */
    protected $version = '';

    /** @var string ~ master|staging|dev|local */
    protected $type = 'master';

    /** @var string ~ will be for any seeds to find their related directory */
    protected $seeds_dir = '';

    /** @var string name of Author of the Migration */
    protected $author = '';

    /** @var string  */
    protected $method = 'up';

    /**
     * Migrations constructor.
     *
     * @param $xPDO
     * @param Migrator $migrator
     */
    public function __construct(\xPDO\xPDO &$xPDO, Migrator $migrator)
    {
        $this->xPDO = $xPDO;
        $this->migrator = $migrator;

        $this->assignDescription();
        $this->assignVersion();
        $this->assignType();
        $this->assignSeedsDir();
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Child class needs to override and implement this
        $this->xPDO->log(
            xPDO::LOG_LEVEL_ERROR,
            get_class($this).'::'.__METHOD__.PHP_EOL.
            'Did not implement up()'
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->method = 'down';

        // Child class needs to override and implement this
        $this->xPDO->log(
            xPDO::LOG_LEVEL_ERROR,
            get_class($this).'::'.__METHOD__.PHP_EOL.
            'Did not implement down()'
        );
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return (string)$this->description;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return (string)$this->version;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string use setSeedsDir
     */
    public function getSeedsDir()
    {
        return $this->seeds_dir;
    }

    /**
     * Method is called on construct, Child class needs to override and implement this
     */
    protected function assignDescription()
    {
        $this->description = '';
    }

    /**
     * Method is called on construct, Child class needs to override and implement this
     */
    protected function assignVersion()
    {
        $this->version = '';
    }

    /**
     * Method is called on construct, Child class can override and implement this
     */
    protected function assignType()
    {
        $this->type = 'master';
    }

    /**
     * Method is called on construct, Child class can override and implement this
     */
    protected function assignSeedsDir()
    {
        $this->seeds_dir = '';
    }

}