<?php

namespace App\Libraries;

use Throwable;

/**
 * "Save to Google Wallet" links for event tickets.
 *
 * The link carries a JWT signed (RS256) with the service account key. It holds the event's ticket class
 * and the ticket object (with the QR code), so Google creates them on first save and no API calls are needed.
 *
 * .env: `googleWallet.issuerId` and `googleWallet.keyFile` (path of the service account JSON key,
 * relative to the project root and outside public/).
 */
class GoogleWallet
{
    private const SAVE_URL = 'https://pay.google.com/gp/v/save/';

    private ?array $key = null;

    public function isConfigured(): bool
    {
        return $this->issuerId() !== '' && $this->serviceAccount() !== null;
    }

    /**
     * @param array<string, mixed> $ticket ticket_code, first_name, last_name, event_id, title, location, address, start_date, end_date
     */
    public function saveUrl(array $ticket): ?string
    {
        $account = $this->serviceAccount();
        if ($account === null) {
            return null;
        }

        $issuer  = $this->issuerId();
        $code    = (string) $ticket['ticket_code'];
        $classId = $issuer . '.event_' . (int) $ticket['event_id'];

        $class = [
            'id'           => $classId,
            'issuerName'   => lang('App.siteTitle'),
            'reviewStatus' => 'UNDER_REVIEW',
            'eventName'    => $this->text((string) $ticket['title']),
            'venue'        => [
                'name'    => $this->text((string) ($ticket['location'] ?? '') ?: lang('App.siteTitle')),
                'address' => $this->text((string) ($ticket['address'] ?? '') ?: '-'),
            ],
            'logo'               => ['sourceUri' => ['uri' => $this->logoUrl()]],
            'hexBackgroundColor' => '#0b1220',
            'homepageUri'        => ['uri' => base_url('events/' . ($ticket['slug'] ?? '')), 'description' => lang('App.siteTitle')],
        ];

        $dateTime = array_filter([
            'start' => $this->isoDate($ticket['start_date'] ?? null),
            'end'   => $this->isoDate($ticket['end_date'] ?? null),
        ]);
        if ($dateTime !== []) {
            $class['dateTime'] = $dateTime;
        }

        $object = [
            // Object ids allow only letters, digits, '.', '_' and '-'.
            'id'               => $issuer . '.ticket_' . preg_replace('/[^A-Za-z0-9]/', '', $code),
            'classId'          => $classId,
            'state'            => 'ACTIVE',
            'ticketNumber'     => $code,
            'ticketHolderName' => trim((string) ($ticket['first_name'] ?? '') . ' ' . (string) ($ticket['last_name'] ?? '')),
            'barcode'          => [
                'type'          => 'QR_CODE',
                'value'         => $code,
                'alternateText' => $code,
            ],
        ];

        $claims = [
            'iss'     => $account['client_email'],
            'aud'     => 'google',
            'typ'     => 'savetowallet',
            'iat'     => time(),
            'origins' => [rtrim(base_url('/'), '/')],
            'payload' => [
                'eventTicketClasses' => [$class],
                'eventTicketObjects' => [$object],
            ],
        ];

        $jwt = $this->sign($claims, (string) $account['private_key']);

        return $jwt === null ? null : self::SAVE_URL . $jwt;
    }

    private function sign(array $claims, string $privateKey): ?string
    {
        $encode = static fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $header  = $encode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $encode((string) json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $signature = '';

        if (! openssl_sign($header . '.' . $payload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            log_message('error', 'Google Wallet: could not sign the JWT (check the service account key).');

            return null;
        }

        return $header . '.' . $payload . '.' . $encode($signature);
    }

    /**
     * @return array{client_email: string, private_key: string}|null
     */
    private function serviceAccount(): ?array
    {
        if ($this->key !== null) {
            return $this->key;
        }

        $file = trim((string) env('googleWallet.keyFile', ''));
        if ($file === '') {
            return null;
        }

        $path = str_starts_with($file, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $file) === 1 ? $file : ROOTPATH . ltrim($file, '/\\');

        try {
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        } catch (Throwable) {
            $data = null;
        }

        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            log_message('error', 'Google Wallet: key file missing or invalid at {path}.', ['path' => $file]);

            return null;
        }

        return $this->key = ['client_email' => (string) $data['client_email'], 'private_key' => (string) $data['private_key']];
    }

    /**
     * Public URL of a square PNG logo; `googleWallet.logoUrl` in .env, or assets/images/logo.png.
     */
    private function logoUrl(): string
    {
        $url = trim((string) env('googleWallet.logoUrl', ''));
        if ($url === '') {
            $url = SiteSettings::assetUrl('logo_path');
        }

        return $url !== '' ? $url : base_url('assets/images/logo.png');
    }

    private function issuerId(): string
    {
        return preg_replace('/\D/', '', (string) env('googleWallet.issuerId', '')) ?? '';
    }

    /**
     * @return array{defaultValue: array{language: string, value: string}}
     */
    private function text(string $value): array
    {
        return ['defaultValue' => ['language' => 'el', 'value' => $value]];
    }

    private function isoDate(mixed $value): ?string
    {
        $time = $value ? strtotime((string) $value) : false;

        return $time === false ? null : date('c', $time);
    }
}
