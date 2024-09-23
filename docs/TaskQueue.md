# Task Queue
> This library class allows you to add a PHP script as a task that will be run in the background.

## How to use

init.php - put the following code in your init.php file.

If your task scripts need access to any objects, you can use the passObjects method to pass an array of key=>instance pares. The object will be accessible with the key name.

You can change the path to the database if needed and it will automatically be created the first time the script runs.

```php
$App->TaskQueue = new True\TaskQueue(BP.'/data/tasks.sqlite');

$App->TaskQueue->passObjects(['App'=>$App]);
```

In a controller file, add the following to run a script in the background.

You can call the AddTask method on the TaskQueue instance which inserts a task into the queue and then runs it in the background. The default behavior is if you pass it just a file name it will look for that script in the BP.'/app/tasks/' directory. If you pass it a root relative path, meaning it starts with a /, then it will use what you pass it to find the script. 

create a script in app/tasks for example app/tasks/task.php

The array of key/values will be available to that script as extracted variables.

```php
try {
	$App->TaskQueue->addTask('task.php', ['var1'=>1]);
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

You want to setup the task runner script to run about every 2-5 sec. Crontab only allows you to run a script every 1 min at the lowest. The best method is to use a System Service.

In this libraries workers folder you will find a taskRunner.service file. Save this to /etc/systemd/system/taskRunner.service You will want to modify the file and PHP exec paths for your environment. 

```service
ExecStart=/usr/local/bin/ea-php56 /home/username/vendor/truecastdesign/true/cron/taskRunner.php
```

You will need to be logged into your server as root. 

cd into /etc/systemd/system if you are not already.

Run these commands

```shell
systemctl daemon-reload
systemctl enable --now taskRunner.service
```

To check if your service is still running:

```shell
systemctl status taskRunner.service
```

This is how you stop and disable the service from running.

```shell
systemctl stop taskRunner.service
systemctl disable taskRunner.service
```

If you just stop the service and not disabled it, you can start it up again with this command.

```shell
systemctl restart taskRunner.service
```

If you make a change to the taskRunner.service after you set it up, run:

```shell
systemctl daemon-reload
```

Look in this dir for service links

```shell
/etc/systemd/system/multi-user.target.wants
```

To debug your task script, run this in the terminal editing for your version of PHP and the correct path to the taskRunner.php file.

```shell
/usr/local/bin/ea-php82 /home/username/vendor/truecastdesign/true/workers/taskRunner.php
```

Another method that is an option if you don't have root access is to use the watch command to run your script. Login to your server with ssh and run this command changing your file paths as needed.

```shell
nohup watch -n 2 /usr/local/bin/ea-php56 /home/username/vendor/truecastdesign/true/workers/taskRunner.php > /home/username/logs/taskqueue.log 2>&1 &
```