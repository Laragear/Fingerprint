<?php

namespace Tests;

use Laragear\Fingerprint\Fingerprinter;
use Laragear\MetaTesting\InteractsWithServiceProvider;

class ServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider;

    public function test_registers_fingerprinter_as_singleton(): void
    {
        $this->assertHasSingletons(Fingerprinter::class);
    }

    public function test_registers_alias(): void
    {
        $this->assertHasAlias(Fingerprinter::class, 'fingerprint');
    }
}
