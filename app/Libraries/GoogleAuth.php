<?php

namespace App\Libraries;

use Throwable;

/**
 * "Sign in with Google" using the OAuth 2.0 authorization-code flow.
 * The code is exchanged server-side and the profile is read from Google's userinfo endpoint over TLS,
 * so no ID-token signature checking is needed. Needs `google.clientId` and `google.clientSecret` in .env.
 */
class GoogleAuth
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function redirectUri(): string
    {
        return base_url('auth/google/callback');
    }

    public function authorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => $this->clientId(),
            'redirect_uri'  => $this->redirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);
    }

    /**
     * Exchanges the authorization code and returns the Google profile, or null on any failure.
     *
     * @return array{sub: string, email: string, email_verified: bool, given_name: string, family_name: string}|null
     */
    public function fetchProfile(string $code): ?array
    {
        try {
            // Not the shared instance: it would carry headers over between requests.
            $client  = \Config\Services::curlrequest([], null, null, false);
            $options = ['timeout' => 10, 'http_errors' => false];

            $tokenResponse = $client->post(self::TOKEN_URL, $options + [
                'form_params' => [
                    'code'          => $code,
                    'client_id'     => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'redirect_uri'  => $this->redirectUri(),
                    'grant_type'    => 'authorization_code',
                ],
            ]);
            $token = json_decode((string) $tokenResponse->getBody(), true);
            if ($tokenResponse->getStatusCode() !== 200 || ! is_array($token) || empty($token['access_token'])) {
                log_message('error', 'Google token exchange failed: {status} {body}', [
                    'status' => $tokenResponse->getStatusCode(),
                    'body'   => mb_substr((string) $tokenResponse->getBody(), 0, 300),
                ]);

                return null;
            }

            $profileResponse = $client->get(self::USERINFO_URL, $options + [
                'headers' => ['Authorization' => 'Bearer ' . $token['access_token']],
            ]);
            $profile = json_decode((string) $profileResponse->getBody(), true);
            if ($profileResponse->getStatusCode() !== 200 || ! is_array($profile) || empty($profile['sub']) || empty($profile['email'])) {
                log_message('error', 'Google userinfo failed: {status}', ['status' => $profileResponse->getStatusCode()]);

                return null;
            }
        } catch (Throwable $exception) {
            log_message('error', 'Google sign-in error: {message}', ['message' => $exception->getMessage()]);

            return null;
        }

        return [
            'sub'            => (string) $profile['sub'],
            'email'          => strtolower(trim((string) $profile['email'])),
            'email_verified' => ($profile['email_verified'] ?? false) === true,
            'given_name'     => trim((string) ($profile['given_name'] ?? '')),
            'family_name'    => trim((string) ($profile['family_name'] ?? '')),
        ];
    }

    private function clientId(): string
    {
        return trim((string) env('google.clientId', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) env('google.clientSecret', ''));
    }
}
