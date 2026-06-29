# Email Class Documentation

## Overview
The **Email** class provides a robust solution for sending emails using SMTP authentication. It supports various configurations such as setting recipients, adding attachments, and applying custom headers. This class is designed for ease of use and flexibility, making it ideal for sending secure emails in PHP applications.

---

## Constructor: `__construct()`
The constructor initializes the **Email** class with the necessary SMTP server details. All parameters are optional — use `setConfig()` to configure via an INI file instead.

### Signature
```php
public function __construct(
    string $server = 'domain.com',
    int $port = 25,
    string $protocol = 'tls',
    string $authMethod = 'plain'
)
```

### Parameters
- **`$server`**: The SMTP server domain (e.g., `smtp.domain.com`). Defaults to `'domain.com'`.
- **`$port`**: The port number to connect to the SMTP server (commonly `25`, `465`, or `587`).
- **`$protocol`**: The protocol to use for secure connections (`tls` or `ssl`).
- **`$authMethod`**: The authentication method (`plain`, `login`, `cram-md5`).

#### Example Usage
```php
// Minimal — configure via setConfig()
$mail = new \True\Email;

// Explicit
$mail = new \True\Email('smtp.domain.com', 587, 'tls', 'plain');
```

---

## `setConfig()`
Loads SMTP and sender settings from an INI file. This is the recommended way to configure the class when the same settings are reused across the application (e.g., via a shared `$App->email` instance).

### Signature
```php
public function setConfig(string $filename): Email
```

### Parameters
- **`$filename`**: Path to the INI file. A bare filename (e.g., `'email.ini'`) resolves relative to `app/config/`. An absolute path is used as-is.

### INI File Keys
| Key | Description |
|-----|-------------|
| `server` | SMTP server hostname |
| `port` | SMTP port number |
| `protocol` | `tls` or `ssl` |
| `authMethod` | `plain`, `login`, or `cram-md5` |
| `username` | SMTP username |
| `password` | SMTP password |
| `fromEmail` | Default sender email address |
| `fromName` | Default sender display name (optional) |

### Example INI File (`app/config/email.ini`)
```ini
server="smtp.example.com"
port=587
protocol="tls"
authMethod="login"
username="noreply@example.com"
password="secret"
fromEmail="noreply@example.com"
fromName="My Site"
```

### Example Usage
```php
// Typical setup in init.php — shared instance for the whole application
$App->email = new \True\Email;
$App->email->setConfig('email.ini');

// Elsewhere in the app
$App->email
    ->addTo('customer@example.com')
    ->setSubject('Your order receipt')
    ->setHtmlMessage($html)
    ->send();
```

> **Note:** `setConfig()` returns `$this`, so it can be chained. Calling it multiple times merges settings — later calls override earlier ones.

---

## Public Methods

### `setLogin()`
Sets the SMTP login credentials.

#### Signature
```php
public function setLogin(string $username, string $password): Email
```

#### Parameters
- **`$username`**: The SMTP username.
- **`$password`**: The SMTP password.

#### Example Usage
```php
$mail->setLogin('user@domain.com', 'password');
```

---

### `setFrom()`
Sets the sender's email address and name.

#### Signature
```php
public function setFrom(string $address, string $name = null): Email
```

#### Parameters
- **`$address`**: The sender's email address.
- **`$name`**: The sender's name (optional).

#### Example Usage
```php
$mail->setFrom('user@domain.com', 'John Doe');
```

---

### `addTo()`
Adds a recipient's email address and name.

#### Signature
```php
public function addTo(string $address, string $name = null): Email
```

#### Parameters
- **`$address`**: The recipient's email address.
- **`$name`**: The recipient's name (optional).

#### Example Usage
```php
$mail->addTo('recipient@domain.com', 'Jane Doe');
```

---

### `addCc()`
Adds a carbon copy (CC) recipient.

#### Signature
```php
public function addCc(string $address, string $name = null): Email
```

#### Parameters
- **`$address`**: The CC recipient's email address.
- **`$name`**: The CC recipient's name (optional).

#### Example Usage
```php
$mail->addCc('cc@domain.com', 'John Smith');
```

---

### `addBcc()`
Adds a blind carbon copy (BCC) recipient.

#### Signature
```php
public function addBcc(string $address, string $name = null): Email
```

#### Parameters
- **`$address`**: The BCC recipient's email address.
- **`$name`**: The BCC recipient's name (optional).

#### Example Usage
```php
$mail->addBcc('bcc@domain.com', 'Jane Smith');
```

---

### `addAttachment()`
Adds a file attachment to the email.

#### Signature
```php
public function addAttachment(string $attachment): Email
```

#### Parameters
- **`$attachment`**: The full path to the attachment file.

#### Example Usage
```php
$mail->addAttachment('/path/to/file.pdf');
```

---

### `setSubject()`
Sets the subject of the email.

#### Signature
```php
public function setSubject(string $subject): Email
```

#### Parameters
- **`$subject`**: The email subject.

#### Example Usage
```php
$mail->setSubject('Test Subject');
```

---

### `setTextMessage()`
Sets the plain text message body.

#### Signature
```php
public function setTextMessage(string $message): Email
```

#### Parameters
- **`$message`**: The plain text message.

#### Example Usage
```php
$mail->setTextMessage('This is a plain text message.');
```

---

### `setHtmlMessage()`
Sets the HTML message body.

#### Signature
```php
public function setHtmlMessage(string $message): Email
```

#### Parameters
- **`$message`**: The HTML message.

#### Example Usage
```php
$mail->setHtmlMessage('<strong>This is an HTML message.</strong>');
```

---

### `send()`
Sends the email via the configured SMTP server.

#### Signature
```php
public function send(): bool
```

#### Returns
- **`true`** if the email was sent successfully.
- **`false`** if an error occurred.

#### Example Usage
```php
if ($mail->send()) {
    echo 'Email sent successfully!';
} else {
    echo 'Failed to send email.';
}
```

---

## Example Usage

### Using `setConfig()` (recommended for application-wide use)
```php
// init.php — set up once
$App->email = new \True\Email;
$App->email->setConfig('email.ini');

// Any controller
$App->email
    ->addTo('recipient@domain.com', 'Jane Doe')
    ->setSubject('Test Subject')
    ->setHtmlMessage('<strong>Hello!</strong>')
    ->send();
```

### Manual configuration
```php
$mail = new \True\Email('smtp.domain.com', 587, 'tls', 'plain');
$mail->setLogin('user@domain.com', 'password')
    ->setFrom('user@domain.com', 'John Doe')
    ->addTo('recipient@domain.com', 'Jane Doe')
    ->addCc('cc@domain.com', 'John Smith')
    ->addBcc('bcc@domain.com', 'Jane Smith')
    ->addAttachment('/path/to/file.pdf')
    ->setSubject('Test Subject')
    ->setTextMessage('This is a plain text message.')
    ->setHtmlMessage('<strong>This is an HTML message.</strong>');

if ($mail->send()) {
    echo 'Email sent successfully!';
} else {
    echo 'Failed to send email.';
    print_r($mail->getLogs());
}
```

---

## Summary
The **Email** class is a powerful and flexible tool for sending emails via SMTP. It supports secure connections, various authentication methods, and customization options, making it a great choice for any PHP application that needs email functionality.

