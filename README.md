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
composer bot
```

The bot uses long polling, so no web server or public URL is required.
Send `/start` or `/hello Maria` to your bot in Telegram. Stop it with Ctrl+C.
