<?php

namespace xPDO\Migrations;

use xPDO\xPDO;

/**
 * Class MigrationsCreator ~ will create Migrations
 * @package xPDO\Migrations
 */
class MigrationsCreator
{
    /** @var string */
    protected $description;

    /** @var bool  */
    protected $log = true;

    /** @var Migrator */
    protected $migrator;

    /** @var xPDO */
    protected $xPDO;

    /** @var string  */
    protected $server_type = 'master';

    /** @var string ~ 1.0.0 the version of the migration file or related project */
    protected $version = '';

    /**
     * MigrationsCreator constructor.
     * @param Migrator $migrator
     * @param xPDO $xPDO
     */
    public function __construct(Migrator $migrator, xPDO $xPDO)
    {
        $this->migrator = $migrator;
        $this->xPDO = $xPDO;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function createBlankMigrationClassFile($name=null)
    {
        return $this->writeMigrationClassFile('blank', $name);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getServerType()
    {
        return $this->server_type;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return bool
     */
    public function isLog()
    {
        return $this->log;
    }

    /**
     * @param string $description
     * @return MigrationsCreator
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param bool $log
     * @return MigrationsCreator
     */
    public function setLog($log)
    {
        $this->log = $log;
        return $this;
    }

    /**
     * @param string $server_type
     * @return MigrationsCreator
     */
    public function setServerType($server_type)
    {
        $this->server_type = $server_type;
        return $this;
    }

    /**
     * @param string $version
     * @return MigrationsCreator
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return bool
     */
    protected function writeMigrationClassFile($type, $name=null)
    {
        $class_name = $this->migrator->getMigrationName($type, $name);

        $migration_template = 'blank.txt';
        $placeholders = [
            'classCreateDate' => date('Y/m/d'),
            'classCreateTime' => date('G:i:s T P'),
            'className' => $class_name,
            'classUpInners' => '//@TODO',
            'classDownInners' => '//@TODO',
            'description' => $this->getDescription(),
            'serverType' => $this->getServerType(),
            'seeds_dir' => $class_name,
            'version' => $this->getVersion()
        ];

        $file_contents = '';

        $migration_template = $this->migrator->getMigrationTemplatePath().$migration_template;
        if (file_exists($migration_template)) {
            $file_contents = file_get_contents($migration_template);
        } else {
            $this->migrator->outError('Migration template file not found: ' . $migration_template);
        }

        foreach ($placeholders as $name => $value) {
            $file_contents = str_replace('[[+'.$name.']]', $value, $file_contents);
        }

        $this->migrator->out($this->migrator->getMigrationPath().$class_name.'.php');

        $write = false;
        if (file_exists($this->migrator->getMigrationPath().$class_name.'.php')) {
            $this->migrator->out($this->migrator->getMigrationPath() . $class_name . '.php migration file already exists', true);

        } elseif (is_object($this->xPDO->getObject($this->migrator->getMigrationClassObject(), ['name' => $class_name]))) {
            $this->migrator->out($class_name . ' migration already has been created in the xpdo_migrations table', true);

        } else {
            try {
                $write = file_put_contents($this->migrator->getMigrationPath() . $class_name . '.php', $file_contents);
                $migration = $this->xPDO->newObject($this->migrator->getMigrationClassObject());
                if ($migration && $this->isLog()) {
                    $migration->set('name', $class_name);
                    $migration->set('type', 'master');
                    $migration->set('description', '');// @TODO
                    $migration->set('version', '');
                    $migration->set('status', 'seed export');
                    $migration->set('created_at', date('Y-m-d H:i:s'));
                    $migration->save();
                }
            } catch (\Exception $exception) {
                $this->migrator->out($exception->getMessage(), true);
            }

            if (!$write) {
                $this->migrator->out($this->migrator->getMigrationPath() . $class_name . '.php Did not write to file', true);
                $this->migrator->out('Verify that the folders exists and are writable by PHP', true);
            }
        }

        return $write;
    }

}