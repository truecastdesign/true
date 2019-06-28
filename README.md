True - Base classes for True framework

![True Framework](https://raw.githubusercontent.com/truecastdesign/true/master/assets/TrueFramework.png "True Framework")

v1.7.11

These classes form the basic functionality of True framework.

Requires PHP 5.5 or newer.

Install
-------

To install with composer:

```sh
$ composer require truecastdesign/true
```

### .htaccess file for Apache

```comf
AddHandler application/x-httpd-php .html .phtml .php

<IfModule mod_rewrite.c>
  RewriteEngine On

  RewriteBase /

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ index.php [QSA,L]
</IfModule>
```

### /public_html/index.php

```php
<?php
require '../init.php';
```

### /init.php

```php
<?php
session_start();

error_reporting(E_ALL & ~E_NOTICE);

require 'vendor/autoload.php';

define('BP', __DIR__);

$App = new \True\App;

$App->load(BP.'/app/config/site.ini');

# check routes
require 'app/routes.php';
```

### /app/routes.php

```php
$App->redirect(['request'=>$_SERVER['REQUEST_URI'], 'lookup'=>BP.'/redirects.json', 'type'=>'301']);

$App->any('/path/:id', function($request) use ($App) {
	$vars = [];
	
	# include controller
	require BP.'/app/controllers/filename.php';

	# set the title and description meta data if the page is dynamically generated
	$App->view->title = "Title Tag Text";
	$App->view->description = "Meta description text.";

	# render the view
	$App->view->render('_layouts/filename.phtml', $vars);
});

$App->any('/*:path', function($request) use ($App) {
	$vars = []; 
	@include $App->controller($request->route->path);
	
	# check selected nav item
	$vars['selNav'] = ['/'.$request->route->path => true];
	
	$App->view->render($request->route->path.'.phtml', $vars);
});

# Put all code in a controller for a route

$App->get('/path/:id', 'filename.php'); 
# It will look in the /app/controllers dir for the file filename.php and include it.
# The $App object and the $request object will be available inside the filename.php script.
```
## Using the True\Auth class
### Using a Bearer Token

To use the Bearer token to authenicate api requests, start by generating a new token using:

```php
$App->auth->requestToken();
```

This will generate a new token and save it to /app/data/auth-tokens. You can change that location when you instantiate the class.

Add this to your init.php file.
```php
# custom path
$App->auth = new True\Auth(['bearerTokensFile'=>BP.'/app/other-path/auth-tokens']);

# default path
$App->auth = new True\Auth;
```

After generating the token, open /app/data/auth-tokens and copy and paste the bottom token line into your api request as a "Authorization: Bearer sdfsdfsSAMPLE_TOKENjlk5324klj5" header.

Check if the api request is authorized with:
```php
if ($App->auth->authenticate(['type'=>'bearer'])) {
	echo json_encode(['result'=>'success']);
} else {
	echo json_encode(['result'=>'bearer token invalid']);
}
```
Place this inside a controller file or inside the callback function.

### Using username and password authentication

```php
$userId = $App->auth->authenticate(['type'=>'login-token', 'token'=>$_COOKIE['login_cookie']]);
if (is_numeric($userId)) {
	echo json_encode(['result'=>'success', 'page'=>$output]);
} else {
	echo json_encode(['result'=>'not logged in']);
}
```

Using PHPView
-------------

View files need to be named {name}.phtml

They go in the /app/views directory

Urls with /name match the file /app/views/name.phtml
Urls with /name/othername match the file /app/views/name/othername.phtml

There should be a base view that has all the site html tags that surround the main content of the page that comes from the page view files. It will insert the contents of, for example name.phtml, and insert it whereever <?=$_html?> is in the base view.



Usage
-----

Build your website in the app folder using the available folder structure. 

The app folder should be located beside the public folder. Assets, such as images, css, js, pdfs, etc., should be located in the assets folder inside the public folder.

Issues
------

When running on localhost, cookies for logging into admin area will not set using Chrome. They require a domain. Use Firefox or Safari to develop site. 


