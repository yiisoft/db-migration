# Modifying migration history

> [!WARNING]
> **Do not use `migrate:mark` unless you understand exactly how changing migration history will affect your database.**
>
> This command changes history records without changing or checking the database schema or data. Incorrectly marking
> migrations can leave history out of sync with the database, skip required changes, or cause later migration commands
> to fail or lose data. A later `migrate:up` will execute migrations whose records were removed, and `migrate:down` can
> revert migrations marked as applied even though this command never executed them.
>
> Before proceeding, verify that the database already matches the intended migration state and make a backup.

When adopting migrations for an existing database, or after making a migration's changes manually, use `migrate:mark`
to move migration history to a particular version without executing `up()` or `down()`:

```shell
./vendor/bin/yii-db-migration migrate:mark 'App\Migrations\M260101000002CreateIndex'
./vendor/bin/yii-db-migration migrate:mark M260101000002CreateIndex
./vendor/bin/yii-db-migration migrate:mark 260101000002
```

Use the full class name, including its namespace when present, or a 12-digit migration timestamp. A timestamp with an
underscore between date and time (`260101_000002`) is also accepted. These are migration filename timestamps, not Unix
timestamps or date strings. If several migrations share a timestamp, the first matching migration in the relevant list
is selected; use its full class name to select an exact target.

The command modifies history as follows:

- If the target is pending, record all pending migrations in discovery order through and including the target.
  Existing history entries remain unchanged.
- If the target is already recorded, remove entries newer than it in migration history, keeping the target and older
  entries. This uses application order, not filename order, and does not require the recorded migration files to exist.
- If the target is already the latest recorded entry, nothing changes.
- If the target is unknown, report an error without adding or removing history entries.

To clear the entire history, use the special base version:

```shell
./vendor/bin/yii-db-migration migrate:mark m000000_000000_base
```

The command asks for confirmation before adding or removing entries. Use `--force-yes` (`-y`) for unattended execution.
With multiple databases configured, select one explicitly using `--db=maps`.
