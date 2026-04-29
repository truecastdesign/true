#!/usr/bin/env php
<?php
/**
 * TaskScheduler runner — self-contained.
 *
 * Cron line (run once a minute):
 *   * * * * * /usr/bin/php /path/to/project/vendor/truecastdesign/true/workers/scheduledTaskRunner.php >> /path/to/project/logs/scheduler.log 2>&1
 *
 * What the runner does, in order:
 *   1. Resolves BP as four directories up from this file (project root).
 *   2. Loads the project's composer autoload.
 *   3. Creates a fresh \True\App and loads site.ini if it exists.
 *   4. Opens (or creates) the scheduler's SQLite DB at BP/app/data/scheduled-tasks.sqlite.
 *   5. Passes $App into the scheduler so task scripts can use it.
 *   6. Runs every task whose next_run is in the past.
 *
 * Inside each task script you have:
 *   - $App                    — the bootstrapped True\App instance
 *   - any variables passed via addTask()
 *
 * Tasks that need additional services (DB connections, view objects, custom
 * config) should set those up themselves at the top of the task file.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BP', dirname(__DIR__, 4));

require_once BP . '/vendor/autoload.php';
if (file_exists(BP . '/vendor/truecastdesign/true/src/Exceptions.php')) {
	require_once BP . '/vendor/truecastdesign/true/src/Exceptions.php';
}

$App = new \True\App;
if (file_exists(BP . '/app/config/site.ini')) {
	$App->load('site.ini');
}

$dbFile = BP . '/app/data/scheduled-tasks.sqlite';
if (!is_dir(dirname($dbFile))) {
	@mkdir(dirname($dbFile), 0755, true);
}

$App->TaskScheduler = new \True\TaskScheduler($dbFile);
$App->TaskScheduler->passObjects(['App' => $App]);

$results = $App->TaskScheduler->runDue();
foreach ($results as $r) {
	$line = '[' . date('Y-m-d H:i:s') . "] task {$r['id']} → {$r['status']}";
	if (!empty($r['error']))  $line .= ' — ' . $r['error'];
	if (!empty($r['reason'])) $line .= ' (' . $r['reason'] . ')';
	echo $line . "\n";
}
