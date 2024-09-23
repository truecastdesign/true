<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 4).'/init-site.php';

$App->TaskQueue->runTasks();