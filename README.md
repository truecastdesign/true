True - Base classes for True framework
=======================================
V 1.1.13

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
require __DIR__.'/../init.php';
```

### /init.php

```php
<?php
<?php
session_start();

error_reporting(E_ALL & ~E_NOTICE);

require __DIR__.'/vendor/autoload.php';

define('BP', __DIR__);

$App = new \True\App;

$App->load(BP.'/app/config/site.ini');

# check routes
require __DIR__.'/app/routes.php';
```

Usage
-----

Build your website in the app folder using the available folder structure. 

The app folder should be located beside the public folder. Assets, such as images, css, js, pdfs, etc., should be located in the assets folder inside the public folder.

Issues
------

When running on localhost, cookies for logging into admin area will not set using Chrome. They require a domain. Use Firefox or Safari to develop site. 


