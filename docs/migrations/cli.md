# xPDO Migrations: CLI reference

Ledger-backed database migrations for xPDO 3.x. You run them through the same `bin/xpdo` binary as `parse-schema` and `write-schema`.

```bash
php bin/xpdo <command> [options]
```

Command names use hyphens: `migrate-create`, `migrate-status`, `migrate`, `migrate-rollback`. There is no `migration:*` Symfony namespace.

Drivers covered by CI and this tooling: **mysql**, **pgsql**, **sqlite**. `sqlsrv` is listed on `--platform` like other Console commands, but migrations are not a sqlsrv CI gate.

## Quick start

```bash
# 1. Point a properties file at your DB + migrations directory
# 2. Scaffold a stub
php bin/xpdo migrate-create CreateWidgetTable \
  -C path/to/properties.inc.php --platform=sqlite

# 3. Edit the generated file: implement up() / down()

# 4. Inspect ledger vs files
php bin/xpdo migrate-status \
  -C path/to/properties.inc.php --platform=sqlite

# 5. Apply pending
php bin/xpdo migrate \
  -C path/to/properties.inc.php --platform=sqlite

# 6. Reverse the last batch if needed
php bin/xpdo migrate-rollback \
  -C path/to/properties.inc.php --platform=sqlite
```

Commit migration PHP files to Git with your application. The ledger table lives in the database; do not invent ledger rows by hand.

## Configuration

Commands load a PHP properties file the same way other xPDO Console commands do (`--config` / `-C`, or the usual discovery for `properties.inc.php`).

You need:

| Key | Required | Meaning |
|-----|----------|---------|
| `{platform}_array_options` | yes | xPDO connection options for that platform (same shape as schema tools) |
| `migrations_path` | yes* | Local directory for `*.php` migration files |
| `migrations_namespace` | yes* | PHP namespace for generated classes |
| `xpdo_driver` | optional | Default platform when `--platform` is omitted |
| `migrations_table` | optional | Ledger table name (default `xpdo_migrations`) |
| `transaction_policy` | optional | `non_transactional` (default) or `transactional` |
| `migrations_lock_name` | optional | Extra salt for the migrate lock key |

\* Required unless you pass `--path` / `--namespace` on the CLI.

Example keys (also commented in `test/properties.sample.inc.php`):

```php
$properties['migrations_path'] = __DIR__ . '/migrations';
$properties['migrations_namespace'] = 'App\\Migrations';
// $properties['migrations_table'] = 'xpdo_migrations';
// $properties['transaction_policy'] = 'non_transactional';
```

Map the namespace to the path in Composer PSR-4, or rely on the runner `require`-ing each file once. Paths must be local filesystem paths (no `http://`, `phar://`, …). Table and lock names must match `^[A-Za-z_][A-Za-z0-9_]*$`.

### Shared CLI options

| Option | Meaning |
|--------|---------|
| `--config` / `-C` | Properties file |
| `--platform` | `mysql`, `sqlite`, `pgsql`, or `sqlsrv` (falls back to `xpdo_driver`) |
| `--path` | Override `migrations_path` |
| `--namespace` | Override `migrations_namespace` |
| `--table` | Override `migrations_table` |

`migrate` and `migrate-rollback` also accept `--step=N` (positive integer). Invalid `--step` exits `1`.

### Exit codes and verbosity

| Code | Meaning |
|------|---------|
| `0` | Success (`Command::SUCCESS`) |
| `1` | Failure (`Command::FAILURE`) |

Commands return Symfony status codes. They do not call `exit()`. Fatal lines print as `fatal: …`. With `-v` / `-vv` / `-vvv` you also get a stack trace. No interactive prompts: safe for CI.

## Identity, discovery, ledger

**Identity** = migration name = filename stem:

```text
YYYYMMDDHHMMSS_Description
```

Example: `20260814120000_CreateWidgetTable`.

| Artifact | Value |
|----------|--------|
| File | `{name}.php` under `migrations_path` |
| Class | `{namespace}\M{name}` (prefix `M` + full stem) |
| Ledger `version` | same stem, `UNIQUE`, max 191 chars |

Discovery only loads `*.php` files matching `^[0-9]{14}_[A-Za-z][A-Za-z0-9]*$`. Other PHP files and subdirectories are ignored. Duplicate names, missing classes, or classes that do not extend `Migration` fail the command.

**Ordering:** pending apply ascending by name. Rollback within a batch runs descending by name. The 14-digit UTC timestamp prefix is the ordering signal.

**Ledger** (default table `xpdo_migrations`): `id`, `version`, `batch`, `applied_at` (UTC). A row means “applied”. No row + file present means “pending”. A row without a file is **orphaned** (shown by `migrate-status`; rollback of that version fails until you restore the file).

## Commands

### `migrate-create`

```bash
php bin/xpdo migrate-create CreateWidgetTable \
  -C path/to/properties.inc.php --platform=sqlite \
  --path=./migrations --namespace='App\Migrations'
```

- Argument `description`: non-alphanumerics stripped; remainder must match `[A-Za-z][A-Za-z0-9]*`.
- Writes a stub with `up()` / `down()` (`down()` throws `IrreversibleMigrationException` until you implement it).
- Creates the file with exclusive open (`fopen(..., 'x')`). Same-second collisions bump the timestamp forward instead of overwriting.
- Does **not** need a live database connection.

On success the CLI prints the created path/name. Commit the new file before you rely on other environments applying it.

### `migrate-status`

