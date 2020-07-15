True - Base classes for True framework

![True Framework](https://raw.githubusercontent.com/truecastdesign/true/master/assets/TrueFramework.png "True Framework")

v1.25.1

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

	RewriteRule .? - [E=HEADER>Authorization:%{HTTP:Authorization}]
	RewriteRule .? - [E=HEADER>Accept:%{HTTP:Accept}]
	RewriteRule .? - [E=HEADER>Accept-Charset:%{HTTP:Accept-Charset}]
	RewriteRule .? - [E=HEADER>Access-Control-Request-Method:%{HTTP:Access-Control-Request-Method}]
	RewriteRule .? - [E=HEADER>Cache-Control:%{HTTP:Cache-Control}]
	RewriteRule .? - [E=HEADER>Connection:%{HTTP:Connection}]
	RewriteRule .? - [E=HEADER>Content-Length:%{HTTP:Content-Length}]
	RewriteRule .? - [E=HEADER>Content-Type:%{HTTP:Content-Type}]
	RewriteRule .? - [E=HEADER>Date:%{HTTP:Date}]
	RewriteRule .? - [E=HEADER>Expect:%{HTTP:Expect}]
	RewriteRule .? - [E=HEADER>Forwarded:%{HTTP:Forwarded}]
	RewriteRule .? - [E=HEADER>Host:%{HTTP:Host}]
	RewriteRule .? - [E=HEADER>HTTP2-Settings:%{HTTP:HTTP2-Settings}]
	RewriteRule .? - [E=HEADER>If-Match:%{HTTP:If-Match}]
	RewriteRule .? - [E=HEADER>If-Modified-Since:%{HTTP:If-Modified-Since}]
	RewriteRule .? - [E=HEADER>If-None-Match:%{HTTP:If-None-Match}]
	RewriteRule .? - [E=HEADER>If-Range:%{HTTP:If-Range}]
	RewriteRule .? - [E=HEADER>If-Unmodified-Since:%{HTTP:If-Unmodified-Since}]
	RewriteRule .? - [E=HEADER>Origin:%{HTTP:Origin}]
	RewriteRule .? - [E=HEADER>Pragma:%{HTTP:Pragma}]
	RewriteRule .? - [E=HEADER>Proxy-Authorization:%{HTTP:Proxy-Authorization}]
	RewriteRule .? - [E=HEADER>Range:%{HTTP:Range}]
	RewriteRule .? - [E=HEADER>Referer:%{HTTP:Referer}]
	RewriteRule .? - [E=HEADER>TE:%{HTTP:TE}]
	RewriteRule .? - [E=HEADER>User-Agent:%{HTTP:User-Agent}]
	RewriteRule .? - [E=HEADER>Upgrade:%{HTTP:Upgrade}]
	RewriteRule .? - [E=HEADER>Upgrade-Insecure-Requests:%{HTTP:Upgrade-Insecure-Requests}]
	RewriteRule .? - [E=HEADER>Via:%{HTTP:Via}]
	RewriteRule .? - [E=HEADER>X-Requested-With:%{HTTP:X-Requested-With}]
	RewriteRule .? - [E=HEADER>DNT:%{HTTP:DNT}]
	RewriteRule .? - [E=HEADER>X-Forwarded-For:%{HTTP:X-Forwarded-For}]
	RewriteRule .? - [E=HEADER>X-Forwarded-Host:%{HTTP:X-Forwarded-Host}]
	RewriteRule .? - [E=HEADER>X-Forwarded-Proto:%{HTTP:X-Forwarded-Proto}]
	RewriteRule .? - [E=HEADER>X-HTTP-Method-Override:%{HTTP:X-HTTP-Method-Override}]
	RewriteRule .? - [E=HEADER>Proxy-Connection:%{HTTP:Proxy-Connection}]

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

define('BP', __DIR__);

require 'vendor/autoload.php';

$App = new \True\App;

$App->load('../app/config/site.ini');

$GLOBALS['debug'] = $App->config->site->debug;
$GLOBALS['dev'] = $App->config->site->dev;

if ($GLOBALS['debug']) {
	error_reporting(E_ALL);
} else {
	error_reporting(E_ALL & E_WARNING & ~E_NOTICE);
}

$App->view = new \True\PhpView;

# global css and js files
$App->view->css = '/vendor/truecastdesign/true/assets/default.css, /assets/css/site.css'; # global css files
# You can also add them on separate lines or different places in your code
$App->view->css = '/assets/example1.css';
$App->view->css = '/assets/example2.css';
# both will be combined and minified. This works for JS files as well.

$App->view->js = '/assets/js/file1.js, /assets/js/file2.js'; # global js files

# If you need to pass variables to the layout template or page template globally, you can use the variables key on the PhpView object as an key value array item.
$App->view->variables = ['key'=>'value'];
$App->view->variables = ['key2'=>'value2'];
$App->view->variables = ['key'=>'value', 'key3'=>'value3'];
# all the arrays will get merged together so you can keep added them and they will all be available. In the view: <?=$key?> <?=$key2?> <?=$key3?>

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

Creating a Response for REST or Similar Responses
---

### Example
```php
$App->response('{"result":"success"}', 'json', 200, ["Cache-Control: no-cache"]);
```
### Explained

$App->response('The body of the response', 'the type: json, html, xml', response code, array of raw headers);

You can still echo and print_r anything you need to and this will not overwrite it. Makes it nice for debugging.

There is a die() called at the end of the response method so it needs to be the last that you want to run. This allow for error responses followed by success response in your controller.

```php
try {
	// run method
} catch (Exception $e) {
	$App->response('{"result":"error", "error":"'.$e->getMessage().'"}', 'json', 401);
}
$App->response('{"result":"success"}', 'json'); // this will run if the above 
// response does not but will not run if there was already a response run above.
```

Using PHPView
-------------

In your init.php file or whereever you want to put it, make a new instance of the class.

If you are using it with True framework, it is best to set it to $App->view so you already have access to it and it is in a perdictable place. It can be used without if you like.

```php
$App->view = new True\PhpView;
```

### Set PHPView variables

#### Set global CSS files. Use a comma delimited list of paths and filenames from public root.

PHPView will combine all the CSS files together and minify the code for output in the `$_css` variable in our template.

```php
$App->view->css = '/assets/css/site.css';
```

#### Set global JS files. Use a comma delimited list of paths and filenames from public root.

PHPView will combine all the JS files together and minify the code for output in the `$_js` variable in our template. If the JS file path has http in it, like from a CDN or other source, it will not combine that resource into the combined and minified file. It will add it in as a separate resource.

```php
$App->view->js = '/assets/js/testing.js, /assets/js/testing2.js';
```

#### Page browser caching

Set to true or false. Caching is on by default and set to expire in 1 week. Individual page caching can be controlled by the top of view meta data. Use the `cache=false` in the view meta data to turn off caching for just that page.

```php
$App->view->cache = true; 
```

#### Other variables

```php
$App->view->title = "Title Tag";  // Output variable: $_metaTitle
$App->view->description = "This is the meta description and og:description";  // Output variable: $_metaDescription
$App->view->canonical = "http://www.domain.com/canonical-url"; // Output variable: $_metaCanonical
$App->view->linkText = "Clickable Text to link to page"; // Output variable: $_metaLinkText
$App->view->headHtml = "<script src='/path/js/dom/js'></script>"; // Output variable: $_headHTML
```


### Creating and Using View Files

View files need to be named {name}.phtml

They go in the /app/views directory

Urls with /name match the file /app/views/name.phtml
Urls with /name/othername match the file /app/views/name/othername.phtml

There should be a base view that has all the site html tags that surround the main content of the page that comes from the page view files. It will insert the contents of, for example name.phtml, and insert it whereever <?=$_html?> is in the base view.

```php
$App->view->render('page.phtml', ['var1'=>6]);
```

The second parameter is an array of variable that will be available to the page. In the above example, `$var1` will be equel to 6. You can pass class objects this way as well.

### Render file that is not in the /app/views directory

```php
$App->view->render(BP.'/vendor/truecastdesign/trueadmin/views/not-authorized.phtml');
```

### Render an error page

Default error pages: 404-error.phtml 401-error.phtml 403-error.phtml error.phtml

For the error.phtml page, there is provided $errorCode and $errorText variables to display which error it is.

```php
$App->view->error(404); // other errors supported: 301, 302, 303, 304, 307, 308, 400, 401, 403, 404, 405
```

### Set custom error page

```php
$App->view->403 = 'not-authorized.phtml';
```

## Emailing

True has a builtin SMTP email class for sending out emails using a SMTP email account for better deliverability and features.

```php
$mail = new \True\Email('domain.com', 465);  // ssl and tcp are turned on or off automatacally based on the port provided.
$mail->setLogin('user@domain.com', 'password')
->setFrom('user@domain.com', 'name')
->addReplyTo('user@domain.com', 'name')
->addTo('user@domain.com', 'name')
->addCc('user@domain.com', 'name')
->addBcc('user@domain.com', 'name')
->addAttachment(BP.'/path/to/filename.jpg')
->addHeader('header-title', 'header value')
->setCharset('utf-16', 'header value') // default: utf-8;  values: utf-16, utf-32, ascii, iso 8859-1 
->setSubject('Test subject')
->setTextMessage('Plain text message')
->setHtmlMessage('<strong>HTML Text Message</strong>')
->setHTMLMessageVariables('name'=>'John Doe', 'phone'=>'541-555-5555', 'message'=>'Plain text message')
->addHeader('X-Auto-Response-Suppress', 'All');

if ($mail->send()) {
	echo 'SMTP Email has been sent' . PHP_EOL;   
} else {
	echo 'An error has occurred. Please check the logs below:' . PHP_EOL;
	pr($mail->getLogs());
}
```

## JWT Javascript Web Token

## LogParser

```php
$logFile = BP."/logs/access.log";

$Parser = new True\LogParser($logFile);

foreach ($Parser as $row) {
	print_r($row);
}
```


Usage
-----

Build your website in the app folder using the available folder structure. 

The app folder should be located beside the public folder. Assets, such as images, css, js, pdfs, etc., should be located in the assets folder inside the public folder.

Issues
------

When running on localhost, cookies for logging into admin area will not set using Chrome. They require a domain. Use Firefox or Safari to develop site. 


