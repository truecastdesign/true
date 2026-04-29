<?php
namespace True;

/**
 * Task Scheduler
 *
 * Companion to True\TaskQueue — but instead of running a task immediately,
 * tasks are scheduled to run at a specific time and/or on a fixed interval.
 *
 * A scheduler runner script (vendor/truecastdesign/true/workers/scheduledTaskRunner.php)
 * is invoked once a minute by cron and asks this class to run any due tasks.
 *
 * @author Daniel Baldwin <danielbaldwin@gmail.com>
 * @version 1.0.0
 */
class TaskScheduler
{
	private \PDO $pdo;
	private array $objects = [];

	public function __construct(string $databaseFile)
	{
		try {
			$this->pdo = new \PDO('sqlite:' . $databaseFile);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->initializeDatabase();
		} catch (\PDOException $e) {
			throw new \Exception('Connection failed: ' . $e->getMessage());
		}
	}

	public function initializeDatabase(): void
	{
		$this->pdo->exec("CREATE TABLE IF NOT EXISTS scheduled_tasks (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT,
			script TEXT NOT NULL,
			variables TEXT,
			next_run DATETIME NOT NULL,
			interval_seconds INTEGER,
			last_run DATETIME,
			last_status TEXT DEFAULT 'pending',
			last_error TEXT,
			created DATETIME
		)");
		$this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_tasks_next_run ON scheduled_tasks(next_run)");
	}

	/**
	 * Make objects available to every task script (extracted into local scope).
	 *
	 *   $TaskScheduler->passObjects(['App' => $App]);
	 */
	public function passObjects(array $objects): void
	{
		foreach ($objects as $key => $object) {
			$this->objects[$key] = $object;
		}
	}

