# Nonce Generator
> This library class allows you to generate a nonce token that is unique and of the length you need.

## General Information
* The source code was take from OpenID's solution and adapted to make it more uique by including a hash of the current date and time.
* Returned characters will be limited to upper and lower case letters and numbers.

## How to use

By default the nonce will be 32 chars long.

You can make it a different length by passing it a length in the first argument. 

```php
$nonce = \True\Nonce::create();

echo $nonce;
```

Output: mccscc8KfrdzJfJvMfVwdXg2Upyzlbwo

Change the nonce length

```php
$nonce = \True\Nonce::create(16);

echo $nonce;
```

Output: 5UWQa9397dO91RdG

If you want to pass your own time, you can do it with the second argument. Not usually needed.

```php
$nonce = \True\Nonce::create(32, 1660338149);
```

## Testing

```shell
% phpunit tests/NonceTest.php
```