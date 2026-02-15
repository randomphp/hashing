![RandomPHP](./assets/randomphp_text.png)

# RandomPHP Hashing

A small, dependency-light PHP library that wraps PHP’s built-in [password_hash()](https://www.php.net/manual/en/function.password-hash.php) / [password_verify()](https://www.php.net/manual/en/function.password-verify.php) functions with:

- Strongly-typed hash objects (`HashInterface`)
- Algorithm objects you can pass around (`HashingAlgorithmInterface`)
- Automatic algorithm detection from an existing hash string
- A “needs rehash” workflow
- Optional Doctrine DBAL type for mapping hashes to value objects

Supported algorithms out of the box:

- **Argon2id** (`PASSWORD_ARGON2ID`)
- **Argon2i** (`PASSWORD_ARGON2I`)
- **bcrypt** (`PASSWORD_BCRYPT`)

---

## Requirements

- **PHP 8.4+**
- PHP must be compiled with the relevant password algorithms:
    - bcrypt is generally always available
    - Argon2 support depends on your PHP build (if unavailable, `password_hash()` will fail for Argon2)

Optional:

- **doctrine/dbal** (only if you want the `HashType` DBAL type)

---

## Installation

Install via Composer:

```bash
composer require randomphp/hashing
```

---

## Basic usage

### Hash a password

Pick an algorithm, hash a clear-text password, and store the resulting string:

```php
use RandomPHP\Hashing\Algorithm\Argon2IDHashingAlgorithm;

$algo = Argon2IDHashingAlgorithm::make(
    memory: 65536,     // kibibytes
    iterations: 4,
    parallelism: 2,
);

$hash = $algo->hash($password);

// Store as string:
$stored = $hash->toString(); // or (string)$hash
```

### Verify a password

Turn the stored string back into a `Hash` value object and verify:

```php
use RandomPHP\Hashing\Hash;

$hash = Hash::make($stored);

if ($hash->verify($passwordAttempt)) {
    // ok
}
```

`Hash::make()` automatically detects the algorithm **from the hash string** and attaches the decoded algorithm instance to the hash.

### Rehash when parameters change

If you update your hashing parameters, you can check whether an existing hash should be rehashed:

```php
use RandomPHP\Hashing\Algorithm\Argon2IDHashingAlgorithm;
use RandomPHP\Hashing\Hash;

$hash = Hash::make($stored);

$newAlgo = Argon2IDHashingAlgorithm::make(
    memory: 131072,
    iterations: 4,
    parallelism: 2,
);

if (!$hash->verify($passwordAttempt)) {
    // Do not continue the password did not match the hash.
}

if ($hash->needsRehash($newAlgo)) {
    $rehash = $newAlgo->hash($passwordAttempt);
    $stored = $rehash->toString(); // replace stored hash
}
```

`needsRehash()` is implemented by comparing the decoded algorithm parameters to your desired algorithm via `HashingAlgorithmInterface::match()`.

---

## Algorithms

### Argon2id / Argon2i

```php
use RandomPHP\Hashing\Algorithm\Argon2IDHashingAlgorithm;
use RandomPHP\Hashing\Algorithm\Argon2IHashingAlgorithm;

$argon2id = Argon2IDHashingAlgorithm::make(65536, 4, 2);
$argon2i  = Argon2IHashingAlgorithm::make(65536, 4, 2);
```

Notes:

- Internally the library relies on PHP’s `password_hash()` and `password_verify()`.
- When decoding an Argon2 hash, the library parses:
    - version (`v=...`)
    - memory cost (`m=...`)
    - time cost / iterations (`t=...`)
    - parallelism / threads (`p=...`)
    - salt length and key length (derived from the base64 parts)

### bcrypt

```php
use RandomPHP\Hashing\Algorithm\BcryptHashingAlgorithm;

$bcrypt = BcryptHashingAlgorithm::make(cost: 12);
```

When decoding, bcrypt hashes are recognized via the prefix (e.g. `$2y$12$...`) and the cost is extracted.

---

## Working with untrusted hash strings

This library keeps a small in-memory cache of algorithm instances (`AlgoCache`) so identical parameter sets don’t create multiple objects.

If you are decoding hashes coming from an **untrusted** source (for example: user input, external payloads), you should disable the cache to avoid unbounded growth:

```php
use RandomPHP\Hashing\AlgoCache;
use RandomPHP\Hashing\Hash;

$hash = AlgoCache::disabled(fn () => Hash::make($untrustedHashString));
```

You can also toggle it globally:

```php
use RandomPHP\Hashing\AlgoCache;

AlgoCache::toggle(false); // disable
AlgoCache::toggle(true);  // enable
```

---

## Serialization

- `Hash` implements `Stringable` and `JsonSerializable`.
    - Casting to string returns the hash string
    - `json_encode($hash)` serializes as the hash string

Algorithm instances can be serialized to arrays:

```php
$payload = $algo->toArray();
$restored = $algo::fromArray($payload); // returns an algorithm instance or null
```

---

## Doctrine DBAL integration

If you use Doctrine DBAL, the library includes a custom type:

- `RandomPHP\Hashing\Doctrine\HashType`
- Type name: `hash`

Register the type (example):

```php
use Doctrine\DBAL\Types\Type;
use RandomPHP\Hashing\Doctrine\HashType;

if (!Type::hasType(HashType::NAME)) {
    Type::addType(HashType::NAME, HashType::class);
}
```

Then map your column as a string type with the `hash` DBAL type and use `HashInterface` in your entities/DTOs. The type will:

- Convert DB values (string) to `Hash::make($value)`
- Convert PHP values (`HashInterface` or string) back to the DB string

---

## Extending: custom algorithms

You can add your own `HashingAlgorithmInterface` implementation and register it so `Hash::make()` can detect it:

```php
use RandomPHP\Hashing\Hash;

Hash::registerAlgorithm(MyCustomAlgorithm::class);
```

Your algorithm must implement the [HashingAlgorithmInterface](./src/Interface/HashingAlgorithmInterface.php)

---

## License

[MIT](./LICENSE)