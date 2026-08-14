# xPDO Migrations: CLI reference

Same binary as `parse-schema` / `write-schema`:

```bash
php bin/xpdo <command> [options]
```

Command names use hyphens (`migrate-create`). There is no `migration:*` Symfony namespace.

## Shared options

| Option | Meaning |
|--------|---------|
| `--config` / `-C` | Properties file (`Command::loadConfig`) |
| `--platform` | `mysql`, `sqlite`, `pgsql`, or `sqlsrv` (default: `xpdo_driver` in config) |
| `--path` | Override `migrations_path` |
| `--namespace` | Override `migrations_namespace` |
| `--table` | Override `migrations_table` |

Config needs `{platform}_array_options`, plus `migrations_path` and `migrations_namespace` unless you pass CLI overrides.

Optional config keys: `migrations_table` (default `xpdo_migrations`), `transaction_policy` (`non_transactional` default, or `transactional`), `migrations_lock_name`.

Exit codes: `0` success, `1` failure. Commands return Symfony status codes; they do not call `exit()`. Stack traces appear only with `-v` / `-vv` / `-vvv`.

## Identity and ordering

- Migration identity = filename stem matching `YYYYMMDDHHMMSS_Description` (UTC timestamp + description).
- Class name = `M{stem}` in the configured namespace.
- Ledger column `version` stores that stem. It is UNIQUE.
- Pending runs in ascending name order. Rollback of a batch runs descending.

## Commands

### `migrate-create`

```bash
php bin/xpdo migrate-create CreateWidgetTable -C path/to/properties.inc.php --platform=sqlite
```

- Description must match `[A-Za-z][A-Za-z0-9]*` after non-alphanumerics are stripped.
- Writes `{YYYYMMDDHHMMSS_Description}.php` with class `M{name}` in the configured namespace.
- Refuses overwrite (exclusive create; timestamp moves forward on collision).

### `migrate-status`

Reads ledger state. Does not create the repository table.

- `Repository: missing` if the ledger table is absent.
- Otherwise prints Applied, Pending, and Orphaned (ledger rows with no file) when present.

### `migrate`

Runs pending migrations in ascending name order. Optional `--step=N`.

Creates the ledger table if needed. Failed `up()` is not recorded; later pending in that run are skipped. Re-run `migrate` after fixing.

### `migrate-rollback`

Reverts `MAX(batch)` in descending version order. Optional `--step=N` caps how many of those rows to reverse.

Missing migration file or failed `down()` aborts and leaves that ledger row. Orphaned rows are never deleted silently.

## Transactions

Default `transaction_policy` is `non_transactional`: `up`/`down` run outside an outer transaction, then the ledger row is written. MySQL DDL often implicit-commits, so the default does not pretend DDL is atomic.

With `transactional`, each migration wraps `up`/`down` + ledger write in one xPDO transaction. Prefer DML inside transactional migrations; treat MySQL DDL as non-atomic regardless.

A crash after a successful `up()` but before the ledger insert (non-transactional) can leave schema changed and the version still pending. Fix manually; write idempotent `up()` where you can.

## Locks

`migrate` / `migrate-rollback` take a fail-fast lock (MySQL `GET_LOCK`, PostgreSQL advisory lock, SQLite `flock` on a file under `migrations_path`). A second concurrent run fails instead of double-applying.

## PHP API

```php
use xPDO\Migrations\MigrationConfig;
use xPDO\Migrations\Migrator;

$migrator = new Migrator($xpdo, MigrationConfig::fromArray([
    'migrations_path' => __DIR__ . '/migrations',
    'migrations_namespace' => 'App\\Migrations',
]));
$migrator->create('CreateWidgetTable');
$migrator->status();
$migrator->migrate();
$migrator->rollback();
```

## CI

Commands take no prompts. Pass `--config` and `--platform` (or set `xpdo_driver` in properties).
