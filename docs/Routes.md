# Router

The Router class, as you might be guessed, is for routing urls to views and controllers.

The Router class requires a True\Request instance.

## How to use

The first step is in your init.php file to create a new instance of the Request class.

```php
$App->request = new True\Request;
```

Then create a new instance of the Router passing it the Request object.

```php
$App->router = new True\Router($App->request);
```

The init.php file should have the app/routes.php file included near the bottom after all the object setups.

```php
require 'app/routes.php';
```

## in the app/routes.php file

```php
$App->router->redirect(['request'=>$_SERVER['REQUEST_URI'], 'lookup'=>BP.'/redirects.json', 'type'=>'301']);

$App->router->any('/path/:id', function($request) use ($App) {
	$vars = [];
	
	# include controller
	require BP.'/app/controllers/filename.php';

	# set the title and description meta data if the page is dynamically generated
	$App->view->title = "Title Tag Text";
	$App->view->description = "Meta description text.";

	# render the view
	$App->view->render('_layouts/filename.phtml', $vars);
});

$App->router->any('/*:path', function($request) use ($App) {
	$vars = []; 
	@include $App->router->controller($request->route->path);
	
	# check selected nav item
	$vars['selNav'] = ['/'.$request->route->path => true];
	
	$App->view->render($request->route->path.'.phtml', $vars);
});

# Put all code in a controller for a route

$App->router->get('/path/:id', 'filename.php'); 
# It will look in the /app/controllers dir for the file filename.php and include it.
# The $App object and the $request object will be available inside the filename.php script.

# Specify the exact request method to match
$App->router->get(...);
$App->router->post(...);
$App->router->put(...);
$App->router->patch(...);
$App->router->delete(...);
$App->router->options(...);

# Use map to specify several methods

$App->router->map(['GET', 'POST', 'PUT'], '/path', 'controller.php');

# The $App object is automatically passed to the controller.php script. The conroller.php file should just be simple code you want that outputs JSON or renders a view, etc. Should not be a class. Should look like the code in the closure.  
```

#### Route Groups

Group routing for running certain code for groups of pages or rest end points.
Allows you to run middleware for certain grouped requests. This allows you to authenicate and authorize requests before control is passed onto the containing routes. 

When passing in a middleware class instance, it should have an __invoke method and return either true or false if the containing routes should run or not.

```php
$App->router->group('/api/*', function() use ($App) {
	# code in here only runs if path matches /api/ and /api/[any further path parts]
	$App->router->get('/api/user/*:id', function($request) use ($App) {
		echo $request->route->id;
	});
});
```

Using groups can speed up your routing because if the group pattern fails to match, all child routes will not be checked.

#### Route Middleware

Route middleware is handy to hide code in a separate file to clean up your routes file if you have lots of routes.

You can pass in an array of class instances that are invokable as the their parameter of the group method. Use [ new \App\AuthMiddleware, new \VendorName\SampleMiddleware ] for multiple layers. The middleware layers are run left to right. If any return false, the others will not run.

```php
$App->router->group('/api/*', function() use ($App) {
	$App->router->get('/api/user/*:id', function($request) use ($App) {
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