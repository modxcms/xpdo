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

Exit codes: `0` success, `1` failure. Commands return Symfony status codes; they do not call `exit()`. Stack traces appear only with `-v` / `-vv` / `-vvv`.

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

### `migrate-rollback`

Reverts `MAX(batch)` in descending version order. Optional `--step=N` caps how many of those rows to reverse.

## CI

Commands take no prompts. Pass `--config` and `--platform` (or set `xpdo_driver` in properties).
