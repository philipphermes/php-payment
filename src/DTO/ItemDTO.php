<?php
declare(strict_types=1);

namespace PhilippHermes\PhpPayment\DTO;

class ItemDTO
{
    public string $name;
    public int $quantity;
    public string $sku;
    public float $price;
}