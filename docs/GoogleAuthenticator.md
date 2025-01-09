# GoogleAuthenticator Class Documentation

## Overview
The **GoogleAuthenticator** class provides functionality to implement two-factor authentication (2FA) using the Time-based One-Time Password (TOTP) protocol. It allows generating secret keys, creating time-based codes, and verifying user inputs against the generated codes. It also supports generating QR codes for easy setup in authentication apps.

---

## Public Methods

### `createSecret($secretLength = 16)`
Generates a new secret key for 2FA.

#### Parameters
- **`$secretLength`** (int): The length of the generated secret key. Must be between 16 and 128 characters. Default is 16.

#### Returns
- (string): The generated secret key.

#### Example Usage
```php
$authenticator = new True\GoogleAuthenticator();
$secret = $authenticator->createSecret();
echo "Your secret key is: $secret";
```

---

### `getCode($secret, $timeSlice = null)`
Generates a time-based one-time password (TOTP) code.

#### Parameters
- **`$secret`** (string): The secret key used to generate the code.
- **`$timeSlice`** (int|null): The time slice used for generating the code. Defaults to the current time slice if not provided.

#### Returns
- (string): The generated TOTP code.

#### Example Usage
```php
$authenticator = new True\GoogleAuthenticator();
$secret = 'JBSWY3DPEHPK3PXP';
$code = $authenticator->getCode($secret);
echo "Your current code is: $code";
```

---

### `getQRCode($name, $secret, $title = null, $params = array())`
Generates a URL to a QR code image that can be scanned by an authentication app.

#### Parameters
- **`$name`** (string): The name of the account or service.
- **`$secret`** (string): The secret key.
- **`$title`** (string|null): The title to display in the authentication app.
- **`$params`** (array): Optional parameters for QR code generation, including `width`, `height`, and `level` (error correction level).

#### Returns
- (string): The URL to the QR code image.

#### Example Usage
```php
$authenticator = new True\GoogleAuthenticator();
$name = 'test@example.com';
$secret = 'JBSWY3DPEHPK3PXP';
$qrCodeUrl = $authenticator->getQRCode($name, $secret, 'MyApp');
echo "Scan this QR code: <img src='$qrCodeUrl'>";
```

---

### `verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null)`
Verifies if a given code is valid for the provided secret key.

#### Parameters
- **`$secret`** (string): The secret key.
- **`$code`** (string): The code to verify.
- **`$discrepancy`** (int): The allowed time drift in 30-second units. Default is 1 (accepts codes from 30 seconds before or after).
- **`$currentTimeSlice`** (int|null): The time slice to verify against. Defaults to the current time slice.

#### Returns
- (bool): `true` if the code is valid, `false` otherwise.

#### Example Usage
```php
$authenticator = new True\GoogleAuthenticator();
$secret = 'JBSWY3DPEHPK3PXP';
$code = '123456';
if ($authenticator->verifyCode($secret, $code)) {
    echo "Code is valid.";
} else {
    echo "Invalid code.";
}
```

---

### `setCodeLength($length)`
Sets the length of the generated code. By default, the length is 6.

#### Parameters
- **`$length`** (int): The desired length of the code. Should be 6 or greater.

#### Returns
- (`GoogleAuthenticator`): The instance of the class for method chaining.

#### Example Usage
```php
$authenticator = new True\GoogleAuthenticator();
$authenticator->setCodeLength(8);
```

---

## Summary
The **GoogleAuthenticator** class is a simple and secure way to implement two-factor authentication using the TOTP protocol. It provides methods to generate secrets, create codes, verify codes, and generate QR codes for easy setup. This class can be used to add an extra layer of security to user authentication systems by enabling 2FA.

