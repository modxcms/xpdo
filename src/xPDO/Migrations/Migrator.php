<?php

namespace xPDO\Migrations;

use xPDO\Helpers\UserInteractionHandler;
use xPDO\Migrations\Model\XPDOMigrations;
use xPDO\xPDO;

/**
 * Class Migrator ~ will run migrations
 * @package xPDO\Migrations
 */
class Migrator
{
    /** @var string ~ version number of the Migrator/Migrations */
    private $version = '1.0.0';

    protected $installed_version = null;

    /** @var  xPDO */
    protected $xPDO;

    /** @var \xPDO\Helpers\UserInteractionHandler */
    protected $userInteractionHandler;

    /** @var array  */
    protected $config = [];

    /** @var boolean|array  */
    protected $xPDOMigrations = false;

    /** @var string date('Y_m_d_His') */
    protected $seeds_dir = '';

    protected $migration_class_object = 'xPDO\\Migrations\\Model\\XPDOMigrations';

    protected $package = 'xPDO\\Migrations\\Model';

    /**
     * Stockpile constructor.
     *
     * @param xPDO $xPDO
     * @param UserInteractionHandler $userInteractionHandler
     * @param array $config
     */
    public function __construct(xPDO $xPDO, UserInteractionHandler $userInteractionHandler, $config=[])
    {
        $this->xPDO = $xPDO;

        $this->userInteractionHandler = $userInteractionHandler;

        $xpdo_migration_dir = dirname(__FILE__).'/database/migrations/';
        if (isset($config['xpdo_migration_dir'])) {
            $xpdo_migration_dir = $config['xpdo_migration_dir'];
        }

        $xpdo_seeds_dir = dirname(__FILE__).'/database/seeds/';
        if (isset($config['xpdo_seeds_dir'])) {
            $xpdo_seeds_dir = $config['xpdo_seeds_dir'];
        }

        $this->config = [
            'migration_templates_path' => dirname(__FILE__). '/database/templates/',
            'migrations_path' => $xpdo_migration_dir,
            'seeds_path' => $xpdo_seeds_dir,
            'model_dir' => dirname(__FILE__) . '/',
            'extras' => [
                'tagger' => false
            ]
        ];
        $this->config = array_merge($this->config, $config);

        $this->seeds_dir = date('Y_m_d_His');

        $this->xPDO->setPackage($this->package, $this->config['model_dir']);
    }

    /**
     * @param string $name
     * @param string $description
     * @param string $version
     * @param string $server_type
     * @param bool $log
     *
     * @return bool
     */
    public function createBlankMigrationClassFile($name, $description='', $version='', $server_type='master', $log=true)
    {
        $migrationsCreator = new MigrationsCreator($this, $this->xPDO);
        return $migrationsCreator
            ->setDescription($description)
            ->setVersion($version)
            ->setLog($log)
            ->setServerType($server_type)
            ->createBlankMigrationClassFile($name);
    }

    /**
     * @return string
     */
    public function getMigrationClassObject()
    {
        return $this->migration_class_object;
    }
    /**
     * @param $type
     * @param null $name
     * @return string
     */
    public function getMigrationName($type, $name=null)
    {
        $dir_name = 'm'.$this->seeds_dir.'_';
        if (empty($name)) {
            $name = ucfirst(strtolower($type));
        }

        $dir_name .= preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(['/', ' '], '_', $name));

