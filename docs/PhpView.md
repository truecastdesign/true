# Using the True\PhpView class

Create a new instance of the PhpView class.
```php
$App->view = new \True\PhpView;
```

This object can be used to combine a HTML template with the view file found by default in the app/views directory.

# Basic Example

```php
$App->view->render('about.phtml');
```

View files should be named with a .phtml extension and use PHP short tags and template if, for, foreach, etc syntax. 

```php
<?if ($ == 1):?>
	// code
<?endif?>

Hello <?=$name?>, how are you?
```

This render method does a lot of operations on the template and view file before echoing it to the screen. 

Other ways to use it are:

# Display error pages mainly 404s

```php
$App->view->render(404);
```

Supported codes are:

301: Moved Permanently
302: Found
303: See Other
304: Not Modified
307: Temporary Redirect
308: Permanent Redirect
400: Bad Request
401: Unauthorized
403: Forbidden
404: Not Found
405: Method Not Allowed

# Display page view file in another directory.

```php
$App->view->render(BP.'/path/view.phtml');
```

# Base Template (header and footer code)

The default is to look for the file app/views/_layouts/base.phtml that is generated by TrueFramework. You can override the default using:

```php
$App->view->layout = BP.'/path/basetemplate.phtml';
```

# Variables

The rendering engine isolates controller variables from the view. This has become standard practice for modern PHP frameworks. You can pass them in as the second argument or the render method in an array. They will be extracted out in the view.

```php
$vars['name'] = "John Smith";
$vars['phone'] = "555-555-5555";

$App->view->render('about.phtml', $vars);
```

In the view:

```html
<p><?=$name?> <?=$phone?></p>
```

Variables can also be set this way as well. This can be useful for global access by all views.

```php
$App->view->variables['email'] = "john@example.com";
```

Views also have access to the $App object and everything loaded in it.

# View formatting

View files should have a meta data header to set things like the title tag contents, meta description, css, js, and caching. The meta data header is in .ini format. The delimiter between it and the HTML of the page is the {endmeta} string on its own line.

```HTML
title = "This is the title of the page"
description = "This is the meta description of the page."
js = "/assets/js/myjsfile.js, /assets/js/myotherjsfile.js"
css = "/assets/css/mycssfile.css, /assets/css/myothercssfile.scss"
cache = false
{endmeta}

<h1>My Great Page</h1>
```

JS files and compressed and combined into a cached file that the filename changes if the contents of any of the JS files changes. So it has auto versioning. It places the cached files in assets/js/cache/. 

The script tag with the generated filename is output in your template with the following.

```HTML
<?=$App->view->jsoutput?>
```

CSS files work the same way. The cache files are placed in assets/css/cache/. It does handle compiling .scss Sass files into CSS if you want to use them.

```HTML
<?=$App->view->cssoutput?>
```

Another special feature for CSS is that any <style> tags in the body of your HTML are auto moved to by default inside the <head> tags of your base template. TrueFramework base template comes with this PHP tag which outputs the combined CSS from the page.

```HTML
<?=$App->view->headHtml?>
```

The headHtml key can also be used to inject HTML into the head tags. It will not be overritten by the CSS styles. The CSS will be added after what is already in the headHtml key. 

Example of using it to inject some HTML.

```php
$App->view->headHtml = '<meta name="robots" content="noindex">
<link rel="canonical" href="https://www.example.com'.$canonicalUrl.'">';
```