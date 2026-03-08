<?php

namespace Tests;

use Laragear\Fingerprint\Facades\Fingerprint;
use Laragear\Fingerprint\FingerprintServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FingerprintServiceProvider::class
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            Fingerprint::class
        ];
    }
}
