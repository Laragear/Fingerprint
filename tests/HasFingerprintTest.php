<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laragear\Fingerprint\Fingerprinter;
use Laragear\Fingerprint\HasFingerprints;
use Mockery\MockInterface;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
class HasFingerprintTest extends TestCase
{
    use RefreshDatabase;

    protected const array USER_ATTRIBUTES = ['name' => 'name', 'email' => 'email', 'password' => 'password'];

    protected function tearDown(): void
    {
        parent::tearDown();

        UserWithProgrammaticFingerprintUpdate::$update = false;
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->text('fingerprint')->nullable();
        });
    }

    public function test_does_not_persist_fingerprints_by_default(): void
    {
        $this->mock(Fingerprinter::class)->expects('make')->never();

        UserWithoutFingerprints::make()->forceFill(static::USER_ATTRIBUTES)->save();

        $this->assertDatabaseHas(UserWithFingerprint::class, [
            'fingerprint' => null
        ]);
    }

    public function test_updates_fingerprint_based_on_attribute(): void
    {
        $this->mock(Fingerprinter::class)->expects('make')->with('name')->andReturn('hashed:name');

        $model = UserWithFingerprint::make()->forceFill(static::USER_ATTRIBUTES);

        static::assertNull($model->fingerprint);

        $model->save();

        $this->assertDatabaseHas(UserWithFingerprint::class, [
            'fingerprint' => 'hashed:name'
        ]);

        static::assertSame('hashed:name', $model->fingerprint);
    }

    public function test_updates_fingerprint_on_subsequent_updates(): void
    {
        $this->mock(Fingerprinter::class, static function (MockInterface $mock): void {
            $mock->expects('make')->with('name')->andReturn('hashed:name');
            $mock->expects('make')->with('updated')->andReturn('hashed:updated');
        });

        $model = UserWithFingerprint::make()->forceFill(static::USER_ATTRIBUTES);

        $model->save();

        $model->forceFill(['name' => 'updated'])->save();

        $this->assertDatabaseHas(UserWithFingerprint::class, [
            'fingerprint' => 'hashed:updated'
        ]);

        static::assertSame('hashed:updated', $model->fingerprint);
    }

    public function test_programmatically_disables_fingerprint_generation_on_update(): void
    {
        $this->mock(Fingerprinter::class, static function (MockInterface $mock): void {
            $mock->expects('make')->with('name')->never();
            $mock->expects('make')->with('updated')->andReturn('hashed:updated');
        });

        $model = UserWithProgrammaticFingerprintUpdate::make()->forceFill(static::USER_ATTRIBUTES);
        $model->save();

        $this->assertDatabaseHas(UserWithProgrammaticFingerprintUpdate::class, [
            'fingerprint' => null
        ]);

        UserWithProgrammaticFingerprintUpdate::$update = true;

        $model->forceFill(['name' => 'updated'])->save();

        $this->assertDatabaseHas(UserWithProgrammaticFingerprintUpdate::class, [
            'fingerprint' => 'hashed:updated'
        ]);

        static::assertSame('hashed:updated', $model->fingerprint);
    }
}

abstract class BaseStubUser extends User
{
    use HasFingerprints;

    protected $table = 'users';
}

class UserWithoutFingerprints extends BaseStubUser
{

}

class UserWithFingerprint extends BaseStubUser
{
    public function updateFingerprints(Fingerprinter $fingerprinter): array
    {
        return [
            'fingerprint' => $fingerprinter->make($this->getAttribute('name')),
        ];
    }
}

class UserWithProgrammaticFingerprintUpdate extends UserWithFingerprint
{
    public static bool $update = false;

    public function shouldUpdateFingerprints(): bool
    {
        return static::$update;
    }
}
