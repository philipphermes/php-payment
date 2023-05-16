<?php
declare(strict_types=1);

namespace PhilippHermes\PhpPayment;

class Credentials
{
    public string $paypalClientId;
    public string $paypalSecret;
    public string $paypalBrandName;
    public string $paypalRequestId = ''; //Set automatically
    public string $paypalAuthToken = ''; //Set automatically
    public bool $paypalTest = true;

    public string $stripeApiKey;

    public string $successUrl;
    public string $cancelUrl;
    public string $currency;
    public string $locale;
}