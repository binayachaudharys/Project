<?php

namespace App\Services\Payments;

final class GatewayRedirect
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public string $url,
        public array $params = [],
        public string $method = 'GET',
    ) {}
}
