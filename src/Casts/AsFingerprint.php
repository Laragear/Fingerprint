<?php

namespace Laragear\Fingerprint\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laragear\Fingerprint\Enums\Format;
use Laragear\Fingerprint\Fingerprint;
use ValueError;
use function array_flip;
use function array_intersect_key;
use function array_slice;

class AsFingerprint implements CastsAttributes
{
    /**
     * The Fingerprint Algorithm to use
     */
    protected string $algorithm;

    /**
     * The format to use to cast Fingerprint instances into strings.
     */
    protected Format $format;

    /**
     * The attributes to use for hashing.
     *
     * @var string[]
     */
    protected array $attributes;

    /**
     * Create a new Cast Attributes instance.
     *
     * @param  string[]  $arguments
     */
    public function __construct(array $arguments)
    {
        if (empty($arguments)) {
            throw new ValueError('No arguments provided.');
        }

        $this->algorithm = $arguments[0];
        $this->format = Format::from($arguments[1]);
        $this->attributes = array_slice($arguments, 2);
    }

    /**
     * @inheritDoc
     */
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        if (!isset($attributes[$key])) {
            return null;
        }

        return new Fingerprint(
            function () use ($model): array {
                return array_intersect_key($model->getAttributes(), array_flip($this->attributes));
            },
            $this->algorithm, [], $this->format, $attributes[$key],
        );
    }

    /**
     * @inheritDoc
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Fingerprint) {
            return $value->as($this->format)->toString();
        }

        throw new InvalidArgumentException(
            "The value to set as [$key] must be an instance of [Laragear\Fingerprint\Fingerprint].",
        );
    }

    /**
     * Create a new "As Fingerprint" cast declaration.
     *
     * @param  string|string[]  $attributes
     * @param  string|null  $algorithm
     * @param  \Laragear\Fingerprint\Enums\Format|null  $format
     * @return string
     */
    public static function of(string|array $attributes, ?string $algorithm = null, ?Format $format = null): string
    {
        if (empty($attributes)) {
            throw new ValueError('The attributes must not be empty.');
        }

        $algorithm = $algorithm ?? Fingerprint::$use;
        $format = $format ?? Fingerprint::$as;

        return static::class.':'.implode(',', [$algorithm, $format->value, ...(array) $attributes]);
    }
}
