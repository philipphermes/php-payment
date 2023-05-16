<?php
declare(strict_types=1);

namespace PhilippHermes\PhpPayment\DTO;

class ShippingDTO
{
    public string $fullName;
    public string $type;
    public string $address;
    public string $city;
    public string $state;
    public string $post;
    public string $country;
    public float $amount;
}