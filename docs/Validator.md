# Validator Class Documentation

## Overview
The **Validator** class provides a comprehensive solution for validating input data against various rules, such as checking if a field is required, ensuring a value matches a specific format, or verifying numeric ranges. It also offers the flexibility to add custom error messages.

---

## Public Methods

### `__construct(array $data = [])`
Initializes the `Validator` class with the data to be validated.

#### Parameters
- **`$data`** (array): An associative array of input data.

#### Example Usage
```php
$validator = new Validator(['name' => 'John Doe']);
```

---

### `validate(array $fieldRules, array $customErrors = [])`
Validates the input data against a set of rules.

#### Parameters
- **`$fieldRules`** (array): An associative array defining validation rules for each field.
- **`$customErrors`** (array): An associative array of custom error messages.

#### Returns
- (bool): `true` if all validations pass, `false` otherwise.

#### Example Usage
```php
$fieldRules = [
    'name' => 'required|alpha',
    'email' => 'required|email'
];
$customErrors = [
    'name' => 'Please enter a valid name!',
    'email' => 'Your email address is not valid.'
];

$isValid = $validator->validate($fieldRules, $customErrors);
```

---

### `errors()`
Retrieves the validation errors encountered.

#### Returns
- (array): An array of error messages.

#### Example Usage
```php
if (!$isValid) {
    $errors = $validator->errors();
    print_r($errors);
}
```

---

## Validation Rules
The `Validator` class supports the following validation rules:

| Rule               | Description                                           |
|--------------------|-------------------------------------------------------|
| `required`         | Ensures the field is not empty.                       |
| `clean`            | Removes potential XSS threats.                        |
| `matches[value]`   | Checks if the field matches a given value.            |
| `in[value1,value2]`| Checks if the field matches any value in a list.      |
| `depends[field=value]` | Ensures a field is required based on another field's value. |
| `min[number]`      | Ensures the field has a minimum length.               |
| `max[number]`      | Ensures the field does not exceed a maximum length.   |
| `alpha`            | Allows only alphabetic characters.                    |
| `alpha_numeric`    | Allows only alphanumeric characters.                  |
| `alpha_numeric_dash` | Allows alphanumeric characters, underscores, and dashes. |
| `numeric`          | Allows only numeric values.                           |
| `integer`          | Ensures the field contains an integer.                |
| `float`            | Ensures the field contains a float.                   |
| `natural`          | Ensures the field contains a natural number.          |
| `natural_no_zero`  | Ensures the field contains a natural number greater than zero. |
| `name`             | Allows a valid person's name with special characters. |
| `address`          | Allows a valid address with specific characters.      |
| `base64`           | Ensures the field contains a valid Base64 string.     |
| `ip`               | Checks if the field contains a valid IP address.      |
| `date[format]`     | Checks if the field matches a given date format.      |
| `ssn[country]`     | Checks if the field contains a valid SSN for a specific country. |
| `phone`            | Checks if the field contains a valid phone number.    |
| `phone_length`     | Ensures the phone number has 7, 10, or 11 digits.     |
| `url`              | Checks if the field contains a valid URL.             |

---

## Private Methods

### `rules($field, $rules, $data, $errorMsg)`
Processes the validation rules for a given field.

#### Parameters
- **`$field`** (string): The field name.
- **`$rules`** (array): The rules to validate against.
- **`$data`** (mixed): The value of the field.
- **`$errorMsg`** (string): A custom error message.

### `errorMsgs($rule, $field, $param)`
Generates error messages based on the validation rule.

#### Returns
- (string): The error message.

---

## Example Usage

```php
use True\Validator;

$data = [
    'name' => 'John',
    'email' => 'john.doe@example.com',
    'age' => 25
];

$validator = new Validator($data);

$fieldRules = [
    'name' => 'required|alpha',
    'email' => 'required|email',
    'age' => 'integer|min[18]|max[65]'
];

$customErrors = [
    'name' => 'Please enter a valid name.',
    'email' => 'Your email address is not valid.',
    'age' => 'Age must be between 18 and 65.'
];

if ($validator->validate($fieldRules, $customErrors)) {
    echo 'All fields are valid!';
} else {
    print_r($validator->errors());
}
```

