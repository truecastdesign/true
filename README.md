True - Base classes for True framework

![True Framework](https://raw.githubusercontent.com/truecastdesign/true/master/assets/TrueFramework.png "True Framework")

v1.9.1

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

# Specify the exact request method to match
$App->get(...);
$App->post(...);
$App->put(...);
$App->patch(...);
$App->delete(...);
$App->options(...);

# Use map to specify several methods

$App->map(['GET', 'POST', 'PUT'], '/path', 'controller.php');

# The $App object is automatically passed to the controller.php script. The conroller.php file should just be simple code you want that outputs JSON or renders a view, etc. Should not be a class. Should look like the code in the closure.  
```

#### Route Groups

Group routing for running certain code for groups of pages or rest end points.
Allows you to run middleware for certain grouped requests. This allows you to authenicate and authorize requests before control is passed onto the containing routes. 

When passing in a middleware class instance, it should have an __invoke method and return either true or false if the containing routes should run or not.

```php
$App->group('/api/*', function() use ($App) {
	# code in here only runs if path matches /api/ and /api/[any further path parts]
	$App->get('/api/user/*:id', function($request) use ($App) {
		echo $request->route->id;
	});
});
```

Using groups can speed up your routing because if the group pattern fails to match, all child routes will not be checked.

#### Route Middleware

Route middleware is handy to hide code in a separate file to clean up your routes file if you have lots of routes.

You can pass in an array of class instances that are invokable as the their parameter of the group method. Use [ new \App\AuthMiddleware, new \VendorName\SampleMiddleware ] for multiple layers. The middleware layers are run left to right. If any return false, the others will not run.

```php
$App->group('/api/*', function() use ($App) {
	$App->get('/api/user/*:id', function($request) use ($App) {
		echo $request->route->id;
	});
}, [ new \App\AuthMiddleware ]);
```

#### Route Middleware Example

```php
<?php
namespace App;

class AuthMiddleware 
{
	public function __invoke($request)
	{
		$Auth = new \True\Auth;
		if ($Auth->authenticate(['type'=>'bearer'])) {
			return true;
		} else {
			return false;
		}
	}
}
```

## Using the True\Auth class
### Using a Bearer Token

To use the Bearer token to authenicate api requests, start by generating a new token using:

```php
echo $App->auth->requestToken();
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


