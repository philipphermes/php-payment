<?php
declare(strict_types=1);

namespace PhilippHermes\PhpPayment;

use PhilippHermes\PhpPayment\DTO\ShippingDTO;
use Stripe\Checkout\Session;
use Stripe\Refund;

class Stripe implements StripeInterface
{
    public function __construct(
        private Credentials $credentials,
    )
    {
        \Stripe\Stripe::setApiKey( $this->credentials->stripeApiKey);
    }

    /**
     * @param \PhilippHermes\PhpPayment\DTO\ItemDTO[] $itemDTOList
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckoutUrl(array $itemDTOList, ?ShippingDTO $shippingDTO = null, string $email = ''): string
    {
        $data = [
            'line_items' => [],
            'mode' => 'payment',
            'success_url' =>  $this->credentials->successUrl . '?session={CHECKOUT_SESSION_ID}',
            'cancel_url' =>  $this->credentials->cancelUrl,
            'locale' =>  $this->credentials->locale,
        ];

        if ($shippingDTO instanceof ShippingDTO) {
            $intShipping = (int)($shippingDTO->amount * 100);

            $data['shipping_options'] = [
                [
                    'shipping_rate_data' => [
                        'display_name' => 'DHL',
                        'type' => 'fixed_amount',
                        'fixed_amount' => [
                            'currency' => $this->credentials->currency,
                            'amount' => $intShipping,
                        ],
                    ],
                ],
            ];
        }

        if ($email !== '') {
            $data['customer_email'] = $email;
        }

        foreach ($itemDTOList as $itemDTO) {
            $intPrice = (int)($itemDTO->price * 100);

            $data['line_items'][] = [
                "price_data" => [
                    "currency" => $this->credentials->currency,
                    "unit_amount" => $intPrice,
                    "product_data" => [
                        "name" => $itemDTO->name,
                        "description" => $itemDTO->sku,
                    ],
                ],
                "quantity" => $itemDTO->quantity,
            ];
        }

        $checkout_session = \Stripe\Checkout\Session::create($data);
        return $checkout_session->url;
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function refund(string $id, float $amount, string $reason = self::REASON_REQUESTED_BY_USER): bool
    {
        $session = $this->getSession($id);
        $paymentIntent = $session['payment_intent'];

        $intAmount = (int)($amount * 100);

        $refund = Refund::create([
            'payment_intent' => $paymentIntent,
            'amount' => $intAmount,
            'reason' => $reason,
        ]);

        if ($refund->status === 'succeeded') {
            return true;
        }

        return false;
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getSession(string $id): array
    {
        $session = Session::retrieve($id);
        return $session->toArray();
    }
}