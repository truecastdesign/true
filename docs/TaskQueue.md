# Task Queue
> This library class allows you to add a PHP script as a task that will be run in the background.


## How to use

init.php

```php
try {
	$App->taskqueue = new True\TaskQueue(BP.$App->getConfig('queue-database.ini')->database);
} catch (Exception $e) {
	trigger_error($e->getMessage(), 256);
}
```

You can call the AddTask method on the TaskQueue instance which inserts a task into the queue and then runs it in the background. The default behavior is if you pass it just a file name it will look for that script in the BP.'/app/tasks/' directory. If you pass it a root relative path, meaning it starts with a /, then it will use what you pass it to find the script. 

create a script in app/tasks for example app/tasks/task.php

The array of key/values will be available to that script in a value object $taskData.

Access the values like $taskData->var1. So don't use dashes in your keys or you won't be able to access it.

Page controller file located in the app/controllers directory.

```php
try {
	$App->taskqueue->addTask('task.php', [
		'var1'=>'value',
		'var2'=>'value'
	]);
} catch (Exception $e) {
	trigger_error($e->getMessage(), 256);
}
```

Example task script in the app/tasks directory.

```php
$config = $App->getConfig('contact-email-info.ini');

$Mail = new \PHPMailer\PHPMailer\PHPMailer(true);

try {
	//Server settings
	$Mail->isSMTP();
	$Mail->Host = $config->host;
	$Mail->SMTPAuth = true;
	$Mail->Username = $config->username;
	$Mail->Password = $config->password;
	$Mail->SMTPSecure = 'tls';
	$Mail->Port = $config->port; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
	$Mail->setFrom($config->from_email, $config->from_name);
	$Mail->addAddress($config->to_email, $config->to_name);
	$Mail->addReplyTo($taskData->email, $taskData->fullName);
					
	$Mail->isHTML(true);
	$Mail->Subject = $config->subject;
	$Mail->Body = $taskData->message;

	$Mail->send();
} catch (Exception) {
	error_log("Message could not be sent. Mailer Error: {$Mail->ErrorInfo}", 3, BP.'/php-error.log');
}
```