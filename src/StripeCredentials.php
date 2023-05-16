<?php
declare(strict_types=1);

namespace PhilippHermes\PhpPayment;

class StripeCredentials
{
    public function __construct(
        public string $stripeApiKey,
        public string $successUrl,
        public string $cancelUrl,
        public string $currency,
        public string $locale,
    )
    {
    }
}