```bash
php bin/xpdo migrate-status -C path/to/properties.inc.php --platform=sqlite
```

Read-only. Does **not** create the ledger table.

| Output | Meaning |
|--------|---------|
| `Repository: missing` | No ledger table yet. Applied/pending unknown until first `migrate`. |
| `Repository: ok` | Lists Applied, Pending, and Orphaned when present. |

Orphans print a warning: restore missing files before `migrate` / `migrate-rollback`. Exit code is still `0` for a successful status read.

### `migrate`

```bash
php bin/xpdo migrate -C path/to/properties.inc.php --platform=sqlite
php bin/xpdo migrate --step=1 -C path/to/properties.inc.php --platform=sqlite
```

1. Acquires the migrate lock.
2. Creates the ledger table if needed.
3. Discovers files, computes pending.
4. Assigns one **batch** id for this run (`MAX(batch)+1`) when there is work.
5. Runs each pending `up()` in ascending name order (honors `--step`).
6. Inserts a ledger row only after that `up()` returns.
7. Releases the lock.

Output examples:

- `Nothing to migrate.`
- `Applied batch 3:` then a bullet list; if more remain, `Still pending:`.

**Failure:** the failed version is not recorded. Later pending in the same invocation are not run. Earlier successes in that batch stay applied (partial batch). Fix the migration and run `migrate` again. To undo a partial batch, run `migrate-rollback` (it targets `MAX(batch)`, which is that partial batch if it is latest).

### `migrate-rollback`

```bash
php bin/xpdo migrate-rollback -C path/to/properties.inc.php --platform=sqlite
php bin/xpdo migrate-rollback --step=1 -C path/to/properties.inc.php --platform=sqlite
```

Reverses rows with `batch = MAX(batch)`, highest version first.

Example: batch 2 has `C` then `D` applied. Default rollback runs `D` then `C`. `--step=1` runs only `D`. Batch 1 (`A`, `B`) is untouched until you roll back again after batch 2 is gone.

| Case | Behavior |
|------|----------|
| Empty ledger / nothing to roll back | `Nothing to roll back.` exit `0` |
| `down()` throws | Abort; that ledger row stays |
| `IrreversibleMigrationException` | Abort; row stays |
| Migration file missing (orphan) | Abort; row stays |

No silent ledger deletes without a successful `down()`.

## Transactions

Set `transaction_policy` in properties (or leave the default).

| Policy | Behavior |
|--------|----------|
| `non_transactional` (**default**) | Run `up`/`down`, then write the ledger. No outer transaction around both. |
| `transactional` | One xPDO transaction per migration: `up`/`down` + ledger write, then commit. Errors roll back that transaction when the driver still has one open. |

Default is `non_transactional` because MySQL DDL often implicit-commits. A “transactional” migration that runs `CREATE TABLE` on MySQL still will not give you DDL atomicity. Prefer DML inside `transactional` migrations, or accept non-atomic DDL and write idempotent `up()` where you can.

Crash window under the default: `up()` finished, process died before the ledger insert. Schema may already have changed while status still shows pending. Repair by hand, then re-run `migrate` (idempotent `up()` helps).

Per-migration override: implement `isTransactional(): ?bool` on the class (`true` / `false` / `null` = use global config).

## Locks

`migrate` and `migrate-rollback` take a fail-fast lock so two processes do not apply the same version twice.

| Driver | Mechanism |
|--------|-----------|
| mysql | `GET_LOCK` (timeout 0) |
| pgsql | `pg_try_advisory_lock` |
| sqlite | `flock` on `{migrations_path}/.xpdo_migration.lock` |

A second run that cannot acquire the lock fails with a lock error. Locks are per database / table (and optional `migrations_lock_name`). SQLite’s file lock is not a distributed lock across NFS or unrelated hosts sharing one DB file carelessly.

`migrate-status` and `migrate-create` do not take this lock.

## Failure recovery cheat sheet

| Situation | What you do |
|-----------|-------------|
| `up()` threw | Fix the PHP; run `migrate` again. Failed version was never recorded. |
| Partial batch (A ok, B failed) | Fix B and `migrate`, or `migrate-rollback` to undo A if that batch is still `MAX(batch)`. |
| `down()` threw | Fix `down()`; run `migrate-rollback` again. Row still present. |
| Orphaned ledger row | Restore the file from Git (or history). Do not delete the row by hand unless you know the schema state. |
| Rename file / change stem | Treated as a new identity. Old version becomes orphaned; new file is pending. Avoid renames after apply. |

## PHP API

Same behavior without Console:

```php
use xPDO\Migrations\MigrationConfig;
use xPDO\Migrations\Migrator;

$migrator = new Migrator($xpdo, MigrationConfig::fromArray([
    'migrations_path' => __DIR__ . '/migrations',
    'migrations_namespace' => 'App\\Migrations',
    // 'migrations_table' => 'xpdo_migrations',
    // 'transaction_policy' => 'non_transactional',
]));

$migrator->create('CreateWidgetTable');
$status = $migrator->status();   // MigratorResult
$migrator->migrate();            // or migrate(1)
$migrator->rollback();           // or rollback(1)
```

Stable types for consumers: `Migrator`, `Migration`, `MigrationContext`, `MigrationConfig`, `MigratorResult`. Discoverer / repository / executor / lock are `@internal`.

## CI

Pass `--config` and `--platform` (or set `xpdo_driver`). Example:

```bash
php bin/xpdo migrate -C deploy/properties.inc.php --platform=mysql
```

No prompts. Treat exit code `1` as a failed deploy step.
