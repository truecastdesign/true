<?php
namespace True;

/**
 * Task Queue
 * 
 * @author Daniel Baldwin <danielbaldwin@gmail.com>
 * @version 1.1.3
 */
class TaskQueue {
	private $pdo;

	var $logFile = BP.'/php-error.log';
	var $worker = BP.'/vendor/truecastdesign/true/workers/queue.php';

	public function __construct($databaseFile) {
		try {
			$this->pdo = new \PDO("sqlite:" . $databaseFile);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->initializeDatabase();
		} catch (\PDOException $e) {
			throw new \Exception("Connection failed: " . $e->getMessage());
		}

		if (!file_exists($this->logFile))
			touch($this->logFile);

		if (!file_exists($this->worker))
			throw new \Exception("The worker script is missing. ".$this->worker);
	}

	public function initializeDatabase() {
		$this->pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			task_name TEXT NOT NULL,
			task_data TEXT NOT NULL,
			status TEXT DEFAULT 'pending',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)");
	}

	public function addTask($taskName, $taskData) {
		$stmt = $this->pdo->prepare("INSERT INTO tasks (task_name, task_data) VALUES (?, ?)");
		$stmt->execute([$taskName, json_encode($taskData)]);

		exec("php ".$this->worker." > /dev/null 2>&1 & echo $!", $output);

		//$processId = (int)$output[0];
	}
}