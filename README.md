True - Base classes for True framework

![True Framework](https://raw.githubusercontent.com/truecastdesign/true/master/assets/TrueFramework.png "True Framework")

These classes form the basic functionality of True framework.

Requires PHP 7.1 or newer.

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
	
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^ index.php [QSA,L]
</IfModule>
```

## Table of Contents
[Nonce Generator](./docs/Nonce.md)

## Files Setup

### /public_html/index.php

```php
<?php
require '../init.php';
```

### /init.php

```php
<?php
define('BP', __DIR__);

require 'vendor/autoload.php';

$App = new True\App;
$App->load('site.ini');

if ($App->config->site->debug) {
	$GLOBALS['debug'] = true;
	error_reporting(E_ALL & ~E_NOTICE);
} else {
	$GLOBALS['debug'] = false;
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}

$App->request = new True\Request;
$App->response = new True\Response;
$App->router = new True\Router($App->request);
$App->view = new True\PhpView;

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

function e($var) {
	return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
}

function p($var) {
	print_r($var);
}

function ph($var) {
	echo '<!--'; print_r($var); echo '-->';
}	
```

### /app/routes.php

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

## Request object

The Router object creates an instance of the Request object and inserts that into any controller as $request.

The router also passes in the $request object to the routing closure as follows.

```php
$App->router->get('/api/user/*:id', function($request){
	echo $request->route->id;
});
```

### Working with uploaded images in the $request object

#### Check if files are uploaded

```php
if ($request->files->file->uploaded) {
	// do something like move or resize it
	echo $request->files->file->ext; // jpg
	echo $request->files->file->mime; // image/jpeg
	echo $request->files->file->tmp_name; // /path/j23k4j8d
}
```

#### Resize Images

```php
$request->files->file->imageWidth = 800;
$request->files->file->imageHeight = 800;
try {
	$request->files->file->resize();
} catch (Exception $e) {
	echo $e->getMessage();
}
```
The keyword 'file' in the above code is the name of the file input field. Be sure to use not use hyphens (-) in the name as that will break the object reference. Just underscores or camelCase names. If you set one dimension the other will be calulated for you.

#### Cropping Images

The crop method allows you to crop the top, right, bottom, or left size off of images. The values passed in the array are in that clockwise order just like in CSS.

```php
try {
	$request->files->file->crop([0,20,0,0]);
} catch (Exception $e) {
	echo $e->getMessage();
}
```

To crop the image square automatically

```php
try {
	$request->files->file->cropSquare();
} catch (Exception $e) {
	echo $e->getMessage();
}
```

To crop the image square keeping the top part and removing just the bottom automatically, use the cropBottomSquare() method.

```php
try {
	$request->files->file->cropBottomSquare();
} catch (Exception $e) {
	echo $e->getMessage();
}
```

To crop the image square keeping the bottom part and removing just the top automatically, use the cropTopSquare() method.

```php
try {
	$request->files->file->cropTopSquare();
} catch (Exception $e) {
	echo $e->getMessage();
}
```

After you crop the image or resize it, you will want to move it to a new location by using the move method and passing in the path and the filename. To use the uploaded file extension, use the $request->files->file->ext proterty.

```php
try {
	$request->files->file->move('/path/', 'newFileName.'.$request->files->file->ext);
} catch (Exception $e) {
	echo $e->getMessage();
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

Using Controllers
-----------------

Controllers should be .php files that live in the app/controllers directory. You can trigger them to run by a custom route like $App->get('/path', 'filename.php'); or if you are using the default route that comes with TrueFramework it handles controllers and views automatically. In that case you can match the path to the controller and view. If url is www.domain.com it will run the index.php controller and display the index.phtml view. You can skip the controller altogether because it is run using a @include which if the file is not available it just quietly skips it.

Controllers should be just simple PHP scripts not classes. One PHP file per route. Nest controlers in directories named the same as the parent controller.

Example: 
Path -> Controller
/about -> about.php
/about/staff -> about/staff.php
/about/staff/john-doe -> about/staff/john-doe.php

Here is what the default route looks like. You can modify it as needed.

```php
$App->any('/*:path', function($request) use ($App) {
	$vars = []; 
	@include $App->controller($request->route->path);

	$vars['config'] = $App->config;

	# check selected nav item
	$vars['selectedNav'] = (object) [$request->route->path => ' class="sel"'];
	
	$App->view->render($request->route->path.'.phtml', $vars);
});
```

Controllers are passed the $App object and the $request object.

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
$App->view->title = "Title Tag";
$App->view->description = "This is the meta description and og:description";
$App->view->canonical = "http://www.domain.com/canonical-url";
$App->view->linkText = "Clickable Text to link to page"; 
$App->view->headHtml = "<script src='/path/js/dom/js'></script>"; 
$App->view->html // page body HTML 
$App->view->breadcrumbs // array of breadcrumbs with name and url
// populate using meta area of view file like the following. Only on pages with parent pages. Home is not a parent page according to Google. Put them in decending order from parents down.
// breadcrumb[] = "Top Parent Title|/parent"
// breadcrumb[] = "Next Parent Title|/parent/other-parent"

$App->view->created = "2022-12-01" // set the date the page was created. It can be outputted or used by calling $App->view->created. If you want a different date format, call $App->view->created("M d, Y") can pass the PHP date formatting string you want.

$App->view->modified = "2022-12-01" // You can set the page or article modified date if you want full control of it. This variable will also be auto filled if not provided from the modified date of the view file. Just like the created variable, you can format it by calling $App->view->modified("M d, Y");
```

Output these in your template like the following.

```html
<?=$App->view->headHtml?>
```

#### Output CSS and JS to template

The css and js view variables are special in that to output a compressed and combined version to your page, you use a special variable rather than $App->view->css like you would all the other variables.

For CSS

```html
<?=$App->view->cssoutput?>
```
The css output should go in your head tag.

For Javascript

```html
<?=$App->view->jsoutput?>
```
The JS output should go right before the closing body tag.

There needs to be a cache folder in both of your CSS and JS folders.

PHPView will put a compressed, versioned, combined file in /assets/css/cache and /assets/js/cache respectfully. Be sure those directories are there for it to work right. 

#### Custom variables

You can use your own custom variables now so you can pass the values to the base template for outputting in the header for example.

In the meta area of the view/.phtml file, add your custom variable like:
```php
customVariable = "custom value"
{endmeta}
```

When you put that in the meta area of a file, then the base template will be able to access and check if it is available with the following.
```php
<?if ($App->view->isset('customVariable')):?>
	<?=$App->view->customVariable?>
<?endif?>
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

### Using a Custom Layout (header/footer code) to insert your view into.

Make a custom controller and in the controller put:

```php
$App->view->layout = BP."/app/views/_layouts/landing-page.phtml";
$App->view->render('page.phtml');
```

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
$mail = new \True\Email('domain.com', 587, 'tls', 'login');  // ssl and tls are turned on or off automatacally based on the port provided.
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
->setHTMLMessageVariables(['name'=>'John Doe', 'phone'=>'541-555-5555', 'message'=>'Plain text message'])
->addHeader('X-Auto-Response-Suppress', 'All');

if ($mail->send()) {
	echo 'SMTP Email has been sent' . PHP_EOL;   
} else {
	echo 'An error has occurred. Please check the logs below:' . PHP_EOL;
	pr($mail->getLogs());
}
```

## JWT Javascript Web Token
```php
$Users = new Users($DB);
$LoginAttempts = new True\LoginAttempts($DB);
$JWT = new True\JWT;
$PasswordGenerator = new True\PasswordGenerator;

$taAuthConfig = $App->getConfig('trueadminAuth.ini');

$Auth = new True\AuthenticationJWT($Users, $LoginAttempts, $JWT, $PasswordGenerator, $App, [
	'privateKeyFile'=>BP.'/app/data/key_private_rsa.pem', 
	'publicKeyFile'=>BP.'/app/data/key_public_rsa.pem', 
	'encryptionPasswordFile'=>'trueadminAuth.ini', 
	'pemkeyPassword'=>$taAuthConfig->pemkey_password, 
	'https'=>$taAuthConfig->https,
	'ttl'=>$taAuthConfig->pemkey_password,
	'alg'=>$taAuthConfig->alg
]);

try {
	if ($Auth->isLoggedIn()) {
		# user logged in
	} 
} catch (Exception $e) {
	echo $e->getMessage();
}
```

## LogParser

```php
$logFile = BP."/logs/access.log";

$Parser = new True\LogParser($logFile);

foreach ($Parser as $row) {
	print_r($row);
}
```

## SEO/OpenGraph LD+JSON

Use this method of the SEO class to output LD+JSON schema.org meta data.

### Global site schema

```php
$schemaInfo = (object)[
	"base_url"=>$App->config->site->url,
	"url"=>$App->request->url->full, 
	"title"=>$App->view->title, 
	"description"=>$App->view->description, 
	"site_logo_url"=>$App->config->site->site_logo_url, 
	"site_logo_width"=>$App->config->site->site_logo_width, 
	"site_logo_height"=>$App->config->site->site_logo_height, 
	"site_logo_caption"=>$App->config->site->site_logo_caption, 
	"datePublished"=>$App->view->datePublished, 
	"dateModified"=>$App->view->dateModified, 
	"social_media"=>$App->config->social_media
];

echo $SEO->schemaGraph($schemaInfo);
```

### Specific page schemas

#### Breadcrumbs

```php
echo $SEO->
```

## Google Tag Manager GA4 Javascript code generator

Use the below code and similar for different events

```php
$eventData = [
	'orderNumber'=>1254875,
	'total'=>'$25.80',
	'source'=>'BatteryStuff',
	'coupon'=>"ZJUE",
	'shipping'=>2.80,
	'tax'=>0.50,
	'items'=>[
		[
			'partNumber'=>'rx8',
			'name'=>'Battery Charger RX8',
			'coupon'=>'ZJUE',
			'discount'=>0.30,
			'brand'=>"Chargers Unlimited",
			'category'=>'Battery Chargers > 12 Volt > Single Bank > 5-10 Amps',
			'variant'=>'With Cables',
			'price'=>'$7.80',
			'quantity'=>'1'
		],
		[
			'partNumber'=>'rx9',
			'name'=>'Battery Charger RX9',
			'coupon'=>'JEN',
			'brand'=>"Chargers Unlimited",
			'category'=>'Battery Chargers > 12 Volt > Single Bank > 10-20 Amps',
			'price'=>7.90,
			'quantity'=>2
		]
	]
];
$GoogleTagManager = new True\GoogleTagManager;
echo $GoogleTagManager->event('purchase', $eventData);
```
This will output the below JS code including script tags for you. The class does basic value validation and filtering. It auto adds in the currency as USD. If you need a different currency just pass it in with the key 'currency'.

Current event support is: 

view_item
add_to_cart
view_cart
begin_checkout
login
purchase

```HTML
<script>
gtag("event", "purchase", 
{
    "currency": "USD",
    "value": 25.8,
    "transaction_id": 1254875,
    "affiliation": "BatteryStuff",
    "coupon": "ZJUE",
    "shipping": 2.8,
    "tax": 0.5,
    "items": [
        {
            "index": 0,
            "item_name": "Battery Charger RX8",
            "item_id": "rx8",
            "coupon": "ZJUE",
            "discount": 0.3,
            "item_brand": "Chargers Unlimited",
            "item_variant": "With Cables",
            "price": 7.8,
            "quantity": 1,
            "item_category": "Battery Chargers",
            "item_category2": "12 Volt",
            "item_category3": "Single Bank",
            "item_category4": "5-10 Amps"
        },
        {
            "index": 1,
            "item_name": "Battery Charger RX9",
            "item_id": "rx9",
            "coupon": "JEN",
            "item_brand": "Chargers Unlimited",
            "price": 7.9,
            "quantity": 2,
            "item_category": "Battery Chargers",
            "item_category2": "12 Volt",
            "item_category3": "Single Bank",
            "item_category4": "10-20 Amps"
        }
    ]
});
</script>
```

# TESTING PHPUNIT

Tests are located in the tests folder.

List of tests and how to run them.

```shell
% phpunit tests/NonceTest.php
```

Usage
-----

Build your website in the app folder using the available folder structure. 

The app folder should be located beside the public folder. Assets, such as images, css, js, pdfs, etc., should be located in the assets folder inside the public folder.

Issues
------

When running on localhost, cookies for logging into admin area will not set using Chrome. They require a domain. Use Firefox or Safari to develop site. 


