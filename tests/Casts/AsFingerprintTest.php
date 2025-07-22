<?php

namespace Tests\Casts;

use InvalidArgumentException;
use Illuminate\Foundation\Auth\User;
use Laragear\Fingerprint\Casts\AsFingerprint;
use Laragear\Fingerprint\Enums\Format;
use Laragear\Fingerprint\Fingerprint;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ValueError;

class AsFingerprintTest extends TestCase
{
    public function test_declares_with_one_attribute(): void
    {
        static::assertSame(AsFingerprint::class . ':xxh3,base64,test', AsFingerprint::of('test'));
    }

    public function test_declares_with_multiple_attributes(): void
    {
        static::assertSame(AsFingerprint::class . ':xxh3,base64,foo,bar', AsFingerprint::of(['foo', 'bar']));
    }

    public static function providesDeclaringAttributes(): array
    {
        return [
            [''],
            [[]],
        ];
    }

    #[DataProvider('providesDeclaringAttributes')]
    public function test_declares_throws_when_no_attributes(mixed $params): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('The attributes must not be empty');

        AsFingerprint::of($params);
    }

    public function test_declares_with_custom_algorithm(): void
    {
        static::assertSame(AsFingerprint::class . ':test,base64,foo,bar', AsFingerprint::of(['foo', 'bar'], 'test'));
    }

    public function test_declares_with_custom_format(): void
    {
        static::assertSame(AsFingerprint::class . ':xxh3,hex,foo', AsFingerprint::of('foo', format: Format::AsHex));
    }

    public function test_new_throws_when_no_arguments(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No arguments provided.');

        new AsFingerprint([]);
    }

    public function test_get_returns_null(): void
    {
        $cast = new AsFingerprint([Fingerprint::$use, Fingerprint::$as->value, 'foo']);
        $model = (new User())->forceFill(['foo' => 'bar', 'first' => 'second', 'hash' => null]);

        static::assertNull($cast->get($model, 'hash', null, $model->getAttributes()));
    }

    public static function providesAttributesToHash(): array
    {
        return [
            [['foo'], '90LTQC924pc='],
            [['foo', 'first'], '/XOoc5T71yo=']
        ];
    }

    #[DataProvider('providesAttributesToHash')]
    public function test_get_returns_fingerprint_from_hash_with_callback(array $attributes, string $result): void
    {
        $cast = new AsFingerprint([Fingerprint::$use, Fingerprint::$as->value, ...$attributes]);
        $model = (new User())->forceFill(['foo' => 'bar', 'first' => 'second', 'hash' => 'invalid']);

        $fingerprint = $cast->get($model, 'hash', null, $model->getAttributes());

        static::assertInstanceOf(Fingerprint::class, $fingerprint);
        static::assertSame(Fingerprint::$use, $fingerprint->uses());
        static::assertSame($result, $fingerprint->toString());
    }

    public function test_set_null(): void
    {
        $cast = new AsFingerprint([Fingerprint::$use, Fingerprint::$as->value, 'foo']);
        $model = (new User())->forceFill(['foo' => 'bar', 'first' => 'second', 'hash' => 'invalid']);

        static::assertNull($cast->set($model, 'hash', null, $model->getAttributes()));
    }

    public function test_set_fingerprint_instance(): void
    {
        $cast = new AsFingerprint([Fingerprint::$use, Fingerprint::$as->value, 'foo']);
        $model = new User();

        $fingerprint = Fingerprint::of('test');

        $hash = $cast->set($model, 'hash', $fingerprint, $model->getAttributes());

        static::assertSame('C4zr/u0NRZ8=', $hash);
    }

    public function test_set_throws_if_not_fingerprint_instance(): void
    {
        $cast = new AsFingerprint([Fingerprint::$use, Fingerprint::$as->value, 'foo']);
        $model = new User();

        $fingerprint = 'not-a-fingerprint';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value to set as [hash] must be an instance of [Laragear\Fingerprint\Fingerprint]');

        $cast->set($model, 'hash', $fingerprint, $model->getAttributes());
    }

    public function test_uses_format_of_cast(): void
    {
        $cast = new AsFingerprint([Fingerprint::$use, Format::AsHex->value, 'foo']);
        $model = new User();

        $fingerprint = Fingerprint::of('test');

        $hash = $cast->set($model, 'hash', $fingerprint, $model->getAttributes());

        static::assertSame('0b8cebfeed0d459f', $hash);
    }

    public function test_uses_algorithm_of_cast(): void
    {
        $cast = new AsFingerprint(['test-algo', Format::AsHex->value, 'foo']);
        $model = new User();

        $fingerprint = Mockery::mock(Fingerprint::class);
        $fingerprint->expects('as')->with(Format::AsHex)->andReturnSelf();
        $fingerprint->expects('use')->with('test-algo')->andReturnSelf();
        $fingerprint->expects('toString')->andReturn('it-worked');

        $hash = $cast->set($model, 'hash', $fingerprint, $model->getAttributes());

        static::assertSame('it-worked', $hash);
    }
}
