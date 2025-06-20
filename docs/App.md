# Using the True\App class

Create a new instance of the App class.
```php
$App = new \True\App;
```

Use this object as a container for storing and accessing all your general objects.

Example

```php
$App->request = new True\Request;
$App->response = new True\Response;
$App->router = new True\Router($App->request);
$App->view = new True\PhpView;
```

The $App object and be passed around so you have access to what you need.

# Config - Use .ini format.

You can load all your config files with an array passed to the construct of App.

```php
$App = new \True\App(['config1.ini', 'config2.ini']);
```

The other way to load config files is using the load method which is useful for loading config files in controllers or other scripts other than init.php.
```php
$App->load('configfile.ini');
$App->load(['config1.ini', 'config2.ini']);
```

If you are going to load a config file into the App object, it needs to have a section heading at the top of it like this:

```ini
[mysection]
key = 'value'
```

To get a value that has been loaded into the App object, access it on the $App->config object.

```php
echo $App->config->mysection->key;
```

You can temporarily set a value like so:

```php
$App->config->mysection->key = 'value';
```

# configUpdate

If you want to write a single value to a config.ini file, you can use the configUpdate method on the App class.

There are two ways to do this. The first is if you already loaded the config file with the load or construct method, the first argument should be the section heading.

```php
$App->configUpdate('mysection', 'key', 'value');
```

If it is not loaded, you can pass the filename if it is located in the app/config director or path/filename if it lives somewhere else.

# getConfig

If it does not have a section heading, then use the getConfig method to put the config object into a variable.

If you just want to get an object back from a config file and not load it into the App object, use the getConfig method like so.
```php
$configObj = $App->getConfig('configfile.ini');

echo $configObj->key;
```

If you just want one value from a flat config file with no section heading, you can use the second argument to pass the desired key.
```php
$value = $App->getConfig('configfile.ini', 'key');
```

# go

This is a convenance method that runs this code:

```php
header("Location: " . $filename);
exit;
```

So to redirect to another page, do this:

```php
$App->go('/path/page');
```

# error

The App class also has a helper method for error reporting using `trigger_error`.

```php
$App->error($message, $level = 'warning');
```

- $message can be a string or an array of errors.
- $level can be 'notice', 'warning', or 'error' (case-insensitive).
- Internally uses `trigger_error()` with appropriate constants:
  - `notice` → `E_USER_NOTICE`
  - `warning` → `E_USER_WARNING`
  - `error` → `E_USER_ERROR`

Examples:

```php
$App->error("This is a warning.");
$App->error(["Field A is required.", "Field B must be numeric."], 'error');
$App->error("This is just a notice.", 'notice');
```

Display system errors to page nicely.

```php
<?$App->displayErrors()?>
```

If you want to benchmark a process.

```php
$App->benchmarkStart();

// run some code

echo $App->benchmarkEnd();

// Completed in 5 ms
```

