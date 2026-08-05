<?php

namespace App\Services\Payments;

final class VerifyResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $ok,
        public ?string $idempotencyKey = null,
        public ?string $gatewayReference = null,
        public array $payload = [],
        public ?string $failureReason = null,
    ) {}
}
