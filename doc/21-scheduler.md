# Scheduler (cron)

This app uses **Laravel’s built-in Task Scheduler** — not custom shell scripts or per-job crontab lines. Recurring work is defined in **`config/schedule.php`**, registered on Laravel’s scheduler at boot, and triggered by a **single** system cron entry (or one long-running `schedule:work` process).

There is no separate `app:cron` command. Use Laravel’s `schedule:*` Artisan commands and the underlying job commands listed below.

## How it works

```
config/schedule.php
        ↓
ScheduleRegistrar (bootstrap/app.php → withSchedule)
        ↓
Laravel Schedule (Illuminate\Console\Scheduling\Schedule)
        ↓
php artisan schedule:run   ← invoked every minute by cron or schedule:work
        ↓
Due tasks run (Artisan commands from config)
```

1. **`config/schedule.php`** — list of tasks: Artisan command, frequency, optional `when` / `enabled` flags.
2. **`ScheduleRegistrar`** — reads that config and registers tasks on Laravel’s scheduler at boot (`bootstrap/app.php`).
3. **`php artisan schedule:run`** — runs once; Laravel executes whichever tasks are due now.
4. **`php artisan schedule:work`** — dev/Docker alternative: loops forever and invokes `schedule:run` each minute.

## CLI reference

### Run all scheduled tasks

| Command | When to use |
|---------|-------------|
| `php artisan schedule:run` | Production — add to crontab, runs every minute |
| `php artisan schedule:work` | Local dev or Docker — long-running worker |

`schedule:run` only executes tasks whose cron expression is due at that moment. It does not force-run every task.

### Run one scheduled task

By **name** from `config/schedule.php` (respects `when` / `enabled`):

```bash
php artisan schedule:test --name=publications-notify-due
php artisan schedule:test --name=subscription-digest
php artisan schedule:test --name=stats-snapshot
php artisan schedule:test --name=notification-mail-queue
```

### Run job commands directly

Bypasses the scheduler (useful for debugging; ignores `when` / timing):

| Task | Direct command |
|------|----------------|
| Scheduled publish + subscriber notify | `php artisan publications:notify-due` |
| Daily subscription digest | `php artisan subscriptions:send-digest` |
| Author stats snapshot | `php artisan stats:snapshot` |
| Queued notification mail | `php artisan queue:work --stop-when-empty --queue=mail --max-jobs=50` |

`stats:snapshot` also accepts `--year=`, `--month=`, and `--user=` options.

### Inspect and manage

```bash
php artisan schedule:list          # list registered tasks and next run times
php artisan schedule:pause           # pause the scheduler
php artisan schedule:resume          # resume
php artisan schedule:clear-cache     # clear mutex cache
```

## Production crontab (single entry)

Add this once for the app user:

```cron
* * * * * cd /path/to/magazines && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/magazines` with your deploy path. Use the same PHP binary as the web app if they differ.

## Docker

`docker-compose.yml` includes a **`scheduler`** service that runs:

```bash
php artisan schedule:work
```

No host crontab is required when using Compose.

## Local development

Either rely on Docker’s scheduler service, or in a separate terminal:

```bash
php artisan schedule:work
```

## Configuration

| Env / config | Purpose |
|--------------|---------|
| `SCHEDULER_ENABLED` | Master switch (`config('schedule.enabled')`). When `false`, no tasks are registered. |
| `schedule.tasks[]` | Each task: `command`, `frequency`, optional `time`, `parameters`, `when`, `enabled`, `name` |

### Frequencies

| `frequency` | Extra fields |
|-------------|--------------|
| `every_minute` | — |
| `hourly` | — |
| `daily` | — |
| `daily_at` | `time` — e.g. `08:00` |
| `weekly` | — |
| `monthly` | — |
| `* * * * *` (cron) | Use a standard 5-field cron string directly as `frequency` |
| `cron` | `expression` — alternative to putting the cron string in `frequency` |

Example with cron notation:

```php
[
    'name' => 'my-task',
    'command' => 'my:command',
    'frequency' => '0 8 * * *',  // daily at 08:00
],
```

Every minute:

```php
'frequency' => '* * * * *',
```

### Conditional tasks (`when`)

Run a task only when a config value matches:

```php
'when' => [
    'config' => 'notifications.email_delivery',
    'equals' => 'queue',
],
```

### Disable a task

Per task:

```php
'enabled' => false,
```

Or via env on the task definition (see subscription digest in `config/schedule.php`).

## Default tasks

| Name | Command | Schedule | Purpose |
|------|---------|----------|---------|
| `subscription-digest` | `subscriptions:send-digest` | Daily at `SUBSCRIPTION_DIGEST_TIME` (if `SUBSCRIPTION_EMAIL_ENABLED`) | Email daily digests to subscribers who chose daily delivery |
| `stats-snapshot` | `stats:snapshot` | Daily at 01:00 | Persist author and per-story monthly stats |
| `publications-notify-due` | `publications:notify-due` | Every minute | Publish posts whose `publish_at` has passed; notify subscribers |
| `notification-mail-queue` | `queue:work --stop-when-empty …` | Every minute when `NOTIFICATION_EMAIL_DELIVERY=queue` | Drain queued notification emails |

## Adding a new scheduled command

1. Implement the Artisan command (e.g. `app/Console/Commands/MyCommand.php`).
2. Append an entry to **`config/schedule.php`**:

```php
[
    'name' => 'my-task',
    'command' => 'my:command',
    'frequency' => 'hourly',
],
```

3. Run `php artisan schedule:list` to confirm it appears.
4. No crontab changes — the existing `schedule:run` entry picks it up automatically.

## Key files

| File | Role |
|------|------|
| `config/schedule.php` | Task definitions |
| `app/Console/Scheduling/ScheduleRegistrar.php` | Config → Laravel scheduler |
| `bootstrap/app.php` | Calls `ScheduleRegistrar` in `withSchedule()` |
| `docker-compose.yml` | `scheduler` service |
