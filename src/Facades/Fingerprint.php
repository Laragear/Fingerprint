<?php

namespace Laragear\Fingerprint\Facades;

use Illuminate\Support\Facades\Facade;
use Laragear\Fingerprint\Fingerprinter;

/**
 * @method static string make(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string base64(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string base64Url(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string binary(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static string hex(mixed $target, string|null $algorithm = null, array $options = [])
 * @method static bool is(mixed $expected, mixed $string)
 * @method static bool isNot(mixed $expected, mixed $string)
 *
 * @see \Laragear\Fingerprint\Fingerprinter
 */
class Fingerprint extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return Fingerprinter::class;
    }
}
