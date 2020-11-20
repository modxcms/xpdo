<?php

/**
 * Ideally, this file should never be changed and no additional install scripts should be created
 * But on any DB/schema update a new update migration should be created relating to the new version like:
 * v001_000_001_Update that migration would then update the DB schema from the first release. So every installation runs
 * starts from install and then runs every update step.
 * @TODO issue: If say column help is added to the xPDO schema modal and for new installs it would run the install to
 *  createObjectContainer() and it would also add the column help and then the update migration would run in both situations
 *  and attempt to add the help column. For new installs this would create an error, maybe each update has to check if
 *  change has been made before updating?
 */

use xPDO\Migrations\Migration;

class InstallXPDOMigrations extends Migration
{
    /** @var \xPDO\Migrations\MigratorInstall */
    protected $migrator;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // install DB table:
        $manager = $this->xPDO->getManager();

        // the class table object name
        $table_class = $this->migrator->getMigrationClassObject();
        if ($manager->createObjectContainer($table_class)) {
            $this->migrator->out($table_class.' table class has been created');
            $this->migrator->setDBVersion('1.0.0');

        } else {
            $this->migrator->out($table_class.' table class was not created', true);
        }

        $this->xPDO->getCacheManager()->refresh();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // remove DB Table:
        $manager = $this->xPDO->getManager();

        // the class table object name
        $table_class = $this->migrator->getMigrationClassObject();
        if ($manager->removeObjectContainer($table_class)) {
            $this->migrator->out($table_class.' table class has been dropped');

        } else {
            $this->migrator->out($table_class.' table class was not dropped', true);
        }

        $this->xPDO->getCacheManager()->refresh();
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignDescription()
    {
        $this->description = 'Install of Migrations';
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignVersion()
    {
        $this->version = $this->migrator->getVersion();
    }

    /**
     * Method is called on construct, can change to only run this migration for those types
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
        $this->seeds_dir = '2018_07_16_010101';
    }
}
