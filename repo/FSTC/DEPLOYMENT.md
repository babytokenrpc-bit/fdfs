# Telegram Mini App deployment

## 1. Set environment variables
Copy `.env.example` to `.env` on the server and fill every value.
Recommended: configure variables in the hosting panel instead of storing `.env` on the server.

Never commit `.env`, bot tokens, database passwords, backups, or `tdata.txt`.

## 2. Database
Create a MySQL/MariaDB database and a restricted application user, then open:

`https://your-domain.example/database/create_tables.php`

Delete `database/create_tables.php` after initialization.

## 3. Telegram bot webhook
Set the webhook to:

`https://your-domain.example/bot/index.php`

Telegram Web App API and Bot API require HTTPS.
Set `TELEGRAM_WEBHOOK_SECRET` to a random value and pass the same value as `secret_token` in `setWebhook`.

Example Bot API call:

`https://api.telegram.org/bot<BOT_TOKEN>/setWebhook?url=https://your-domain.example/bot/index.php&secret_token=<WEBHOOK_SECRET>`

## 4. Telegram Web App
Open BotFather, configure `/setmenubutton` or the Mini App URL, and set:

`https://your-domain.example/`

## 5. Hosting requirements
- PHP 8.1+
- mysqli and cURL extensions
- HTTPS / valid certificate
- writable `bot/` for maintenance file and temporary backup
- outgoing HTTPS access to `api.telegram.org`

## 6. Important production checks
- Rotate the bot token exposed previously
- Replace all `FSTCApp`, `FSTC_community`, `FSTC_test_bot`, `dogotap.com`, and staging URLs
- Add a real `bot/home.jpg` image or remove photo sending
- Validate Telegram `initData` server-side on every API mutation
- Restrict database and API permissions
- Configure scheduled/public-send handling through a protected cron endpoint
