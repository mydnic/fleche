# Fleche 🏹

Todos that show up on their own. Write rules ("every Wednesday", "1 in 7 chance",
"last day of the month", "at most every 2 weeks"), Fleche drops the todos in your
list each night and sends the list every morning by email or Telegram. Earn points,
spend them on reward rules, share rule packs on the community hub.

## Self-host

```sh
cp deploy/.env.example .env   # APP_URL, DB_PASSWORD, TELEGRAM_*, MAIL_*
docker compose up -d
```

One `app` container (nginx + PHP-FPM + queue worker + scheduler) plus Postgres and
Redis. On boot the entrypoint generates `APP_KEY` into the `storage` volume (once),
migrates, and creates the admin from `ADMIN_EMAIL`/`ADMIN_PASSWORD`, or shows a
setup screen on first visit. TLS: put a reverse proxy in front.

- **Mail**: set a `MAIL_*` driver, needed for the morning email and password resets.
- **Telegram**: create a bot with @BotFather, set `TELEGRAM_BOT_TOKEN` and
  `TELEGRAM_BOT_USERNAME`. Users click "Connect Telegram" in Settings; while they press
  Start in Telegram, that page asks the bot for the message. No webhook, no
  public URL, no background job.
- `REGISTRATION_ENABLED=false` makes `/app/register` a 404.
- **Images** (pictures on rules/todos): `FILESYSTEM_DISK=public` (default) keeps them in the
  storage volume; any Laravel disk works, e.g. `FILESYSTEM_DISK=s3` pointed at Cloudflare R2
  (`AWS_*`, `AWS_ENDPOINT`, public `AWS_URL`), which is what fleche.io uses.
- **Timezones** are per user (detected at sign-up, editable in Settings): rules
  roll once a day at the hour each user's day starts (Settings, default 07:00),
  and their list is sent right after.

## Scheduler

| Command | When |
| --- | --- |
| `fleche:generate [--date=]` | hourly; starts the day of users whose chosen hour it is (local time): rolls their rules, then sends their list. `--date` = generate only, everyone, that day |
| `fleche:prune` | 03:00, cloud free tier retention |
| `fleche:install` | entrypoint, first admin from env |

## API

Generate a key in Settings (docs are on that page). `Authorization: Bearer <key>`,
base `/api/v1`: `todos` (filters `from`, `to`, `active`), `todos/{id}/done` (final,
no undo), `todo-settings` CRUD.

## Develop

```sh
corepack enable              # Yarn 4, pinned in package.json
composer install && yarn install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed    # demo user test@example.com / password
yarn dev
php artisan test
```
