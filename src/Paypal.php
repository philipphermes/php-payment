<?php
declare(strict_types=1);

namespace PhilippHermes\PhpPayment;

use PhilippHermes\PhpPayment\DTO\ShippingDTO;
use PhilippHermes\PhpPayment\DTO\ItemDTO;
use GuzzleHttp\Client;

class Paypal implements PaypalInterface
{
    private Client $client;

    private array $urls = [
        true => [
            'auth' => 'https://api-m.sandbox.paypal.com/v1/oauth2/token',
            'order' => 'https://api-m.sandbox.paypal.com/v2/checkout/orders/',
            'refund' => 'https://api-m.sandbox.paypal.com/v2/payments/captures/',
        ],
        false => [
            'auth' => 'https://api-m.paypal.com/v1/oauth2/token',
            'order' => 'https://api-m.paypal.com/v2/checkout/orders/',
            'refund' => 'https://api-m.paypal.com/v2/payments/captures/',
        ]
    ];

    private array $currentUrls;

    public function __construct(
        private PaypalCredentials $credentials,
    )
    {
        $this->client = new Client();
        $this->currentUrls = $this->urls[$this->credentials->paypalTest];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function auth(): void
    {
        $response = $this->client->request('POST', $this->currentUrls['auth'], [
            'auth' => [
                $this->credentials->paypalClientId,
                $this->credentials->paypalSecret,
            ],
            'Content-Type' => 'application/x-www-form-urlencoded',
            'body' => 'grant_type=client_credentials',
        ]);

        $responseBody = $response->getBody()->getContents();
        $responseBodyArray = json_decode($responseBody, true);
        $this->credentials->paypalAuthToken = $responseBodyArray['access_token'];
    }

    /**
     * @param ?ShippingDTO $shippingDTO
     * @param ItemDTO[] $itemDTOList
     * @return string Url for payment
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder(?ShippingDTO $shippingDTO, array $itemDTOList): string
    {
        if ($this->credentials->paypalAuthToken === '') {
            $this->auth();
        }

        $total = 0;
        $itemsPaypal = [];
        $this->credentials->paypalRequestId = uniqid(time() . 'Paypal');

        foreach ($itemDTOList as $itemDTO) {
            $itemsPaypal[] = [
                'name' => $itemDTO->name,
                'quantity' => $itemDTO->quantity,
                'sku' => $itemDTO->sku,
                'unit_amount' => [
                    'currency_code' =>  $this->credentials->currency,
                    'value' => $itemDTO->price,
                ],
            ];

            $total += $itemDTO->quantity * $itemDTO->price;
        }

        $purchaseUnits = [
            [
                'description' => 'Order',
                'items' => $itemsPaypal,
                'amount' => [
                    "breakdown" => [
                        'item_total' => [
                            'currency_code' =>  $this->credentials->currency,
                            'value' => $total,
                        ],
                    ],
                    "currency_code" =>  $this->credentials->currency,
                    "value" => $total + $shippingDTO->amount,
                ],
            ],
        ];

        if ($shippingDTO instanceof ShippingDTO) {
            $shippingPreference = 'SET_PROVIDED_ADDRESS';

            $shippingPaypal = [
                'type' => $shippingDTO->type,
                'name' => [
                    'full_name' => $shippingDTO->fullName,
                ],
                'address' => [
                    'address_line_1' => $shippingDTO->address,
                    'admin_area_2' => $shippingDTO->city,
                    'admin_area_1' => $shippingDTO->state,
                    'postal_code' => $shippingDTO->post,
                    'country_code' => $shippingDTO->country,
                ],
            ];

            $purchaseUnits[0]['shipping'] = $shippingPaypal;
            $purchaseUnits[0]['amount']['breakdown']['shipping'] = [
                'currency_code' =>  $this->credentials->currency,
                'value' => $shippingDTO->amount,
            ];
        } else {
            $shippingPreference = 'NO_SHIPPING';
        }


        $response = $this->client->request('POST',  $this->currentUrls['order'], [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->credentials->paypalAuthToken,
                'PayPal-Request-Id' => $this->credentials->paypalRequestId,
            ],
            'json' => [
                'purchase_units' => $purchaseUnits,
                "intent" => "CAPTURE",
                "payment_source" => [
                    "paypal" => [
                        'experience_context' => [
                            "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                            "payment_method_selected" => "PAYPAL",
                            "brand_name" =>  $this->credentials->paypalBrandName,
                            "locale" =>  $this->credentials->locale,
                            "landing_page" => "LOGIN",
                            "shipping_preference" => $shippingPreference,
                            "user_action" => "PAY_NOW",
                            "return_url" =>  $this->credentials->successUrl,
                            "cancel_url" =>  $this->credentials->cancelUrl,
                        ],
                    ],
                ],
            ],
        ]);

        $responseBody = $response->getBody()->getContents();

        $responseBodyArray = json_decode($responseBody, true);

        return $responseBodyArray['links'][1]['href'];
    }

    /**
     * @return string CaptureId needed for refunds
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function captureOrder(string $token): string
    {
        if ($this->credentials->paypalAuthToken === '') {
            $this->auth();
        }

        $response = $this->client->request('POST',  $this->currentUrls['order'] . $token . '/capture', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->credentials->paypalAuthToken,
                'PayPal-Request-Id' => $this->credentials->paypalRequestId,
                'Content-Type' => 'application/json',
            ],
        ]);

        $responseBody = $response->getBody()->getContents();

        $responseBodyArray = json_decode($responseBody, true);

        return $responseBodyArray['purchase_units'][0]['payments']['captures'][0]['id'];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refund(string $captureId, float $amount, string $note): bool
    {
        if ($this->credentials->paypalAuthToken === '') {
            $this->auth();
        }

        $response = $this->client->request('POST',  $this->currentUrls['refund'] . $captureId . '/refund', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->credentials->paypalAuthToken,
                'PayPal-Request-Id' => $this->credentials->paypalRequestId,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'amount' => [
                    'value' => $amount,
                    'currency_code' =>  $this->credentials->currency,
                ],
                'note_to_payer' => $note,
            ],
        ]);

        $responseBody = $response->getBody()->getContents();

        $responseBodyArray = json_decode($responseBody, true);

        if ($responseBodyArray['status'] === 'COMPLETED') {
            return true;
        }

        return false;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrder(string $token): array
    {
        $response = $this->client->request('GET',  $this->currentUrls['order'] . $token, [
            'headers' => [
                'Authorization' => 'Bearer ' .  $this->credentials->paypalAuthToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        $responseBody = $response->getBody()->getContents();

        return json_decode($responseBody, true);
    }
}