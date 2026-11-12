# manger

Requires PHP 8.2 or newer and Composer.

```bash
composer install
composer start
```

Open http://localhost:8000. Stop the development server with Ctrl+C.

## Telegram bot

The bot entry point is `app/bot/bot.php`. Its configuration is loaded from the root `.env` file.

Install dependencies with `composer install`, then copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Set `TELEGRAM_BOT_TOKEN` in `.env` to the token provided by Telegram's BotFather.

Start the bot in a terminal:

```bash
composer migrate
composer bot
```

The bot uses long polling, so no web server or public URL is required.
Send `/start` or `/hello Maria` to your bot in Telegram. Stop it with Ctrl+C.

`/start` saves the sender's Telegram ID and full name (first name and optional
last name) in `users` before confirming registration. Sending it again updates
the name without creating a duplicate user. `/hello` only sends a greeting.

Bot modules:

- `app/bot/bot.php` starts polling.
- `app/bot/bootstrap.php` loads configuration, connects the database, and wires dependencies.
- `app/bot/commands.php` maps Telegram commands to their handlers.
- `app/bot/commands/StartCommand.php` registers users and displays their saved information.
- `app/bot/commands/HelloCommand.php` sends personalized greetings.
- `app/bot/commands/HelpCommand.php` lists commands through `/help`.

To add a command, create a handler in `app/bot/commands/` and register it in
`app/bot/commands.php`. Database queries stay in the repositories.

### Food group commands

Run `composer migrate` to add the default groups: fruits, vegetables, grains,
protein, dairy, and fats. Existing groups with matching names are preserved.

```text
/fgroup Index
/fgroup Create "Whole grains"
/fgroup Read "Whole grains"
/fgroup Update "Whole grains" "Cereal grains"
/fgroup Delete "Cereal grains"
```

Operations and name lookups ignore letter case. Quote names containing spaces.
Send `/fgroup` for usage instructions. Duplicate names are rejected by the
command, and groups referenced by foods cannot be deleted. Food groups are shared
across bot users. `FoodGroupRepository::findByName()` returns the matching row or
`null`.

`/fgroup Index` also displays the available operations. `/help` lists all bot
commands. Run `php tests/food_groups.php` to test the commands using simulated
Telegram messages and an in-memory database.

## Database

Requires the PHP PDO and `pdo_sqlite` extensions. Load the SQLite connection
from application code (for example, from `app/bot/bot.php`):

```php
$pdo = require dirname(__DIR__) . '/database/connection.php';
```

The connection creates `storage/database.sqlite` automatically. Database files
are ignored by Git. PDO throws exceptions on database errors and returns rows
as associative arrays. Foreign key enforcement is enabled.

### Users table

Run `composer migrate` to create the `users` table. The runner records completed
migrations, so running it again skips them. Each migration runs in a transaction.
Add future schema changes as new numbered files in `app/database/migrations/`.

`app/database/repositories/UserRepository.php` handles user queries separately
from the migration that defines the table. For example, inside `app/bot/bot.php`:

```php
require_once dirname(__DIR__) . '/database/repositories/UserRepository.php';
$pdo = require dirname(__DIR__) . '/database/connection.php';
$users = new \App\Database\Repositories\UserRepository($pdo);

$users->save(123456789, 'Maria');
$user = $users->findByTelegramId(123456789);
```

`save()` inserts a user or updates their name when the Telegram ID already exists.
`findByTelegramId()` returns an associative array, or `null` when no user matches.
The sample uses a placeholder Telegram ID; use the actual sender's ID in bot handlers.

### Food tracking tables

Run `composer migrate` to apply the additional schema:

- `food_groups`: `id`, `name`.
- `foods`: `id`, `name`, `energy`, `food_group_id`.
- `daily`: `id`, `name`, `user_id`.
- `daily_foods`: `id`, `diet_id`, `food_id`, `quantity`, `consumed_at`.

`daily_foods.diet_id` references `daily.id`. `quantity` is measured in grams;
store `consumed_at` as a date and time string such as `2026-11-11 12:30:00`.
Foreign keys prevent references to missing records and deletion of referenced
records. SQLite uses `TEXT` for the diagram's `TEXT(65535)` columns.

### Repository operations

Each table has a repository in `app/database/repositories/`: `UserRepository`,
`FoodGroupRepository`, `FoodRepository`, `DailyRepository`, and `DailyFoodRepository`.
They share the following operations through `Repository`:

- `index(): array` lists all rows ordered by ID.
- `create(array $data): int` inserts a row and returns its ID.
- `read(int $id): ?array` returns a row, or `null` if missing.
- `update(int $id, array $data): bool` updates the supplied fields and returns false if missing.
- `delete(int $id): bool` deletes a row and returns false if missing.

For example, from `app/bot/bot.php`:

```php
require_once dirname(__DIR__) . '/database/repositories/FoodGroupRepository.php';
$pdo = require dirname(__DIR__) . '/database/connection.php';
$groups = new \App\Database\Repositories\FoodGroupRepository($pdo);

$id = $groups->create(['name' => 'Fruit']);
$all = $groups->index();
$group = $groups->read($id);
$groups->update($id, ['name' => 'Fresh fruit']);
$groups->delete($id);
```

Pass column names as array keys. Empty arrays and unknown or read-only columns
are rejected; database constraint failures throw PDO exceptions. Delete dependent
rows first when a foreign key prevents deletion. Existing user methods `save()`
and `findByTelegramId()` remain available.
