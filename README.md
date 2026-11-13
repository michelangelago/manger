# Manger

A Telegram bot for tracking food consumption, calories, and daily calorie goals.
Manage a shared food catalog, log portions in grams, and view compact summaries
for a day, week, month, or year.

Built with **PHP, Nutgram, and SQLite**. The bot uses long polling, so it does
not need a public URL or web server.

## HTML documentation

Open [docs/index.html](docs/index.html) for the complete documentation, including
setup, commands, architecture, database details, and tests. The pages work offline.
To browse them through a local server:

```bash
php -S 127.0.0.1:8080 -t docs
```

Then visit `http://127.0.0.1:8080`.

The implementation specification is organized into:

- [Functional requirements and use cases](docs/requirements.html).
- [Business rules and enforcement layers](docs/business-rules.html).
- [Architecture, flow, sequence, runtime, and entity diagrams](docs/diagrams.html).
- [Database dictionary, keys, indexes, and migrations](docs/data-dictionary.html).
- [Traceability from requirements to source and tests](docs/traceability.html).
- [Current limitations and unimplemented capabilities](docs/limitations.html).

These pages describe the current source. SVG diagrams work offline and include
editable Mermaid sources in `docs/diagrams/`.

## Contents

- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Using the bot](#using-the-bot)
- [Command reference](#command-reference)
- [Tests](#tests)
- [Project structure](#project-structure)
- [Database and repositories](#database-and-repositories)
- [Composer commands](#composer-commands)

## Quick start

### Requirements

- PHP 8.2 or newer, with PDO and `pdo_sqlite` enabled.
- Composer.
- A Telegram bot token from BotFather.

### Install and run

```bash
composer install
cp .env.example .env
```

Set `TELEGRAM_BOT_TOKEN` in `.env`, then run:

```bash
composer migrate
composer bot
```

In Telegram, send `/start` to register. Type `/` to open the command menu or
send `/help` for the full syntax. Stop the bot with **Ctrl+C**.

Migrations create the database and seed six food groups: **fruits, vegetables,
grains, protein, dairy, and fats**.

## Configuration

The bot loads configuration from the root `.env` file.

| Variable | Purpose | Default |
| --- | --- | --- |
| `TELEGRAM_BOT_TOKEN` | Token provided by BotFather; required to start the bot. | None |
| `APP_TIMEZONE` | Timezone used for daily logs and calendar summaries. | `America/Recife` |

Example:

```dotenv
TELEGRAM_BOT_TOKEN=your_bot_token
APP_TIMEZONE=America/Recife
```

On startup, the bot synchronizes its Telegram autocomplete menu. The menu lists
command names; descriptions and `/help` explain their arguments and flags.

## Using the bot

A typical workflow:

```text
/start
/food -C "Green apple" 52 fruits
/eat "Green apple" 150
/goal -C 2000
/dview
```

- Food calories are entered **per 100 grams**. Consumed portions are positive
  whole grams; calories are calculated as `energy × grams / 100`.
- Food and group names ignore letter case. Quote names containing spaces.
- Foods and groups form a **shared catalog**. Daily logs, consumption entries,
  and calorie goals belong to individual users.
- The first consumed-food entry creates that user's daily log for today.
  Further entries reuse it. Days without consumption do not create logs.
- Editing a consumption entry keeps its original date and time. Deleting all
  entries keeps the daily log available for reuse that day.
- Creating catalog foods with `/food -C` does not create daily logs.

## Command reference

### Operation flags

Flags ignore letter case. Commands support only the operations listed below.

| Flag | Operation |
| --- | --- |
| `-I` | Index / list |
| `-C` | Create |
| `-R` | Read |
| `-U` | Update |
| `-D` | Delete |

`/dview` has its own period flags: its `-D` means **day**.

### Registration and help

| Command | Result |
| --- | --- |
| `/start` | Registers your Telegram ID and full name, then shows your saved details. |
| `/help` | Lists available commands and their syntax. |

Sending `/start` again welcomes you back and updates your name without creating
a duplicate user.

### Food groups — `/fgroup`

```text
/fgroup -I
/fgroup -C "Whole grains"
/fgroup -R "Whole grains"
/fgroup -U "Whole grains" "Cereal grains"
/fgroup -D "Cereal grains"
```

Index also shows the available operations. Duplicate names are rejected. Groups
referenced by foods cannot be deleted.

### Food catalog — `/food`

```text
/food -I
/food -C "Green apple" 52 fruits
/food -R "Green apple"
/food -U "Green apple" "Apple yogurt" 90 dairy
/food -D "Apple yogurt"
```

Create takes a name, **calories per 100 g**, and an existing group name. Calories
must be a non-negative integer. The bot resolves the group's name to its database
ID internally.

Update takes the existing food name followed by the new name, calories, and
group name. Duplicate food names are rejected. Foods referenced by consumption
entries cannot be deleted. Index also shows the available operations.

### Log consumption — `/eat` and `/dfoods`

Quick logging:

```text
/eat "Green apple" 150
```

This is a shortcut for `/dfoods -C "Green apple" 150` and uses the same validation
and automatic daily log creation.

Full operations:

```text
/dfoods -I
/dfoods -C "Green apple" 150
/dfoods -R 1
/dfoods -U 1 "Green apple" 200
/dfoods -D 1
```

Register with `/start` first. Food names must exist in the catalog, and quantities
must be positive whole grams. Read, Update, and Delete use the **entry ID** shown
in the reply, because the same food can be logged more than once.

### Daily logs — `/daily`

```text
/daily -I
/daily -R
/daily -R 2026-11-13
```

Only Index and Read are available. Read shows the day's food entries and total
calories. Omit the date to use today in `APP_TIMEZONE`.

### Calorie summaries — `/dview`

```text
/dview
/dview "2026-11-13"
/dview -D 2026-11-13
/dview -W
/dview -M 2026-11-13
/dview -Y
```

| Flag | Period containing the selected date |
| --- | --- |
| `-D` | Day; the default. |
| `-W` | Calendar week, Monday through Sunday. |
| `-M` | Calendar month. |
| `-Y` | Calendar year. |

The date defaults to today in `APP_TIMEZONE`. A date and flag can appear in
either order.

Summaries show total calories first, then combined grams and calories per food.
Repeated foods are merged. Up to 15 foods are shown, ordered by calories; the
footer counts additional foods, which are still included in the total. Empty
periods show zero calories.

### Browse foods by group — `/gview`

```text
/gview
/gview "fruits"
```

View the entire catalog or one group. Foods appear alphabetically under their
group, with a compact name and calorie value beneath a `kcal/100 g` heading.
Empty groups are marked as empty. Large catalogs are split into short messages,
and long names are shortened for display.

### Daily calorie goals — `/goal`

```text
/goal -C 2000
/goal -R
/goal -U 1800
/goal -D
```

Register with `/start` first. Each user has one goal, expressed as a positive
whole number in **kcal/day**. Create refuses to overwrite an existing goal;
use Update to change it.

Today's `/dview` shows the goal and remaining calories, or calories over the
goal. Goals are current settings without historical versions, so this comparison
is omitted for past dates and weekly, monthly, and yearly summaries. Deleting
a goal does not remove food entries.

## Tests

```bash
composer test
```

GitHub Actions runs the same suite on every push and pull request, using PHP
8.2 and 8.5 with SQLite support. The workflow also validates Composer and checks
dependency requirements. No bot token or application database is needed.
View results in the repository's **Actions** tab, or run the workflow manually
there. Configuration: [.github/workflows/tests.yml](.github/workflows/tests.yml).

The runner groups results by file, prints `[OK]` or `[FAIL]` for each named case,
and finishes with totals and duration. Failures include details and produce a
non-zero exit status for CI.

| Location | Responsibility |
| --- | --- |
| `tests/Bot/` | Command integration tests using simulated Telegram updates. |
| `tests/Database/` | Migration, seed, constraint, and repository tests. |
| `tests/Support/TestContext.php` | Shared setup, assertions, and a fake bot client. |
| `tests/bootstrap.php` | Loads test dependencies. |
| `tests/run.php` | Discovers and runs `*Test.php` files recursively. |

Each test gets a fresh in-memory SQLite database. Tests do not modify application
data or send real Telegram messages.

To add a test, return an array of named callables from a `*Test.php` file. Each
callable receives a `Tests\Support\TestContext` instance:

```php
use Tests\Support\TestContext;

return [
    'Lists the default fruit group' => static function (TestContext $t): void {
        $t->command('/fgroup -I', 'fruits');
    },
];
```

## Project structure

```text
app/
  bot/
    bot.php             Polling entry point and menu synchronization
    bootstrap.php       Configuration, database connection, and dependencies
    commands.php        Telegram command routing
    menu.php            Autocomplete command definitions
    commands/           One handler per command
    support/            Input parsing and compact view formatting
  database/
    connection.php      SQLite connection
    migrate.php         Migration runner
    migrations/         Numbered schema changes and seeds
    repositories/       Table queries and persistence operations
public/
  index.php             Web environment status page
storage/
  database.sqlite       Local application database, ignored by Git
tests/                  Automated tests
```

Add new command handlers in `app/bot/commands/`, wire them in
`app/bot/commands.php`, and update `app/bot/menu.php` and `/help`. Keep queries
in repositories and reusable display formatting in `app/bot/support/`.

## Database and repositories

SQLite is stored at `storage/database.sqlite` and created automatically by the
connection. Foreign keys are enabled, errors throw PDO exceptions, and query
results use associative arrays.

### Migrations and tables

Run `composer migrate` after adding or updating the application. Completed
migrations are recorded and skipped on subsequent runs. Each migration runs
in a transaction. Add schema changes as new numbered files under
`app/database/migrations/`.

| Table | Main fields and purpose |
| --- | --- |
| `users` | Telegram ID, name, and registration timestamp. |
| `food_groups` | Group name. |
| `foods` | Name, energy in kcal/100 g, and food group ID. |
| `daily` | Name, user ID, and date; dated logs are unique per user and date. |
| `daily_foods` | Daily log ID, food ID, quantity in grams, and consumption timestamp. |
| `calorie_goals` | User ID and positive daily calorie target; one goal per user. |

`daily_foods.diet_id` references `daily.id`. New consumption timestamps include
the timezone offset. Existing undated daily logs are preserved as legacy rows.
Default group seeding preserves existing groups with matching names.

Daily log creation and its first consumption entry run in one transaction.
A unique index on `(user_id, date)` prevents duplicate dated logs, and a failed
entry does not leave a new empty log. Foreign keys prevent deleting referenced
records.

### Repository API

Repositories share these methods through `Repository`:

| Method | Result |
| --- | --- |
| `index(): array` | All rows, ordered by ID. |
| `create(array $data): int` | Inserts a row and returns its ID. |
| `read(int $id): ?array` | Matching row, or `null`. |
| `update(int $id, array $data): bool` | Updates writable fields; returns false if missing. |
| `delete(int $id): bool` | Deletes a row; returns false if missing. |

Empty data and unknown or read-only columns are rejected. Constraint violations
throw PDO exceptions. `UserRepository` also provides `save()` and
`findByTelegramId()`; foods and groups provide `findByName()`. Daily entries,
logs, and goals provide queries scoped to the current user. Use those scoped
methods when handling personal data in bot commands.

## Composer commands

| Command | Purpose |
| --- | --- |
| `composer bot` | Synchronize the Telegram menu and start long polling. |
| `composer migrate` | Apply pending migrations and seeds. |
| `composer test` | Run all automated tests. |
| `composer start` | Serve the optional web status page at `http://localhost:8000`. |

The web status page is separate from the Telegram bot. Stop either process
with **Ctrl+C**.
