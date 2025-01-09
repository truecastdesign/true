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

## Basic Authentication Support

### How It Works
- The client sends an `Authorization` header with the format:
  `Authorization: Basic <base64(username:password)>`
- The server decodes the header and validates the credentials.

### Configuration
To enable Basic Authentication in the `Auth` class:
1. Use the `authenticate` method with `type: basic`:
```php
$App->auth->authenticate(['type' => 'basic']);
```

2. Add your validation logic in the validateBasicCredentials method.

### Example Usage

#### Request

```http
GET /api/resource HTTP/1.1
Authorization: Basic am9obmRvZTpwYXNzd29yZDEyMw==
```

#### Response (Success)

```json
{
  "status": "success",
  "message": "Authenticated"
}
```

#### Response (Failure)

```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

