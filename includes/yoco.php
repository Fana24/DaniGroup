<?php
/**
 * Yoco checkout helper - ports the logic from the original app's
 * YocoPaymentService.cs.
 *
 * IMPORTANT: same caveat as the original C# service - the payload and
 * response field names here should be checked against your current
 * Yoco developer docs / portal before going live with real payments.
 * This structure is correct for Yoco's online checkout + Bearer auth
 * pattern, but exact field names can change.
 *
 * Returns an array: ['ok' => bool, 'id' => ?string, 'redirect_url' => ?string, 'error' => ?string]
 */
function yoco_create_checkout(array $order): array
{
    $amountCents = (int) round(((float) $order['total_amount']) * 100);

    $successUrl = base_url('payment/success.php') . '?orderId=' . $order['id'];
    $cancelUrl  = base_url('payment/cancel.php') . '?orderId=' . $order['id'];

    $payload = [
        'amount'   => $amountCents,
        'currency' => 'ZAR',
        'successUrl' => $successUrl,
        'cancelUrl'  => $cancelUrl,
        'metadata' => [
            'orderId'  => $order['id'],
            'email'    => $order['email'],
            'provider' => 'DaniGroup',
        ],
    ];

    $ch = curl_init(rtrim(YOCO_API_BASE_URL, '/') . '/checkouts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . YOCO_SECRET_KEY,
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false) {
        return ['ok' => false, 'error' => 'Could not reach Yoco: ' . $curlError];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => "Yoco error: HTTP $httpCode - $responseBody"];
    }

    $data = json_decode($responseBody, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Yoco returned an unexpected response.'];
    }

    $checkoutId  = $data['id'] ?? null;
    $redirectUrl = $data['redirectUrl'] ?? ($data['redirect_url'] ?? null);

    if (!$redirectUrl) {
        return ['ok' => false, 'error' => 'Yoco did not return a redirect URL. Check the Yoco API response format.'];
    }

    return ['ok' => true, 'id' => $checkoutId, 'redirect_url' => $redirectUrl];
}
