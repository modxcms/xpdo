# xPDO Copilot Instructions

## Repository Overview

xPDO is a PDO-based Object/Relational Bridge (ORB) library for PHP. It wraps PDO to provide a lightweight ORM with support for MySQL, PostgreSQL, SQLite, and SQL Server. It is used as the database abstraction layer in MODX CMS.

- **Current version branch**: `3.x`
- **PHP requirement**: `>=7.2.5`
- **License**: GPL-2.0+

## Project Structure

```
src/xPDO/           # Main source code (namespace: xPDO\*)
  xPDO.php          # Core class — entry point for all database interaction
  Om/               # Object model: xPDOObject, xPDOQuery, xPDOManager, xPDOGenerator, etc.
  Cache/            # Cache layer: file, APC, Memcache, Redis, WinCache
  Transport/        # Package transport/vehicle system for deployable packages
  Console/          # Symfony Console CLI commands
  Compression/      # Zip/archive utilities
  Reflect/          # Reflection helpers
  Validation/       # Validator support
test/               # PHPUnit tests
  xPDO/
    Test/           # Modern test suite (PSR-4 model)
    Legacy/         # Legacy test suite
  model/            # Sample model used in tests
  bootstrap.php     # Test bootstrap
  properties.sample.inc.php   # Sample test connection properties
  properties.ci.inc.php       # CI test connection properties
  *.phpunit.xml     # PHPUnit config per driver (mysql, pgsql, sqlite, complete)
bin/                # CLI entry point (Symfony Console app)
vendor/             # Composer dependencies (not committed)
```

## Setting Up

Install dependencies with Composer:

```bash
composer install
```

## Running Tests

Tests require a `test/properties.inc.php` file. Copy the sample properties file to get started quickly:

```bash
cp test/properties.sample.inc.php test/properties.inc.php
```

Run tests against a specific database driver:

```bash
# SQLite (no external service needed — easiest for local development)
vendor/bin/phpunit -c ./test/sqlite.phpunit.xml

# MySQL (requires a running MySQL instance with database 'xpdotest')
vendor/bin/phpunit -c ./test/mysql.phpunit.xml

# PostgreSQL (requires a running PostgreSQL instance with database 'xpdotest')
vendor/bin/phpunit -c ./test/pgsql.phpunit.xml
```

The active driver is selected via the `TEST_DRIVER` environment variable (set in each phpunit XML config).

Run tests against all supported drivers:

```bash
vendor/bin/phpunit -c ./test/complete.phpunit.xml
```

### CI Test Setup

CI uses GitHub Actions (`.github/workflows/ci.yml`) and tests all three drivers across PHP 7.2–8.5. MySQL CI uses `root` with empty password; PostgreSQL uses `postgres`/`postgres`. SQLite needs no service.

## Writing Tests

- Extend `xPDO\TestCase` (in `test/xPDO/TestCase.php`), which extends `Yoast\PHPUnitPolyfills\TestCases\XTestCase`.
- Use `@before` / `@after` / `@beforeClass` annotations (not `setUp`/`tearDown` directly).
- Tests access the xPDO instance via `$this->xpdo`.
- The `Test/` suite uses a modern PSR-4 model under `test/model/PSR4/`.
- The `Legacy/` suite uses the older model under `test/model/sample/`. New tests should target the modern PSR-4 model only.
- Test fixtures (SetUpTest/TearDownTest) create and drop tables; always run the full suite in order.

## Coding Conventions

- Namespace: `xPDO\*` (PSR-0 autoloading via Composer, mapped from `src/` and `test/`).
- Class files follow the namespace hierarchy exactly (e.g. `xPDO\Om\xPDOObject` → `src/xPDO/Om/xPDOObject.php`).
- No strict coding standard tool is configured; match the existing style (K&R-style braces, 4-space indentation).
- PHP 7.2 is the minimum — avoid syntax or functions only available in PHP 8+.
- Add `#[\AllowDynamicProperties]` to classes that use dynamic properties (required for PHP 8.2+ compatibility).

## Key Classes

| Class | Purpose |
|-------|---------|
| `xPDO\xPDO` | Main entry point; manages connections, packages, caching, logging |
| `xPDO\Om\xPDOObject` | Base persistent object class; all model classes extend this |
| `xPDO\Om\xPDOSimpleObject` | xPDOObject with a simple integer primary key |
| `xPDO\Om\xPDOQuery` | Query builder used with `$xpdo->newQuery()` |
| `xPDO\Om\xPDOManager` | Schema/table management (create/alter/drop tables) |
| `xPDO\Om\xPDOGenerator` | Generates PHP class and map files from XML schema |
| `xPDO\Cache\xPDOCacheManager` | Manages cache providers |
| `xPDO\Transport\xPDOTransport` | Package transport for deployable content |

## Adding a New Database Driver

Driver-specific code lives under `src/xPDO/Om/<driver>/` (e.g., `mysql/`, `pgsql/`, `sqlite/`, `sqlsrv/`). Each driver directory contains driver-specific subclasses of `xPDODriver`, `xPDOManager`, `xPDOGenerator`, and `xPDOQuery`.

## Schema / Model Generation

xPDO uses XML schema files (see `test/model/schema/`) to define the object model. Generate PHP class and map files from a schema with:

```bash
bin/xpdo parse-schema [options] [--] <platform> <schema_file> [<path>]
```

Generate a schema from existing database tables with:

```bash
bin/xpdo write-schema [options] [--] <platform> <schema_file> <package> [<base_class> [<table_prefix>]]
```

## Common Errors and Workarounds

- **`test/properties.inc.php` missing**: Tests will fail immediately. Always copy `properties.sample.inc.php` to `properties.inc.php` and set the appropriate values for your environment before running tests.
- **PHP 8.2+ dynamic properties deprecation**: Classes using dynamic properties need the `#[\AllowDynamicProperties]` attribute. This has already been applied to `xPDO\xPDO`.
- **PHPUnit version mismatch**: The project uses `yoast/phpunit-polyfills` for compatibility across PHPUnit versions. Use `@before` / `@after` annotations rather than overriding `setUp()` / `tearDown()` directly.
- **`php_pgsql` extension name in CI**: The PostgreSQL CI job installs `php_pgsql` as the extension name; on some systems it may be `pgsql`. If PostgreSQL tests fail with a missing extension, verify extension availability with `php -m | grep pgsql`.
