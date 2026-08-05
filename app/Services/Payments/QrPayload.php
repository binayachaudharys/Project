<?php

namespace App\Services\Payments;

final class QrPayload
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $qrData,
        public array $meta = [],
    ) {}
}
