<?php

namespace Laragear\Fingerprint;

use Closure;
use Generator;
use Laragear\Fingerprint\Enums\Format;
use Stringable;
use function base64_decode;
use function base64_encode;
use function is_array;
use function is_iterable;
use function rtrim;
use function strtr;

/**
 * @template TValue
 */
class Fingerprint implements Stringable
{
    /**
     * The default algorithm to use to create non-cryptographic fingerprints.
     */
    public static string $use = 'xxh3';

    /**
     * The default formatting type to cast Fingerprint instances into strings.
     */
    public static Enums\Format $as = Enums\Format::AsBase64;

    /**
     * Create a new Fingerprint instance.
     *
     * @param  TValue|\Closure():TValue  $value
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        protected mixed $value,
        protected string $algorithm,
        protected array $options,
        protected Enums\Format $format,
        protected ?string $hash = null,
    ) {
        //
    }

    /**
     * Returns the algorithm used to generate a fingerprint hash.
     */
    public function uses(): string
    {
        return $this->algorithm;
    }

    /**
     * Sets the formatting type when casting this Fingerprint instance into a string.
     */
    public function as(Enums\Format $as): static
    {
        $this->format = $as;

        return $this;
    }

    /**
     * Returns the fingerprint hash as a binary string.
     */
    public function raw(): string
    {
        if ($this->value instanceof Closure) {
            return $this->hash = $this->generate();
        }

        return $this->hash ??= $this->generate();
    }

    /**
     * Returns the fingerprint hash encoded as Base64.
     */
    public function hash(): string
    {
        return $this->base64();
    }

    /**
     * Returns the fingerprint hash encoded as Base64.
     */
    public function base64(): string
    {
        return base64_encode($this->raw());
    }

    /**
     * Returns the fingerprint hash encoded as Base64 with URL-safe characters.
     */
    public function base64Url(): string
    {
        return rtrim(strtr($this->base64(), ['+' => '-', '/' => '_']), '=');
    }

    /**
     * Returns the fingerprint hash as a hexadecimal string.
     */
    public function hex(): string
    {
        return bin2hex($this->raw());
    }

    /**
     * Generates a fingerprint hash as a binary string.
     */
    protected function generate(): string
    {
        $context = hash_init($this->algorithm, 0, '', $this->options);

        foreach ($this->normalizeValue($this->value) as $value) {
            hash_update($context, json_encode($value));
        }

        return hash_final($context, true);
    }

    /**
     * Normalize the fingerprintable value into an iterable object for hashing.
     */
    protected static function normalizeValue(mixed $value): iterable
    {
        if ($value instanceof Closure) {
            $value = $value();
        }

        return match (true) {
            is_array($value) => $value,
            is_iterable($value) => (static function () use ($value): Generator {
                foreach ($value as $yield) {
                    yield $yield;
                }
            })(),
            is_resource($value) => (static function () use ($value): Generator {
                rewind($value);

                while (!feof($value)) {
                    yield fgetc($value);
                }
            })(),
            default => [$value],
        };
    }

    /**
     * Determines if this fingerprint hash and the issued hash are the same.
     */
    public function is(self|string $hash, bool $fromBase64 = true): bool
    {
        if ($hash instanceof self) {
            $hash = $hash->raw();
        } elseif ($fromBase64) {
            $hash = base64_decode($hash);
        }

        return hash_equals($this->raw(), $hash);
    }

    /**
     * Determines if this fingerprint hash and the issued hash are different.
     */
    public function isNot(self|string $hash, bool $fromBase64 = true): bool
    {
        return !$this->is($hash, $fromBase64);
    }

    /**
     * Returns the string representation of the object.
     */
    public function toString(): string
    {
        return $this->__toString();
    }

    /**
     * Returns the string representation of the object.
     */
    public function __toString(): string
    {
        return match($this->format) {
            Format::AsHex => $this->hex(),
            Format::AsBase64 => $this->base64(),
            Format::AsBase64UrlSafe => $this->base64Url(),
            default => $this->raw(),
        };
    }

    /**
     * Create a new Fingerprint instance.
     *
     * @param  array<string, mixed>  $options
     */
    public static function of(mixed $value, ?string $algorithm = null, array $options = []): static
    {
        return new static($value, $algorithm ?? static::$use, $options, static::$as);
    }
}