	/**
	 * Schedule a task. Provide at least one of `runAt` or `intervalSeconds`.
	 *
	 * @param string $script    Script path. If absolute (starts with /) used as-is;
	 *                          otherwise resolved against BP/app/tasks/.
	 * @param array  $variables Variables to extract into the task's scope.
	 * @param array  $opts      [
	 *     'name'            => string|null  Human-readable label.
	 *     'runAt'           => string|null  First run datetime, anything strtotime() understands.
	 *                                       Defaults to now if omitted and intervalSeconds is set.
	 *     'intervalSeconds' => int|null     If set, task is recurring with this interval (>=1).
	 * ]
	 * @return int ID of the inserted task.
	 */
	public function addTask(string $script, array $variables = [], array $opts = []): int
	{
		$name     = $opts['name']            ?? null;
		$runAt    = $opts['runAt']           ?? null;
		$interval = $opts['intervalSeconds'] ?? null;

		if ($interval !== null) {
			$interval = (int) $interval;
			if ($interval < 1) throw new \Exception('intervalSeconds must be >= 1.');
		}

		if ($runAt === null) {
			if ($interval === null) {
				throw new \Exception('Provide runAt, intervalSeconds, or both.');
			}
			$runAt = date('Y-m-d H:i:s'); // run as soon as the next runner tick fires
		}

		$ts = strtotime((string) $runAt);
		if ($ts === false) throw new \Exception('Invalid runAt: ' . $runAt);

		$stmt = $this->pdo->prepare("
			INSERT INTO scheduled_tasks (name, script, variables, next_run, interval_seconds, last_status, created)
			VALUES (?, ?, ?, ?, ?, 'pending', ?)
		");
		$stmt->execute([
			$name,
			$script,
			json_encode($variables),
			date('Y-m-d H:i:s', $ts),
			$interval,
			date('Y-m-d H:i:s'),
		]);
		return (int) $this->pdo->lastInsertId();
	}

	/**
	 * Run every task whose next_run is in the past. Reschedules recurring tasks,
	 * deletes successful one-shots, marks failed tasks with last_error.
	 *
	 * Call this from a cron-driven worker every minute.
	 *
	 * @return array<int, array{id:int, status:string, error?:?string, reason?:string}>
	 */
	public function runDue(): array
	{
		$this->resetStuckTasks();

		$now = date('Y-m-d H:i:s');
		$stmt = $this->pdo->prepare("
			SELECT * FROM scheduled_tasks
			WHERE next_run <= ?
			  AND (last_status IS NULL OR last_status != 'running')
			ORDER BY next_run ASC
		");
		$stmt->execute([$now]);
		$tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		$results = [];
		foreach ($tasks as $task) {
			$results[] = $this->runOne($task);
		}
		return $results;
	}

	/**
	 * Recover tasks stuck in 'running' state longer than $maxRuntimeSeconds.
	 * Called automatically at the start of runDue(), but exposed for manual use.
	 */
	public function resetStuckTasks(int $maxRuntimeSeconds = 300): int
	{
		$threshold = date('Y-m-d H:i:s', time() - $maxRuntimeSeconds);
		$stmt = $this->pdo->prepare("
			UPDATE scheduled_tasks
			SET last_status = 'failed', last_error = 'Reset from stuck running state'
			WHERE last_status = 'running' AND (last_run IS NULL OR last_run < ?)
		");
		$stmt->execute([$threshold]);
		return $stmt->rowCount();
	}

	/** All tasks, soonest-first. */
	public function getTasks(): array
	{
		$stmt = $this->pdo->query("SELECT * FROM scheduled_tasks ORDER BY next_run ASC");
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function removeTask(int $id): bool
	{
		$stmt = $this->pdo->prepare("DELETE FROM scheduled_tasks WHERE id = ?");
		$stmt->execute([$id]);
		return $stmt->rowCount() > 0;
	}

	private function runOne(array $task): array
	{
		$taskId = (int) $task['id'];

		// Atomically claim the task so a parallel run can't grab it.
		$claim = $this->pdo->prepare("
			UPDATE scheduled_tasks
			SET last_status = 'running', last_run = ?
			WHERE id = ? AND (last_status IS NULL OR last_status != 'running')
		");
		$claim->execute([date('Y-m-d H:i:s'), $taskId]);
		if ($claim->rowCount() === 0) {
			return ['id' => $taskId, 'status' => 'skipped', 'reason' => 'already running'];
		}

		$taskFile = (strpos($task['script'], '/') === 0)
			? $task['script']
			: BP . '/app/tasks/' . $task['script'];

		$variables = json_decode($task['variables'] ?: 'null', true) ?: [];

		$status = 'ok';
		$error  = null;

		try {
			if (!file_exists($taskFile)) {
				throw new \Exception('Task script not found: ' . $taskFile);
			}

			// Make passed objects + per-task variables available inside the script.
			extract($this->objects);
			if (is_array($variables)) extract($variables);

			require $taskFile;
		} catch (\Throwable $e) {
			$status = 'failed';
			$error  = $e->getMessage();
			error_log(PHP_EOL . '[TaskScheduler] task ' . $taskId . ' failed: ' . $error, 3, BP . '/php-error.log');
		}

		// Reschedule recurring tasks; remove successful one-shots; keep failed one-shots for inspection.
		if (!empty($task['interval_seconds'])) {
			$nextRun = date('Y-m-d H:i:s', time() + (int) $task['interval_seconds']);
			$upd = $this->pdo->prepare("
				UPDATE scheduled_tasks
				SET next_run = ?, last_status = ?, last_error = ?
				WHERE id = ?
			");
			$upd->execute([$nextRun, $status, $error, $taskId]);
		} elseif ($status === 'ok') {
			$this->pdo->prepare('DELETE FROM scheduled_tasks WHERE id = ?')->execute([$taskId]);
		} else {
			$upd = $this->pdo->prepare("
				UPDATE scheduled_tasks
				SET last_status = ?, last_error = ?
				WHERE id = ?
			");
			$upd->execute([$status, $error, $taskId]);
		}

		return ['id' => $taskId, 'status' => $status, 'error' => $error];
	}
}
