<?php
namespace True;

/**
 * Task Queue
 * 
 * @author Daniel Baldwin <danielbaldwin@gmail.com>
 * @version 1.2.4
 */
class TaskQueue 
{
	private $pdo;
	private $objects = []; // Array to store passed objects

	var $logFile = BP.'/php-error.log';

	public function __construct($databaseFile) 
	{
		try {
			$this->pdo = new \PDO("sqlite:" . $databaseFile);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$this->initializeDatabase();
		} catch (\PDOException $e) {
			throw new \Exception("Connection failed: " . $e->getMessage());
		}		

		// if (!file_exists($this->worker))
		// 	throw new \Exception("The worker script is missing. ".$this->worker);
	}

	public function initializeDatabase() 
	{
		$this->pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			script TEXT,
			variables TEXT,
			created DATETIME,
			status VARCHAR (15)
		)");
	}

	public function addTask($script, $variables) 
	{
		$stmt = $this->pdo->prepare("INSERT INTO tasks (script, variables, status, created) VALUES (?, ?, ?, ?)");
		$stmt->execute([$script, json_encode($variables), 'pending', date("Y-m-d H:i:s")]);
	}

	/**
	 * If a task script needs certain objects to run, pass them in with this method.
	 * 
	 * $TaskQueue->passObjects(['Obj1'=>$Obj1, 'Obj2'=>$Obj2]);
	 */
	public function passObjects(array $objects)
	{
		foreach ($objects as $key => $object)
			$this->objects[$key] = $object;
  	}

	public function runTasks()
	{
		$stmt = $this->pdo->query("SELECT * FROM tasks WHERE status = 'pending'");
		$tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		if (is_array($tasks))
		foreach ($tasks as $task) {
			$taskId = $task['id'];

			try {
				$stmt = $this->pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    			$stmt->execute(['processing', $taskId]);
			} catch (\PDOException $e) {
				error_log("Change the status to processing for task ID $taskId: " . $e->getMessage(), 3, BP.'/php-error.log');
			}
	
			$taskData = json_decode($task['variables'], true);
	
			if (strpos($task['script'], '/') === 0)
				$taskFile = $task['script'];
			else
				$taskFile = BP.'/app/tasks/'.$task['script'];

			if (is_array($taskData))
				extract($taskData);

			// $fileExists = file_exists($taskFile) ? "true" : "false";

			// error_log(PHP_EOL."Task file: $taskFile $fileExists ".print_r($taskData, true), 3, BP.'/php-error.log');
	
			try {
				if (file_exists($taskFile)) {
					extract($this->objects); // Make all passed objects available in the scope of the included file

					require_once $taskFile;

					try {
						$stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
    					$stmt->execute([$taskId]);
					} catch (\PDOException $e) {
							error_log("Failed to delete the task ID $taskId: " . $e->getMessage(), 3, BP.'/php-error.log');
					}
				}
				else {
					error_log(PHP_EOL."Task script not found: " . $taskFile, 3, BP.'/php-error.log');

					$this->pdo->exec("UPDATE tasks SET status = 'failed' WHERE id = $taskId");
				}
	
			} catch (\Exception $e) {
				$stmt = $this->pdo->prepare("UPDATE tasks SET status = 'failed' WHERE id = ?");
    			$stmt->execute([$taskId]);
				
				error_log(PHP_EOL.$e->getMessage(), 3, BP.'/php-error.log');
			}
		}
	}

	/**
	 * Get an array of current tasks in the database and their info
	 * 
	 * @return [0=>['id'=>1, 'script'=>'filename.php', 'variables'=>'["var":"value"]', 'status'=>'pending', 'created'=>'2024-01-01 11:25:41']]
	 */
	public function getTaskList()
	{
		$stmt = $this->pdo->query("SELECT * FROM tasks");
		$tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $tasks;
	}

	public function resetAllTasksToPending()
	{
		$this->pdo->exec("UPDATE tasks SET status = 'pending'");
	}
}