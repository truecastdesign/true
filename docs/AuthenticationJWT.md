# AuthenticationJWT Class Documentation

## Overview
The **AuthenticationJWT** class provides a complete JWT-based authentication system for PHP applications, including support for RSA and HMAC algorithms. It handles user login, logout, token management, and two-factor authentication (2FA) using Google Authenticator.

---

## Constructor: `__construct()`
The constructor initializes the AuthenticationJWT class by setting up the required dependencies and configurations.

### Signature
```php
public function __construct(
    object $userClass,
    object $loginAttemptClass,
    object $JWT,
    object $PasswordGenerator,
    object $App,
    array $config = []
)
```

### Parameters
- **`$userClass`**: The user class that handles user-related operations (e.g., fetching user details).
- **`$loginAttemptClass`**: Class that manages login attempt tracking (e.g., lockout time and failed attempts).
- **`$JWT`**: The JWT class responsible for encoding and decoding JSON Web Tokens.
- **`$PasswordGenerator`**: A class with a `generate()` method to create passwords. The `generate()` method accepts a word count (e.g., `5`).
- **`$App`**: The main application class that provides configuration management with `getConfig()` and `writeConfig()` methods.
- **`$config`**: An optional array of configuration values.

### Default Configuration
The constructor initializes the following default configuration values:
```php
$this->config = [
    'attemptsAllowed' => 8,
    'alg' => 'RS256',
    'privateKeyFile' => null,
    'publicKeyFile' => null,
    'pemkeyPassword' => null,
    'encryptionPasswordFile' => null,
    'cookie' => 'authjwt',
    'ttl' => 60 * 60 * 24 * 30,  // 30 days
    'https' => true,
    'httpOnly' => true
];
```

---

## Configuration Keys
| Key                     | Description                                           | Default Value            |
|-------------------------|-------------------------------------------------------|--------------------------|
| `attemptsAllowed`       | Number of allowed login attempts before lockout.     | `8`                      |
| `alg`                   | JWT algorithm to use (e.g., `RS256`, `HS256`).       | `RS256`                  |
| `privateKeyFile`        | Path to the private key file for signing tokens.      | `null`                   |
| `publicKeyFile`         | Path to the public key file for verifying tokens.     | `null`                   |
| `pemkeyPassword`        | Password to access the private key.                  | `null`                   |
| `encryptionPasswordFile`| Path to the encryption password file.                | `null`                   |
| `cookie`                | Name of the cookie used to store JWT tokens.         | `authjwt`                |
| `ttl`                   | Time-to-live (TTL) for JWT tokens in seconds.        | `60 * 60 * 24 * 30`      |
| `https`                 | Whether to set the cookie as secure (HTTPS only).    | `true`                   |
| `httpOnly`              | Whether to make the cookie HTTP-only.                | `true`                   |

---

## Key Methods

### `login()`
Handles user login by verifying the username and password.

#### Signature
```php
public function login(string $username, string $password): bool
```

#### Workflow
1. Validates that both username and password are provided.
2. Checks if the account is locked out due to too many failed attempts.
3. Verifies the provided credentials.
4. If the user has 2FA enabled, it sets a partial JWT indicating a pending 2FA status.
5. If the credentials are correct and 2FA is not required, it sets a full JWT.

#### Example Usage
```php
try {
    $auth = new AuthenticationJWT($userClass, $loginAttemptClass, $JWT, $PasswordGenerator, $App);
    if ($auth->login('john.doe', 'securepassword')) {
        echo "Login successful!";
    } else {
        echo "2FA required.";
    }
} catch (Exception $e) {
    echo "Login failed: " . $e->getMessage();
}
```

---

#### Example Usage with Custom Config
```php
$taAuthConfig = (array) $App->getConfig('trueadminAuth.ini');

$configArguments = [
    'privateKeyFile' => BP . '/app/data/key_private_rsa.pem',
    'publicKeyFile' => BP . '/app/data/key_public_rsa.pem',
    'encryptionPasswordFile' => 'trueadminAuth.ini'
];

foreach ($taAuthConfig as $key => $value) {
    $configArguments[$key] = $value;
}

$Auth = new True\AuthenticationJWT($AdminUsers, $LoginAttempts, $JWT, $PasswordGenerator, $App, $configArguments);
```

---

### `logout()`
Logs the user out by clearing the authentication cookie.

#### Signature
```php
public function logout(): void
```

#### Example Usage
```php
$auth->logout();
echo "You have been logged out.";
```

---

### `isLoggedIn()`
Checks if the user is currently logged in by verifying the JWT token in the cookie.

#### Signature
```php
public function isLoggedIn(): bool
```

#### Example Usage
```php
if ($auth->isLoggedIn()) {
    echo "User is logged in.";
} else {
    echo "User is not logged in.";
}
```

---

### `googleAuth2fAStep()`
Checks if the user is in the 2FA pending step.

#### Signature
```php
public function googleAuth2fAStep(): bool
```

#### Example Usage
```php
if ($auth->googleAuth2fAStep()) {
    echo "User needs to complete 2FA.";
}
```

---

### `getUserInfo()`
Retrieves detailed information about the logged-in user.

#### Signature
```php
public function getUserInfo(): object
```

#### Example Usage
```php
$userInfo = $auth->getUserInfo();
echo "Welcome, " . $userInfo->first_name . " " . $userInfo->last_name;
```

---

### `updateToken()`
Updates the JWT token with additional claims.

#### Signature
```php
public function updateToken(array $additionalClaims = []): void
```

#### Example Usage
```php
$auth->updateToken(['role' => 'admin']);
echo "Token updated.";
```

---

## Error Handling
The class throws exceptions for various failure scenarios, such as:
- Missing or invalid configuration keys.
- Failed login attempts.
- Missing encryption keys.

#### Example
```php
try {
    $auth = new AuthenticationJWT($userClass, $loginAttemptClass, $JWT, $PasswordGenerator, $App);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Supported Algorithms
The class supports dynamically fetching supported algorithms using the `getSupportedAlgorithms()` method.

#### Supported Algorithms:
- `RS256`
- `RS384`
- `RS512`
- `HS256`
- `HS384`
- `HS512`

---

## Security Considerations
- Ensure private keys are securely stored and not exposed.
- Use HTTPS to protect JWT cookies.
- Regularly rotate encryption keys.

