<?php

namespace Laragear\Fingerprint;

use Illuminate\Database\Eloquent\Model;
use function app;

trait HasFingerprints
{
    /**
     * Boot the trait.
     */
    protected static function bootHasFingerprints(): void
    {
        static::saving(static function (Model $model): void {
            $model->shouldUpdateFingerprints() && $model->forceFill(
                $model->updateFingerprints(app(Fingerprinter::class))
            );
        });
    }

    /**
     * Checks if the model should update its fingerprints before persisting into storage.
     */
    public function shouldUpdateFingerprints(): bool
    {
        return true;
    }

    /**
     * Receive a Fingerprinter instance and returns an array of attributes with the fingerprints.
     *
     * @return array<string, string>
     */
    public function updateFingerprints(Fingerprinter $fingerprinter): array
    {
        return [
            // 'fingerprint' => $fingerprinter->make($this->body)
        ];
    }
}
