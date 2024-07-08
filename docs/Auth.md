# Using the True\Auth class

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