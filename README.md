# xPDO O/RB v3

[![Build Status](https://github.com/modxcms/xpdo/workflows/CI/badge.svg?branch=3.x)](https://github.com/modxcms/xpdo/workflows/CI/badge.svg?branch=3.x)

xPDO is an ultra-light object-relational bridge library for PHP 5.6+. It is a standalone library and can be used with any framework or DI container.

## Installation

xPDO can be installed in your project via composer:

    composer require xpdo/xpdo ^3.0


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
