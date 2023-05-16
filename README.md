# PHP Payment

## Setup Credentials:

```php
$credentials = new \App\Credentials();

$credentials->paypalTest = true;
$credentials->paypalClientId = 'ClientId';
$credentials->paypalSecret = 'Secret';
$credentials->paypalBrandName = 'Test Shop';

$credentials->stripeApiKey = 'ApiKey';

$credentials->currency = 'eur';
$credentials->locale = 'de';

$credentials->successUrl = 'http://localhost:8000/success';
$credentials->cancelUrl = 'http://localhost:8000';

$paypal = new \App\Paypal($credentials);
$stripe = new \App\Stripe($credentials);
```

### Shipping
If you don't use shipping pass null
```php
$paypalShippingDTO = new \App\DTO\ShippingDTO();
$paypalShippingDTO->type = 'SHIPPING';
$paypalShippingDTO->address = 'Strasse 1';
$paypalShippingDTO->city = 'Düsseldorf';
$paypalShippingDTO->state = 'NRW';
$paypalShippingDTO->post = '40210';
$paypalShippingDTO->country = 'DE';
$paypalShippingDTO->amount = 4.99;
$paypalShippingDTO->fullName = 'Max Mustermann';
```

### Items:
```php
$itemDTO1 = new \App\DTO\ItemDTO();
$itemDTO1->name = 'Product 1';
$itemDTO1->sku = '100-001';
$itemDTO1->quantity = 2;
$itemDTO1->price = 2.0;

$itemDTO2 = new \App\DTO\ItemDTO();
$itemDTO2->name = 'Product 2';
$itemDTO2->sku = '101-001';
$itemDTO2->quantity = 1;
$itemDTO2->price = 4.0;
```

## Create Checkout Urls:
```php
//Paypal
try {
    $paypalUrl = $paypal->createOrder($paypalShippingDTO, [
        $itemDTO1,
        $itemDTO2,
    ]);
} catch (\GuzzleHttp\Exception\GuzzleException $exception) {
    echo $exception->getMessage();
    die();
}

//Stripe
try {
    $stripeUrl = $stripe->createCheckoutUrl([
        $itemDTO1,
        $itemDTO2,
    ], $paypalShippingDTO, 'maxmustermann@email.com');
    //if the email is not parsed the user has to provide an email in stripe himself
} catch (\Stripe\Exception\ApiErrorException $exception) {
    echo $exception->getMessage();
    die();
}
```

## Capture Order:
Stripe Orders are captured automatically
```php
//Paypal
try {
    $captureId = $paypal->captureOrder($_GET['token']); //get param after checkout success page
} catch (\GuzzleHttp\Exception\GuzzleException $exception) {
    echo $exception->getMessage();
    die();
}
```

## Refunds:
```php
//Paypal
try {
    $success = $paypal->refund(
        $captureId, //returned by captureOrder
        9.99,
        "Your note"
    );
}catch (\GuzzleHttp\Exception\GuzzleException $exception) {
    echo $exception->getMessage();
    die();
}

//Stripe
try {
    $success = $stripe->refund(
        $_GET['session'], //get param after checkout on success page
        9.99,
        \App\Stripe::REASON_REQUESTED_BY_USER,
    );
} catch (\Stripe\Exception\ApiErrorException $exception) {
    echo $exception->getMessage();
    die();
}
```

## Get Orders:

```php
//Paypal
try {
    $order = $paypal->getOrder($_GET['token']); //get param after checkout success page
} catch (\GuzzleHttp\Exception\GuzzleException $exception) {
    echo $exception->getMessage();
    die();
}

//Stripe
try {
    $session = $stripe->getSession($_GET['session']); //get param after checkout on success page
} catch (\Stripe\Exception\ApiErrorException $exception) {
    echo $exception->getMessage();
    die();
}
```