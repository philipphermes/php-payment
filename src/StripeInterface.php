<?php
declare(strict_types=1);

namespace App;

use App\DTO\ShippingDTO;

interface StripeInterface
{
    public const REASON_DUPLICATE = 'duplicate';
    public const REASON_FRAUDULENT = 'fraudulent';
    public const REASON_REQUESTED_BY_USER = 'requested_by_customer';

    /**
     * @param \App\DTO\ItemDTO[] $itemDTOList
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckoutUrl(array $itemDTOList, ?ShippingDTO $shippingDTO): string;

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function refund(string $id, float $amount, string $reason = self::REASON_REQUESTED_BY_USER): bool;

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getSession(string $id): array;
}