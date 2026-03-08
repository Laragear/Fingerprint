# Fingerprint
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/fingerprint.svg)](https://packagist.org/packages/laragear/fingerprint)
[![Latest stable test run](https://github.com/Laragear/Fingerprint/actions/workflows/php.yml/badge.svg)](https://github.com/Laragear/Fingerprint/actions/workflows/php.yml)
[![Codecov coverage](https://codecov.io/gh/Laragear/Fingerprint/graph/badge.svg?token=lOyu8F4EHc)](https://codecov.io/gh/Laragear/Fingerprint)
[![Maintainability](https://qlty.sh/gh/Laragear/projects/Fingerprint/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/Fingerprint)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Fingerprint&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Fingerprint)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/12.x/octane#introduction)

Ridiculously fast non-cryptographic hashes in your application or Eloquent Models.

```php
use Laragear\Fingerprint\Fingerprinter;

$fingerprint = Fingerprinter::of('some-string-or-object');

return $fingerprint->hash();
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable.

## Requirements

* PHP 8.3 or later
* Laravel 12 or later

## Installation

You can install the package via Composer. 

```bash
composer require laragear/fingerprint
```

## How does this work?

This adds the `Fingerprint` service in your service container to conveniently create non-cryptographic fingerprint hashes of strings, `Stringable` instance, resources, or any object in a memory-efficient way.

It leverages the power of [`hash_init()`](https://www.php.net/manual/function.hash-init.php) and `json_encode` to hash anything, including Eloquent Models or Collections, and uses the fastest hash algorithm around, [`xxHash`](https://xxhash.com/) by default.

## Usage

To create fingerprints, you may use the `Fingerprint` facade, and any of its methods to create a hash.

```php
use Laragear\Fingerprint\Facades\Fingerprint;

// Make a Base64 encoded hash
Fingerprint::make($hashable);
Fingerprint::base64($hashable);

// Make a Base64 URL-safe hash to transmit over the network.
Fingerprint::base64Url($hashable);

// Make a "raw" binary hash.
Fingerprint::binary($hashable);

// Make a hexadecimal hash.
Fingerprint::hex($hashable);

// Compare two hashes.
Fingerprint::is($expected, $string);
Fingerprint::isNot($expected, $string);
```

### Algorithms

By default, Fingerprints are hashed using the [`xxh3` algorithm](https://xxhash.com/), [available since PHP 8.1](https://www.php.net/manual/migration81.new-features.php#migration81.new-features.hash.xxhash), which is the fastest non-cryptographic algorithm around and returns short hashes.

You may change it using the second parameter when creating fingerprints, and custom options as third parameter to be passed to `hash()|hash_init()`.

```php
use Laragear\Fingerprint\Facades\Fingerprint;

$hash = Fingerprint::base64($value, 'xxh128', [
    'seed' => 33
]);
```

> [!NOTE]
> 
> The algorithms available will depend on your environment. You may check them using `hash_algos()`.

#### Changing the default algorithm

You may change the default format using the `Laragear\Fingerprint\Fingerprinter::$algorithm` with the one you want to be passed down to `hash()|hash_init()` methods. You may do this while your application boots in your `App\Providers\AppServiceProvider` or `bootstrap\app.php`.

```php
use Illuminate\Foundation\Application;
use Laragear\Fingerprint\Fingerprinter;

return Application::configure(basePath: dirname(__DIR__))
    ->booted(function () {
        Fingerprinter::$algorithm = 'sha256';
    })
    ->create();
```

## Eloquent Models

You can use the [`Laragear\Fingerprint\HasFingerprints`](src/HasFingerprints.php) trait in your models to automatically calculate fingerprints in your model when saving them into the database.

Set the trait and use the `updateFingerprints()` method to return an array of all the fingerprints to calculate before persistence.

```php
use Illuminate\Database\Eloquent\Model;
use Laragear\Fingerprint\HasFingerprints;
use Laragear\Fingerprint\Fingerprinter;

/**
 * @property string $body 
 */
class Article extends Model
{
    use HasFingerprints;
    
    /**
     * Receive a Fingerprinter instance and returns an array of attributes with the fingerprints.
     *
     * @return array<string, string>
     */
    public function updateFingerprints(Fingerprinter $fingerprinter): array
    {
        return [
            'fingerprint' => $fingerprinter->make($this->body)
        ];
    }
}
```

Since you receive a Fingerprinter instance, you can create a fingerprint anyway you want.

```php
use Laragear\Fingerprint\Fingerprinter;

public function updateFingerprints(Fingerprinter $fingerprinter): array
{
    return [
        'fingerprint' => $fingerprinter->base64Url($this->body)
    ];
}
```

If you want to programmatically disable (or enable) the fingerprint update, use the `shouldUpdateFingerprints()` method.

```php
/**
 * Checks if the model should update its fingerprints before persisting into storage.
 */
public function shouldUpdateFingerprints(): bool
{
    return $this->isPublished();
}
```

## Laravel Octane compatibility

- There are no singletons using a stale app instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security-related issues, issue a [Security Advisor](https://github.com/Laragear/Fingerprint/security/advisories/new)

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at the time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011–2026 Laravel LLC.
