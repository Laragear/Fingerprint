# Fingerprint
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/fingerprint.svg)](https://packagist.org/packages/laragear/fingerprint)
[![Latest stable test run](https://github.com/Laragear/Fingerprint/actions/workflows/php.yml/badge.svg)](https://github.com/Laragear/Fingerprint/actions/workflows/php.yml)
[![Codecov coverage](https://codecov.io/gh/Laragear/Fingerprint/branch/graph/badge.svg?token=lOyu8F4EHc)](https://codecov.io/gh/Laragear/Fingerprint)
[![Maintainability](https://qlty.sh/gh/Laragear/projects/Fingerprint/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/Fingerprint)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Fingerprint&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Fingerprint)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/12.x/octane#introduction)

Ridiculously fast non-cryptographic hashes in your application or Eloquent Models.

```php
use Laragear\Fingerprint\Fingerprint;

$fingerprint = Fingerprint::of('some-string-or-object');

return $fingerprint->hash();
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable.

## Requirements

* PHP 8.2
* Laravel 11 or later

## Installation

You can install the package via Composer. 

```bash
composer require laragear/fingerprint
```

## How does this work?

This basically creates non-cryptographic fingerprint hashes of strings, `Stringable` instance, resources, or any object in a memory-efficient way. It leverages the power of [`hash_init()`](https://www.php.net/manual/function.hash-init.php) and `json_encode` to hash anything, including Eloquent Models or Collections, and uses the fastest hash algorithm around, [`xxHash`](https://xxhash.com/).

## Usage

To instance a Fingerprint, you may use the `of()` static method, which will automatically create an instance for the given string.

```php
use Laragear\Fingerprint\Fingerprint;

$fingerprint = Fingerprint::of('string')
```

The Fingerprint instance is not limited to strings. You can shove in anything that can be encoded into JSON, like Eloquent Models, or _traversable_ objects, like resource streams or Lazy Collections.

```php
use App\Models\Article;
use Laragear\Fingerprint\Fingerprint;

$fingerprint = Fingerprint::of(
    Article::latest()->select(['id', 'body'])->lazy(50)
);
```

Once done, you may retrieve the fingerprint hash encoded in Base64 using `hash()`, or just casting the Fingerprint as a string wherever you require.

```php
use Laragear\Fingerprint\Fingerprint;

$fingerprint = Fingerprint::of($text);

return "This is the article fingerprint: [$fingerprint]."
```

### Rehashing

The Fingerprint hash is generated on demand and cached inside the Fingerprint instance. If the value is an object and it changes, the hash will remain the same.

To avoid this, you may use a callback that returns the value to hash. Everytime the hash is required, it will be generated anew.

```php
use App\Models\Article;
use Laragear\Fingerprint\Fingerprint;

$article = Article::find(1);

$fingerprint = Fingerprint::of(fn () => $article->body);
```

> [!NOTE]
> 
> Because the hash is generated every time is required, you may want to save the hash into a variable to avoid hashing large values, and retrieving a new hash only when needed.

### Formats

By default, the Fingerprint hash returned is encoded in Base64 for portability. You may retrieve the hash in other formats using the available methods:

| Method               | Description                                                | Example       |
|----------------------|------------------------------------------------------------|---------------|
| `raw()`              | Returns the hash as a binary string                        | `...`      |
| `hash()`, `base64()` | Returns the hash encoded in Base64                         | `dGV+z/dA==`  |
| `base64UrlSafe()`    | Returns the hash encoded in Base64 and URL-Safe characters | `dGV-z_dA`    |
| `hex()`              | Returns the hash encoded in hexadecimal                    | `38d1ffa8...` |

You may change the default format using the `Laragear\Fingerprint\Fingerprint::$as` with the Format enum of choice. You may do this while your application boots in your `App\Providers\AppServiceProvider` or `bootstrap\app.php`. 

```php
use Illuminate\Foundation\Application;
use Laragear\Fingerprint\Enums\Format;
use Laragear\Fingerprint\Fingerprint;

return Application::configure(basePath: dirname(__DIR__))
    ->booted(function () {
        Fingerprint::$as = Format::AsHex;
    })
    ->create();
```

This may also be changed on a per-instance basis using as `as()`.

```php
use Laragear\Fingerprint\Enums\Format;
use Laragear\Fingerprint\Fingerprint;

$fingerprint = Fingerprint::of($value)->as(Format::AsHex);
```

### Algorithms

By default, Fingerprints are hashed using the [`xxh3` algorithm](https://xxhash.com/), [available since PHP 8.1](https://www.php.net/manual/migration81.new-features.php#migration81.new-features.hash.xxhash), which is the fastest non-cryptographic algorithm around and returns short hashes.

You may change it using the second parameter when creating a Fingerprint instance, and custom options to be passed to `hash_init()`.

```php
use Laragear\Fingerprint\Fingerprint;

$fingerprint = Fingerprint::of($value, 'xxh128', [
    'seed' => 33
]);
```

You may also change the default algorithm for the Fingerprint instances through the `$use` static property.

```php
use Laragear\Fingerprint\Fingerprint;

Fingerprint::$use = 'xxh128';
```

> [!NOTE]
> 
> The algorithms available will depend on your environment. You may check them using `hash_algos()`.

## Eloquent Models

You can use the `Laragear\Fingerprint\Casts\AsFingerprint` cast in any of your model attributes to create a Fingerprint instance based on one or more attributes from the model, and save the resulting hash into the database.

```php
use Illuminate\Database\Eloquent\Model;
use Laragear\Fingerprint\Casts\AsFingerprint;

/**
 * @property \Laragear\Fingerprint\Fingerprint $fingerprint 
 */
class Article extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts()
    {
        return [
            'fingerprint' => AsFingerprint::of('body'),
        ];
    }    
}
```

> [!NOTE]
> 
> The `AsFingerprint` cast doesn't support hash options. If you require custom options, consider using an [Eloquent accessor/mutator](https://laravel.com/docs/12.x/eloquent-mutators#accessors-and-mutators) instead.

The cast also supports using a custom algorithm and format through the second and third argument. The Cast configuration will override the default algorithm and format, and these will be respected when retrieving or saving the Fingerprint instance.

```php
use Laragear\Fingerprint\Casts\AsFingerprint;
use Laragear\Fingerprint\Enums\Format;

AsFingerprint::of(['title', 'body'], 'sha256', Format::AsBase64UrlSafe)
```

> [!NOTE]
> 
> The Fingerprint instance returned by `AsFingerprint` uses a [callback to return the value to hash](#rehashing), meaning, the hash will be regenerated each time is required.

## Serialization

> [!DANGER]
> 
> Fingerprint instances are not safely serializable. If you require serializing a Fingerprint into storage, transform it as a string.

## Laravel Octane compatibility

- There are no singletons using a stale app instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security related issues, issue a [Security Advisor](https://github.com/Laragear/Fingerprint/security/advisories/new)

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2025 Laravel LLC.
