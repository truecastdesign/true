# PasswordGenerator Class Documentation

## Overview
The **PasswordGenerator** class generates secure word-based passwords by combining random adjectives and nouns. The generated passwords can consist of 2, 3, 4, or 5 words to improve memorability while maintaining security.

---

## Public Methods

### `generate($words = 4)`
Generates a word-based password.

#### Parameters
- **`$words`** (int): The number of words to use in the password. Supported values are:
  - `2`: Generates a password with 1 adjective and 1 noun.
  - `3`: Generates a password with 2 adjectives and 1 noun.
  - `4` (default): Generates a password with 2 adjectives and 2 nouns.
  - `5`: Generates a password with 3 adjectives and 2 nouns.

#### Returns
- **`string`**: The generated password.

---

## Example Usage
```php
use True\PasswordGenerator;

$passwordGenerator = new PasswordGenerator();
$password = $passwordGenerator->generate(4);

echo "Generated Password: " . $password;
```

#### Output Example
```
Generated Password: Brave Tiger Happy Mountain
```

---

## Password Word Sources
The adjectives and nouns used to generate passwords are loaded from text files:
- **`adjectives.txt`**: A list of adjectives.
- **`nouns.txt`**: A list of nouns.

These files are expected to be located at the following paths:
- `BP/vendor/truecastdesign/true/assets/adjectives.txt`
- `BP/vendor/truecastdesign/true/assets/nouns.txt`

---

## Summary
The **PasswordGenerator** class provides a simple way to generate secure and memorable passwords. By adjusting the number of words, you can control the complexity and length of the generated password.

