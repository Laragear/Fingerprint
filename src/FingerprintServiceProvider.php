<?php

namespace Laragear\Fingerprint;

use Illuminate\Support\ServiceProvider;

class FingerprintServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->singleton(Fingerprinter::class, static function (): Fingerprinter {
            return new Fingerprinter();
        });
        $this->app->alias(Fingerprinter::class, 'fingerprint');
    }
}
