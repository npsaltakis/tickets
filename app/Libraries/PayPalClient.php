<?php

namespace App\Libraries;

use Throwable;

/**
 * Thin PayPal REST wrapper used by refunds and webhooks.
 */
class PayPalClient
{
    public function baseUrl(): string
    {
        return rtrim(trim((string) (env('paypal.baseUrl') ?: env('PAYPAL_BASE_URL', ''))), '/');
    }

    public function webhookId(): string
    {
        return trim((string) (env('paypal.webhookId') ?: env('PAYPAL_WEBHOOK_ID', '')));
    }

    private function verifySsl(): bool
    {
        return env('CI_ENVIRONMENT', 'production') === 'production';
    }

    public function accessToken(): ?string
    {
        $clientId = trim((string) (env('paypal.clientId') ?: env('paypal_clientId', '')));
        $secret   = trim((string) (env('paypal.secret') ?: env('paypal_secret', '')));
        $baseUrl  = $this->baseUrl();

        if ($clientId === '' || $secret === '' || $baseUrl === '') {
            return null;
        }

        try {
            $response = service('curlrequest')->post($baseUrl . '/v1/oauth2/token', [
                'headers'     => ['Authorization' => 'Basic ' . base64_encode($clientId . ':' . $secret)],
                'form_params' => ['grant_type' => 'client_credentials'],
                'http_errors' => false,
                'verify'      => $this->verifySsl(),
                'timeout'     => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal token request exception: {message}', ['message' => $exception->getMessage()]);

            return null;
        }

        $data  = json_decode($response->getBody(), true);
        $token = is_array($data) ? ($data['access_token'] ?? null) : null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Refunds a capture (fully when $amount is null).
     *
     * @return array{0: bool, 1: string|null, 2: string|null} [ok, refundId, error]
     */
    public function refundCapture(string $captureId, ?float $amount = null, string $currency = 'EUR', string $note = ''): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return [false, null, 'config'];
        }

        $payload = new \stdClass();
        if ($amount !== null) {
            $payload->amount = [
                'value'         => number_format($amount, 2, '.', ''),
                'currency_code' => $currency,
            ];
        }
        if ($note !== '') {
            $payload->note_to_payer = mb_substr($note, 0, 255);
        }

        try {
            $response = service('curlrequest')->post($this->baseUrl() . '/v2/payments/captures/' . urlencode($captureId) . '/refund', [
                'headers'     => [
                    'Authorization'     => 'Bearer ' . $token,
                    'Content-Type'      => 'application/json',
                    'PayPal-Request-Id' => 'refund-' . sha1($captureId . '|' . ($amount ?? 'full') . '|' . $note),
                ],
                'json'        => $payload,
                'http_errors' => false,
                'verify'      => $this->verifySsl(),
                'timeout'     => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal refund request failed: {message}', ['message' => $exception->getMessage()]);

            return [false, null, 'request'];
        }

        $data   = json_decode($response->getBody(), true);
        $id     = is_array($data) ? (string) ($data['id'] ?? '') : '';
        $status = is_array($data) ? (string) ($data['status'] ?? '') : '';

        if ($id !== '' && in_array($status, ['COMPLETED', 'PENDING'], true)) {
            return [true, $id, null];
        }

        log_message('error', 'PayPal refund failed [{status}]: {body}', [
            'status' => $response->getStatusCode(),
            'body'   => $response->getBody(),
        ]);

        return [false, null, 'rejected'];
    }

    /**
     * Verifies a webhook delivery using PayPal's verify-webhook-signature API.
     *
     * @param array<string, string> $headers lower-cased header name => value
     */
    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        $webhookId = $this->webhookId();
        $event     = json_decode($rawBody, true);
        $token     = $this->accessToken();

        if ($webhookId === '' || $token === null || ! is_array($event)) {
            return false;
        }

        try {
            $response = service('curlrequest')->post($this->baseUrl() . '/v1/notifications/verify-webhook-signature', [
                'headers'     => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json'        => [
                    'auth_algo'         => $headers['paypal-auth-algo'] ?? '',
                    'cert_url'          => $headers['paypal-cert-url'] ?? '',
                    'transmission_id'   => $headers['paypal-transmission-id'] ?? '',
                    'transmission_sig'  => $headers['paypal-transmission-sig'] ?? '',
                    'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                    'webhook_id'        => $webhookId,
                    'webhook_event'     => $event,
                ],
                'http_errors' => false,
                'verify'      => $this->verifySsl(),
                'timeout'     => 20,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'PayPal webhook verification failed: {message}', ['message' => $exception->getMessage()]);

            return false;
        }

        $data = json_decode($response->getBody(), true);

        return is_array($data) && ($data['verification_status'] ?? '') === 'SUCCESS';
    }
}
