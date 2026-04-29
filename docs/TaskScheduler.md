# Task Scheduler

> Schedule a PHP task script to run at a specific date/time, on a recurring interval, or both. Companion to `True\TaskQueue` (which runs tasks immediately).

## How it works

`TaskScheduler` keeps a SQLite table of upcoming tasks (`next_run`, optional `interval_seconds`). A small worker script invoked once a minute by cron asks the scheduler to run any tasks whose `next_run` is in the past. Recurring tasks are automatically re-scheduled; one-shots are deleted on success or kept with an error message on failure.

## Setup

The runner is self-contained — no project-level bootstrap file is required. Just install the cron line (below) and start scheduling tasks.

The SQLite database is created on first use at `BP/app/data/scheduled-tasks.sqlite`. No migrations to run.

To **schedule** a task (from a controller, CLI script, or anywhere else in your app), open the same SQLite file:

```php
$scheduler = new True\TaskScheduler(BP . '/app/data/scheduled-tasks.sqlite');
$scheduler->addTask('cleanup.php', ['olderThan' => '30 days'], ['runAt' => 'tomorrow 3am']);
```

## Scheduling tasks

Drop your task script into `app/tasks/`, then schedule it from anywhere in your app. Construct a `TaskScheduler` against the same SQLite file the runner uses (`BP/app/data/scheduled-tasks.sqlite`).

### One-shot at a specific time

```php
$scheduler = new True\TaskScheduler(BP . '/app/data/scheduled-tasks.sqlite');
$scheduler->addTask('cleanup.php', ['olderThan' => '30 days'], [
    'name'  => 'Clean up temp uploads',
    'runAt' => '2026-05-01 14:00:00',
]);
```

### Recurring at a fixed interval

```php
// Every 10 minutes
$scheduler->addTask('refresh-cache.php', [], [
    'name'            => 'Cache refresh',
    'intervalSeconds' => 600,
]);
```

### Recurring with a fixed start time (e.g. daily at 3am)

```php
$scheduler->addTask('reindex.php', [], [
    'name'            => 'Daily search reindex',
    'runAt'           => 'tomorrow 3:00 AM',
    'intervalSeconds' => 86400,
]);
```

`runAt` accepts anything `strtotime()` understands.

## Writing a task script

Same convention as `TaskQueue`: variables and the `$App` object are extracted into local scope. Throw on failure — the scheduler catches and records the error.

```php
<?php
// app/tasks/refresh-cache.php

// $App is available — the runner builds it with site.ini loaded.
// Per-task variables passed via addTask() are also extracted.

// Tasks needing additional services (DB, custom configs) set them up here:
$db = new \Truecast\Hopper($App->getConfig('mysql.ini'));
$db->execute('DELETE FROM cache_entries WHERE expires_at < ?', [date('Y-m-d H:i:s')]);

echo "Cache cleaned at " . date('c') . "\n";
```

## The runner

The scheduler doesn't run itself — a cron job invokes the runner once a minute.

```cron
* * * * * /usr/bin/php /path/to/project/vendor/truecastdesign/true/workers/scheduledTaskRunner.php >> /path/to/project/logs/scheduler.log 2>&1
```

The runner is self-contained: it derives the project root from its own location, loads composer's autoloader, builds a fresh `\True\App` (with `site.ini` loaded if present), and calls `runDue()`. No project bootstrap file is required.

## Inspecting and managing tasks

```php
$scheduler = new True\TaskScheduler(BP . '/app/data/scheduled-tasks.sqlite');

// All upcoming tasks, soonest-first
$tasks = $scheduler->getTasks();

// Remove a scheduled task by id
$scheduler->removeTask(42);

// Recover tasks that crashed mid-run (called automatically before each tick).
$scheduler->resetStuckTasks();
```

## Failure handling

- **One-shot, ok**: row is deleted.
- **One-shot, failed**: row is kept with `last_status = 'failed'` and `last_error` populated. It will *not* be retried automatically; remove and re-add to retry.
- **Recurring, ok or failed**: `next_run` advances by `interval_seconds`. The error is recorded in `last_error` and remains visible until the next successful run.
- **Stuck `running`**: any task that has been in `running` state for more than 5 minutes is reset on the next tick (configurable via `resetStuckTasks($maxRuntimeSeconds)`).

Errors are also written to `BP/php-error.log` so you can audit failed runs without querying the SQLite database.

## Comparison with TaskQueue

| | TaskQueue | TaskScheduler |
| --- | --- | --- |
| Trigger | Run immediately when added | Run at a future time, optionally repeating |
| Worker cadence | Continuous (~2s) via systemd | Once a minute via cron |
| Use case | Email send, image processing, anything you want offloaded *now* | Daily reindex, hourly cleanup, run-once-at-a-future-time tasks |

You can use both side-by-side — they have separate SQLite databases and separate runner scripts.
