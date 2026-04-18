# 💳 PayMongo Test API Documentation

This document contains all the code and configurations related to the PayMongo Payment Gateway integration for the Coffee Shop system.

## 1. Configuration (API Keys)
This file stores your secret and public keys. The `sk_test` prefix is what enables the "Authorized Test API" simulation.

**File:** `config/paymongo.php`
```php
<?php
// PayMongo API Configuration
define('PAYMONGO_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');

// Helper function to call PayMongo API
function callPayMongo($endpoint, $method = 'POST', $data = null) {
    $url = "https://api.paymongo.com/v1/" . $endpoint;
    $ch = curl_init();

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => ['attributes' => $data]]));
        }
    }

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => $error];
    }

    return json_decode($response, true);
}
?>
```

## 2. Backend Integration (Checkout Session)
This code handles the creation of a payment session and returns the checkout URL to the customer.

**File:** `ajax/order_actions.php` (Payment Section)
```php
// Create PayMongo Checkout Session
$sessionData = [
    'payment_method_types' => ['gcash', 'paymaya', 'grab_pay'],
    'line_items' => $lineItems,
    'send_email_receipt' => false,
    'show_description' => true,
    'description' => "Order #$orderId from Yours Truly Coffee Shop",
    'success_url' => "http://localhost/Coffee_Shop/index.php?payment=success&order_id=$orderId",
    'cancel_url'  => "http://localhost/Coffee_Shop/index.php?payment=cancelled&order_id=$orderId",
];

$sessionResponse = callPayMongo('checkout_sessions', 'POST', $sessionData);

if (isset($sessionResponse['data']['attributes']['checkout_url'])) {
    $checkoutUrl = $sessionResponse['data']['attributes']['checkout_url'];
    // ... logic to redirect user to $checkoutUrl
}
```

## 3. Payment Verification
This script checks if a payment was actually completed by querying the PayMongo session ID.

**File:** `ajax/order_actions.php` (Verify Section)
```php
case 'verify_payment':
    $orderId = intval($_POST['order_id'] ?? 0);
    // ... fetch order from DB
    
    require_once '../config/paymongo.php';
    $sessionResponse = callPayMongo("checkout_sessions/" . $order['payment_session_id'], 'GET');

    if (isset($sessionResponse['data']['attributes']['status'])) {
        $pmStatus = $sessionResponse['data']['attributes']['status'];
        
        if ($pmStatus === 'paid') {
            // Update order to 'is_paid = 1'
        }
    }
    break;
```

## ⚠️ Important Note: The "Authorize" Button
The **"Authorize Test Payment"** button that you see when testing is **not in this code**. It is part of the external PayMongo checkout page. 

As long as your `PAYMONGO_SECRET_KEY` starts with `sk_test_`, PayMongo will automatically show that button to help you test your integration without using real money.
