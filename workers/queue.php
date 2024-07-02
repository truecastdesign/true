<?php

define('BP2', dirname(__DIR__, 4));

require BP2.'/init.php';

$App = new \True\App;

$config = $App->getConfig('queue-database.ini');

$pdo = new PDO('sqlite:'.BP.$config->database);

try {
	$stmt = $pdo->query("SELECT * FROM tasks WHERE status = 'pending'");
   $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if (is_array($tasks))
	foreach ($tasks as $task) {
		$taskId = $task['id'];
		$pdo->exec("UPDATE tasks SET status = 'processing' WHERE id = $taskId");

		$taskData = json_decode($task['task_data']);

		$taskFile = BP.'/app/tasks/'.$task['task_name'];

		try {
			if (file_exists($taskFile)) {
				require $taskFile;
			}
			else
				error_log(PHP_EOL."Task script not found: " . $taskFile, 3, BP.'/php-error.log');

			$pdo->exec("UPDATE tasks SET status = 'done' WHERE id = $taskId");
		} catch (Exception $e) {
			$pdo->exec("UPDATE tasks SET status = 'failed' WHERE id = $taskId");
			
			error_log(PHP_EOL.$e->getMessage(), 3, BP.'/php-error.log');
		}
  }
} catch (Exception $e) {
	// Log the error
	error_log(PHP_EOL.$e->getMessage(), 3, BP.'/php-error.log');
}