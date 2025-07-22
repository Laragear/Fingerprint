<?php

namespace Tests;

use ArrayIterator;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Laragear\Fingerprint\Enums\Format;
use Laragear\Fingerprint\Fingerprint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FingerprintTest extends TestCase
{
    protected function setUp(): void
    {
        Fingerprint::$use = 'xxh3';
        Fingerprint::$as = Format::AsBase64;
    }

    public function test_defaults(): void
    {
        static::assertSame('xxh3', Fingerprint::$use);
        static::assertSame(Format::AsBase64, Fingerprint::$as);
    }

    public function test_as_changes_format(): void
    {
        Fingerprint::$as = Format::AsRaw;

        $fingerprint = Fingerprint::of('test');

        $fingerprint->as(Format::AsBase64UrlSafe);

        static::assertSame('C4zr_u0NRZ8', (string) $fingerprint);
    }

    public function test_raw(): void
    {
        static::assertSame(Str::fromBase64('C4zr/u0NRZ8='), Fingerprint::of('test')->raw());
    }

    public function test_hash(): void
    {
        static::assertSame('C4zr/u0NRZ8=', Fingerprint::of('test')->hash());
    }

    public function test_base64(): void
    {
        static::assertSame('C4zr/u0NRZ8=', Fingerprint::of('test')->base64());
    }

    public function test_base64_url(): void
    {
        static::assertSame('C4zr_u0NRZ8', Fingerprint::of('test')->base64Url());
    }

    public function test_hex(): void
    {
        static::assertSame('0b8cebfeed0d459f', Fingerprint::of('test')->hex());
    }

    public function test_caches_fingerprint_hash(): void
    {
        $object = (object) ['text' => 'test'];

        $fingerprint = new Fingerprint(
            $object, Fingerprint::$use, [], Fingerprint::$as, Str::fromBase64('lu+aV3cITnY=')
        );

        static::assertSame('lu+aV3cITnY=', $fingerprint->hash());

        $object->text = 'another-test';

        static::assertSame('lu+aV3cITnY=', $fingerprint->hash());
    }

    public function test_does_not_caches_fingerprint_hash_with_callback(): void
    {
        $fluent = new Fluent(['text' => 'test']);

        $fingerprint = Fingerprint::of(fn () => $fluent->get('text'));

        static::assertSame('C4zr/u0NRZ8=', $fingerprint->hash());

        $fluent->set('text', 'another-test');

        static::assertSame('YoCnYg5SxkI=', $fingerprint->hash());
    }

    public function test_hashes_resource(): void
    {
        $resource = fopen(__DIR__ . '/fixtures/fingerprintable.txt', 'r');

        $fingerprint = Fingerprint::of($resource);

        $this->assertSame('6Eq/UI1GMuc=', $fingerprint->hash());
    }

    public function test_hashes_iterable(): void
    {
        $fingerprint = Fingerprint::of(new ArrayIterator(str_split('test')));

        $this->assertSame('TEO9OatCBX8=', $fingerprint->hash());
    }

    public function test_hashes_model(): void
    {
        $fingerprint = Fingerprint::of((new User())->forceFill(['name' => 'test']));

        $this->assertSame('ANVzoYpsoh0=', $fingerprint->hash());
    }

    public function test_compares_an_equal_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $equal = $fingerprint->hash();

        static::assertTrue($fingerprint->is($equal));
        static::assertFalse($fingerprint->isNot($equal));

        static::assertTrue($fingerprint->is(Str::fromBase64($equal), false));
        static::assertFalse($fingerprint->isNot(Str::fromBase64($equal), false));
    }

    public function test_compares_a_different_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $different = 'different';

        static::assertFalse($fingerprint->is($different));
        static::assertTrue($fingerprint->isNot($different));

        static::assertFalse($fingerprint->is(Str::fromBase64($different), false));
        static::assertTrue($fingerprint->isNot(Str::fromBase64($different), false));
    }

    public function test_compares_an_equal_fingerprint_instance(): void
    {
        $fingerprint = Fingerprint::of('test');

        $equal = Fingerprint::of('test');

        static::assertTrue($fingerprint->is($equal));
        static::assertFalse($fingerprint->isNot($equal));

        static::assertTrue($fingerprint->is(Str::fromBase64($equal), false));
        static::assertFalse($fingerprint->isNot(Str::fromBase64($equal), false));
    }

    public function test_compares_a_different_fingerprint_instance(): void
    {
        $fingerprint = Fingerprint::of('test');

        $different = Fingerprint::of('different');

        static::assertFalse($fingerprint->is($different));
        static::assertTrue($fingerprint->isNot($different));

        static::assertFalse($fingerprint->is(Str::fromBase64($different), false));
        static::assertTrue($fingerprint->isNot(Str::fromBase64($different), false));
    }

    public function test_of_uses_default_algorithm(): void
    {
        static::assertSame(Fingerprint::$use, Fingerprint::of('test')->uses());
    }

    public function test_of_uses_custom_algorithm(): void
    {
        static::assertSame('test-algo', Fingerprint::of('test', 'test-algo')->uses());
    }

    public function test_of_uses_options(): void
    {
        static::assertSame('l01vw7ZuFeA=', Fingerprint::of('test', options: ['seed' => 3])->hash());
    }

    public static function providesFormatToSerialize(): array
    {
        return [
            [Format::AsRaw, Str::fromBase64('3OMlCLsjizg=')],
            [Format::AsBase64, '3OMlCLsjizg='],
            [Format::AsBase64UrlSafe, '3OMlCLsjizg'],
            [Format::AsHex, 'dce32508bb238b38'],
        ];
    }

    #[DataProvider('providesFormatToSerialize')]
    public function test_serializes_into_string(Format $format, string $result): void
    {
        static::assertSame($result, (string) Fingerprint::of('value')->as($format));
        static::assertSame($result, Fingerprint::of('value')->as($format)->toString());
    }
}
