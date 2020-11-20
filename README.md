# xPDO O/RB v3

[![Build Status](https://travis-ci.com/modxcms/xpdo.svg?branch=3.x)](https://travis-ci.com/github/modxcms/xpdo)

xPDO is an ultra-light object-relational bridge library for PHP 5.6+. It is a standalone library and can be used with any framework or DI container.

## Installation

xPDO can be installed in your project via composer:

    composer require xpdo/xpdo ^3.0@dev


## Usage

The `\xPDO\xPDO` class is the main point of access to the framework. Provide a configuration array describing the connection(s) you want to establish when creating an instance of the class.

```php
require __DIR__ . '/../vendor/autoload.php';

$xpdoMySQL = \xPDO\xPDO::getInstance('aMySQLDatabase', [
    \xPDO\xPDO::OPT_CACHE_PATH => __DIR__ . '/../cache/',
    \xPDO\xPDO::OPT_HYDRATE_FIELDS => true,
    \xPDO\xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    \xPDO\xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    \xPDO\xPDO::OPT_CONNECTIONS => [
        [
            'dsn' => 'mysql:host=localhost;dbname=xpdotest;charset=utf8',
            'username' => 'test',
            'password' => 'test',
            'options' => [
                \xPDO\xPDO::OPT_CONN_MUTABLE => true,
            ],
            'driverOptions' => [],
        ],
    ],
]);

$xpdoSQLite = \xPDO\xPDO::getInstance('aSQLiteDatabase', [
    \xPDO\xPDO::OPT_CACHE_PATH => __DIR__ . '/../cache/',
    \xPDO\xPDO::OPT_HYDRATE_FIELDS => true,
    \xPDO\xPDO::OPT_HYDRATE_RELATED_OBJECTS => true,
    \xPDO\xPDO::OPT_HYDRATE_ADHOC_FIELDS => true,
    \xPDO\xPDO::OPT_CONNECTIONS => [
        [
            'dsn' => 'sqlite:path/to/a/database',
            'username' => '',
            'password' => '',
            'options' => [
                \xPDO\xPDO::OPT_CONN_MUTABLE => true,
            ],
            'driverOptions' => [],
        ],
    ],
]);
```

## Migrations

1. Copy properties.sample.inc.php to be properties.inc.php and set for your DB.
2. SSH or in your terminal CD to the xPDO directory
3. Then run ```php bin/xpdo.php``` to see the list of commands
4. Run the mig-install command like so: ```php bin/xpdo.php xpdo:mig-install```
5. Once installed you can generate a blank migration file run: ```php bin/xpdo.php xpdo:mig-generate``` it will tell you what directory it is in
6. Once you have a completed migraiton file you can run all migrations or a single one by passing the name option.
```php bin/xpdo.php xpdo:mig-run``` would run all.

### Configuration options in properties.inc.php

Optionally define any of the following constants 

- XPDO_CONSOLE_COMMAND_NAMESPACE
- XPDO_MIGRATION_DIR
- XPDO_SEEDS_DIR

## Example to Generate the Migrations xPDO model:

Note the migration model already exists but the example exists if you need to generate your own model, just change the path.
1. cd to xPDO project root
2. ```php bin/xpdo xpdo:parse-schema mysql src/xPDO/Migrations/Model/schema/migrations.mysql.schema.xml  src/ --psr4=PSR4```
