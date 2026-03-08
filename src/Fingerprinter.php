<?php

namespace Laragear\Fingerprint;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use JsonSerializable;
use Stringable;
use function base64_encode;
use function bin2hex;
use function feof;
use function fgets;
use function hash;
use function hash_equals;
use function hash_final;
use function hash_init;
use function hash_update;
use function is_iterable;
use function is_resource;
use function is_string;
use function json_encode;
use function rewind;
use function rtrim;
use function strtr;
use const JSON_THROW_ON_ERROR;

class Fingerprinter
{
    /**
     * The default algorithm to use out-of-the-box.
     */
    public const string DEFAULT_ALGORITHM = 'xxh3';

    /**
     * The algorithm to use for fingerprinting when no algorithm is issued.
     */
    public static string $algorithm = self::DEFAULT_ALGORITHM;

    /**
     * Determines if this fingerprint hash and the issued hash are the same.
     */
    public function is(mixed $expected, mixed $string): bool
    {
        return hash_equals($expected, $string);
    }

    /**
     * Determines if this fingerprint hash and the issued hash are different.
     */
    public function isNot(mixed $expected, mixed $string): bool
    {
        return !$this->is($expected, $string);
    }

    /**
     * Returns the fingerprint hash encoded in Base64.
     */
    public function make(mixed $target, ?string $algorithm = null, array $options = []): string
    {
        return base64_encode($this->generate($target, $algorithm, $options));
    }

    /**
     * Returns the fingerprint hash encoded in Base64.
     */
    public function base64(mixed $target, ?string $algorithm = null, array $options = []): string
    {
        return $this->make($target, $algorithm, $options);
    }

    /**
     * Returns the fingerprint hash encoded as Base64 with URL-safe characters.
     */
    public function base64Url(mixed $target, ?string $algorithm = null, array $options = []): string
    {
        return rtrim(strtr($this->binary($target, $algorithm, $options), ['+' => '-', '/' => '_']), '=');
    }

    /**
     * Returns the fingerprint hash as a binary string.
     */
    public function binary(mixed $target, ?string $algorithm = null, array $options = []): string
    {
        return $this->generate($target, $algorithm, $options);
    }

    /**
     * Returns the fingerprint hash as a hexadecimal string.
     */
    public function hex(mixed $target, ?string $algorithm = null, array $options = []): string
    {
        return bin2hex($this->binary($target, $algorithm, $options));
    }

    /**
     * Generates a fingerprint hash as a binary string.
     */
    protected function generate(mixed $target, ?string $algorithm, array $options): string
    {
        if (!$target) {
            return '';
        }

        if (!$algorithm) {
            $algorithm = static::$algorithm;
        }

        // When the target can be represented as a string, we can just hash it and return the
        // result as-is. For other types of objects, we will try to normalize them to avoid
        // loading the whole buffer into system memory and encode each "part" using JSON.
        if ($target instanceof JsonSerializable) {
            return hash($algorithm, json_encode($target), true, $options);
        } elseif ($target instanceof Jsonable) {
            return hash($algorithm, $target->toJson(), true, $options);
        } elseif (is_string($target) || $target instanceof Stringable) {
            return hash($algorithm, $target, true, $options);
        }

        $context = hash_init($algorithm, 0, '', $options);

        foreach ($this->normalizeTarget($target) as $memberOrLine) {
            hash_update($context, json_encode($memberOrLine, JSON_THROW_ON_ERROR));
        }

        return hash_final($context, true);
    }

    /**
     * Normalize the fingerprintable target into an iterable object for hashing.
     */
    protected function normalizeTarget(mixed $target): iterable
    {
        return match (true) {
            is_resource($target) => new LazyCollection(function () use ($target) {
                rewind($target);

                while (!feof($target)) {
                    yield fgets($target);
                }
            }),
            is_iterable($target) => new LazyCollection(function () use ($target) {
                foreach ($target as $value) {
                    yield $value;
                }
            }),
            $target instanceof Arrayable => $target->toArray(),
            default => Arr::wrap($target),
        };
    }
}
