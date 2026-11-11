<?php

namespace App\Services\Couriers;

/**
 * Normalised result from any courier, so the admin panel does not have to
 * know which provider it is talking to.
 */
class CourierResult
{
    public function __construct(
        public readonly string $consignmentId,
        public readonly ?string $trackingCode = null,
        public readonly ?float $deliveryFee = null,
        public readonly array $raw = [],
    ) {}
}
