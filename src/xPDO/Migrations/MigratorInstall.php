<?php

namespace xPDO\Migrations;

/**
 * Class Migrator ~ will run migrations
 * @package xPDO\Migrations
 */
class MigratorInstall extends Migrator
{
    /**
     * @param string $version ~ 1.0.0
     */
    public function setDBVersion($version)
    {
        file_put_contents(__DIR__.'/log/version.log', $version);
    }
}