        return $dir_name;
    }

    /**
     * @return UserInteractionHandler
     */
    public function getUserInteractionHandler()
    {
        return $this->userInteractionHandler;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return bool|string
     */
    public function getInstalledVersion()
    {
        if (is_null($this->installed_version) ) {
            if (file_exists(__DIR__ . '/log/version.log')) {
                $this->installed_version = file_get_contents(__DIR__ . '/log/version.log');
            } else {
                $this->installed_version = false;
            }
        }
        return $this->installed_version;
    }

    /**
     * @return string
     */
    public function getSeedsDir()
    {
        return $this->seeds_dir;
    }

    /**
     * @param string $seeds_dir ~ local folder
     *
     * @return Migrator
     */
    public function setSeedsDir($seeds_dir)
    {
        $this->seeds_dir = $seeds_dir;
        return $this;
    }

    /**
     * @param null $directory_key
     * @return string
     */
    public function getSeedsPath($directory_key=null)
    {
        $seed_path = $this->config['seeds_path'];
        if (!empty($directory_key)) {
            $seed_path .= trim($directory_key, '/') . DIRECTORY_SEPARATOR;
        }
        return $seed_path;
    }

    /**
     * @return string
     */
    public function getMigrationPath()
    {
        return $this->config['migrations_path'];
    }

    /**
     * @return string
     */
    public function getMigrationTemplatePath()
    {
        return $this->config['migration_templates_path'];
    }

    /**
     * @param bool $reload
     * @param string $dir
     * @param int $count
     * @param int $id
     * @param string $name
     *
     * @return array ~ array of \XPDOMigrations
     */
    public function getxPDOMigrationCollection($reload=false, $dir='ASC', $count=0, $id=0, $name=null)
    {
        if (!$this->xPDOMigrations || $reload) {
            $xPDOMigrations = [];

            /** @var \xPDO\Om\xPDOQuery $query */
            $query = $this->xPDO->newQuery($this->migration_class_object);
            if ($id > 0 ) {
                $query->where(['id' => $id]);
            } elseif (!empty($name)) {
                $query->where(['name' => $name]);
            }

            $query->sortBy('name', $dir);
            if ($count > 0 ) {
                $query->limit($count);
            }
            $query->prepare();
            //echo 'SQL: '.$query->toSQL();
            $migrationCollection = $this->xPDO->getCollection($this->migration_class_object, $query);

            /** @var \xPDO\Migrations\Model\XPDOMigrations $migration */
            foreach ($migrationCollection as $migration) {
                $xPDOMigrations[$migration->get('name')] = $migration;
            }
            $this->xPDOMigrations = $xPDOMigrations;
        }
        return $this->xPDOMigrations;
    }

    /**
     * @param string $method
     * @param bool $prompt
     */
    public function install($method='up', $prompt=false)
    {
        $migration_name = 'InstallXPDOMigrations';
        $custom_migration_dir = __DIR__.'/database/migrations/install/';

        $config = $this->config;

        $config['migrations_path'] = $custom_migration_dir;

        if (!empty($seed_root_path)) {
            $config['seeds_path'] = $seed_root_path;
        }

        /** @var Migrator $migrator */
        $migrator = new MigratorInstall($this->xPDO, $this->getUserInteractionHandler(), $config);

        $migrator->runMigration($method, 'master', 0, 0, $migration_name);

        // does the migration directory exist?
        if (!file_exists($this->getMigrationPath())) {
            $create = true;
            if ($prompt) {
                $response = $this->prompt('Create the following directory for migration files? (y/n) '.PHP_EOL
                    .$this->getMigrationPath(), 'y');
                if (strtolower(trim($response)) != 'y') {
                    $create = false;
                }
            }
            if ($create) {
                mkdir($this->getMigrationPath(), 0700, true);
                $this->outSuccess('Created migration directory: '. $this->getMigrationPath());
            }
        }
    }

    /**
     * @return bool
     */
    public function isXPDOMigrationsInstalled()
    {
        try {
            $table = $this->xPDO->getTableName($this->migration_class_object);
            if ($this->xPDO->query("SELECT 1 FROM {$table} LIMIT 1") === false) {
                return false;
            }
        } catch (\Exception $exception) {
            // We got an exception == table not found
            return false;
        }

        /** @var \xPDO\Om\xPDOQuery $query */
        $query = $this->xPDO->newQuery($this->migration_class_object);
        $query->select('id');
        $query->where([
            'name' => 'InstallXPDOMigrations',
            'status' => 'up_complete'
        ]);
        $query->sortBy('name');

        $installMigration = $this->xPDO->getObject($this->migration_class_object, $query);
        if ($installMigration instanceof XPDOMigrations) {
            return true;
        }

        return false;
    }

    /**
     * @param string $message
     * @param bool $error
     */
    public function out($message, $error=false)
    {
        if ($error) {
            $this->userInteractionHandler->tellUser($message, userInteractionHandler::MASSAGE_ERROR);

        } else {
            $this->userInteractionHandler->tellUser($message, userInteractionHandler::MASSAGE_STRING);
        }
    }

    /**
     * @param string $message
     */
    public function outError($message)
    {
        $this->out($message, true);
    }

    /**
     * @param string $message
     */
    public function outSuccess($message)
    {
        $this->userInteractionHandler->tellUser($message, userInteractionHandler::MASSAGE_SUCCESS);
    }

    /**
     * @param string $name
     * @param string $type ~ chunk, plugin, resource, snippet, systemSettings, template, site
     *
     * @return bool
     */
    public function removeMigrationFile($name, $type)
    {
        $class_name = $this->getMigrationName($type, $name);

        $removed = false;
        $migration_file = $this->getMigrationPath() . $class_name . '.php';
        if (file_exists($migration_file)) {
            if (unlink($migration_file)) {
                $removed = true;
                $migration = $this->xPDO->getObject($this->migration_class_object, ['name' => $class_name]);
                if (is_object($migration) && $migration->remove()) {
                    $this->out($class_name . ' migration has been removed from the xpdo_migrations table');

                }
            } else {
                $this->out($class_name . ' migration has not been removed from the xpdo_migrations table', true);
            }

        } else {
            $this->out($this->getMigrationPath() . $class_name . '.php migration could not be found to remove', true);
        }

        return $removed;
    }

    /**
     * @return bool
     */
    public function requireUpdate()
    {
        $upgrade = false;

        $db_version = $this->getInstalledVersion();
        if ( $this->isXPDOMigrationsInstalled() && ( !$db_version || version_compare($this->getVersion(), $db_version)) ) {
            $upgrade = true;
        }

        return $upgrade;
    }

    /**
     * @param bool $save
     * @return array ~ ['Migration Name' => xPDOMigrations, ...]
     */
    public function retrieveMigrationFiles($save=true)
    {
        // 1. Get all migrations currently in DB:
        $migrationCollection = $this->xPDO->getCollection($this->migration_class_object);

        $xPDOMigrations = [];

        /** @var xPDOMigrations $migration */
        foreach ($migrationCollection as $migration) {
            $xPDOMigrations[$migration->get('name')] = $migration;
        }

        $migration_dir = $this->getMigrationPath();
        $this->out('Searching '.$migration_dir);

        /** @var \DirectoryIterator $file */
        foreach (new \DirectoryIterator($this->getMigrationPath()) as $file) {
            if ($file->isFile() && $file->getExtension() == 'php') {

                $name = $file->getBasename('.php');
                // @TODO query DB! and test this method
                if (!isset($xPDOMigrations[$name])) {
                    $this->out('Create new '.$name);
                    /** @var \xPDO\Migrations\Migration $migrationProcessClass */
                    $migrationProcessClass = $this->loadMigrationClass($name, $this);

                    /** @var xPDOMigrations $migration */
                    $migration = $this->xPDO->newObject($this->migration_class_object);
                    $migration->set('name', $name);
                    $migration->set('status', 'ready');
                    if ($migrationProcessClass instanceof Migration) {
                        $migration->set('description', $migrationProcessClass->getDescription());
                        $migration->set('version', $migrationProcessClass->getVersion());
                        $migration->set('author', $migrationProcessClass->getAuthor());
                    }

                    if ($save && !$migration->save()) {
                        exit();
                    };

                    $xPDOMigrations[$name] = $migration;
                }
            }
        }

        return $xPDOMigrations;
    }

    /**
     * @param string $method
     * @param string $type
     * @param int $count
     * @param int $id
     * @param string $migration_name
     */
    public function runMigration($method='up', $type='master', $count=0, $id=0, $migration_name=null)
    {
        $dir = 'ASC';
        if ($method == 'down') {
            $dir = 'DESC';
        } else {
            $count = 0;
        }
        // 1. Get all migrations currently in DB:
        $xPDOMigrations = [];
        if ($migrations_installed = $this->isXPDOMigrationsInstalled()) {
            $xPDOMigrations = $this->getxPDOMigrationCollection(false, $dir, $count, $id, $migration_name);
        }

        // 2. Load migration files:
        if ($method == 'up') {
            $xPDOMigrations = $this->retrieveMigrationFiles($migrations_installed);
            if ($migrations_installed) {
                // this is needed just to insure that the order is correct and any new files
                $xPDOMigrations = $this->getxPDOMigrationCollection(true, $dir, $count, $id, $migration_name);
            }
        }

        // 3. now run migration if proper
        /** @var xPDOMigrations $migration */
        foreach ($xPDOMigrations as $name => $migration) {
            if (($id > 0 && $migration->get('id') != $id) || (!empty($migration_name) && $name != $migration_name)) {
                continue;
            }
            /** @var string $name */
            $name = $migration->get('name');

            /** @var string $status ~ ready|up_complete|down_complete*/
            $status = $migration->get('status');

            /** @var string $server_type */
            $server_type = $migration->get('type');

            if ( ($server_type != $type) || ($method == 'up' && $status == 'up_complete') || ($method == 'down' && $status != 'up_complete') ) {
                continue;
            }

            /** @var Migrator $migrator */
            //$migrator = new Migrator($this->xPDO, $this->getUserInteractionHandler(), $this->config);

            /** @var Migration $migrationProcessClass */
            $migrationProcessClass = $this->loadMigrationClass($name, $this);

            if ($migrationProcessClass instanceof Migration) {
                $this->out('Load Class: '.$name.' M: '.$method);
                if ($method == 'up') {
                    $migrationProcessClass->up();
                    $this->out('Run up: '.$name);
                    $migration->set('status', 'up_complete');
                    $migration->set('processed_at', date('Y-m-d H:i:s'));
                    $migration->save();

                } elseif ($method == 'down') {
                    $migrationProcessClass->down();
                    $migration->set('status', 'down_complete');
                    $migration->set('processed_at', date('Y-m-d H:i:s'));
                    $migration->save();

                } else {
                    $this->outError('Invalid method: ' . $method);
                }
            } else {
                $this->outError('Invalid migration: ' . $name);
            }
        }
    }

    /**
     * @param string $method
     */
    public function update($method='up')
    {
        $config = $this->config;
        $config['migrations_path'] = __DIR__.'/database/migrations/updates/';

        $migrator = new MigratorInstall($this->xPDO, $this->getUserInteractionHandler(), $config);

        $migrator->runMigration($method);
    }

    /**
     * @param string $name
     * @param Migrator $migrator
     *
     * @return bool|Migration
     */
    protected function loadMigrationClass($name, Migrator $migrator)
    {
        $migrationProcessClass = false;

        $file = $migrator->getMigrationPath().$name.'.php';
        if (file_exists($file)) {
            require_once $file;

            if(class_exists($name)) {
                /** @var Migration $migrationProcessClass */
                $migrationProcessClass = new $name($this->xPDO, $migrator);
            }
        }

        return $migrationProcessClass;
    }

    /**
     * @param mixed|array $data
     * @param int $tabs
     *
     * @return string
     */
    protected function prettyVarExport($data, $tabs=1)
    {
        $spacing = str_repeat(' ', 4*$tabs);

        $string = '';
        $parts = preg_split('/\R/', var_export($data, true));
        foreach ($parts as $k => $part) {
            if ($k > 0) {
                $string .= $spacing;
            }
            $string .= $part.PHP_EOL;
        }

        return trim($string);
    }

    /**
     * @param string $question
     * @param string $default
     *
     * @return mixed
     */
    protected function prompt($question, $default='')
    {
        return $this->userInteractionHandler->promptInput($question, $default);
    }

    /**
     * @param string $question
     * @param bool $default
     * @return bool
     */
    protected function promptConfirm($question, $default=true)
    {
        return $this->userInteractionHandler->promptConfirm($question, $default);
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @param array $options ~ ex: ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @return mixed ~ selected value
     */
    protected function promptSelectOneOption($question, $default, $options=[])
    {
        return $this->userInteractionHandler->promptSelectOneOption($question, $default, $options);
    }

    /**
     * @param string $question
     * @param string|mixed $default
     * @param array $options ~ ex: ['Option1' => 'value', 'Option2' => 'value2', ...]
     * @return array ~ array of selected values
     */
    protected function promptSelectMultipleOptions($question, $default, $options=[])
    {
        return $this->userInteractionHandler->promptSelectMultipleOptions($question, $default, $options);
    }

    /**
     * @param string $version ~ 1.0.0
     */
    protected function setDBVersion($version)
    {
        file_put_contents(__DIR__.'/log/version.log', $version);

    }
}
