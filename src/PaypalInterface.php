<?php
declare(strict_types=1);

namespace App;

use App\DTO\ItemDTO;
use App\DTO\ShippingDTO;

interface PaypalInterface
{
    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function auth(): void;

    /**
     * @param ?ShippingDTO $shippingDTO
     * @param ItemDTO[] $itemDTOList
     * @return string Url for payment
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder(?ShippingDTO $shippingDTO, array $itemDTOList): string;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function captureOrder(string $token): string;

    public function refund(string $captureId, float $amount, string $note): bool;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrder(string $token): array;
}