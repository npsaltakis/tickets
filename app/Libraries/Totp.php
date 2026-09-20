<?php

namespace App\Libraries;

/**
 * Minimal RFC 6238 TOTP (SHA-1, 6 digits, 30 s) compatible with Google Authenticator, Authy, 1Password...
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, ?int $timestamp = null, int $period = 30): string
    {
        $counter = intdiv($timestamp ?? time(), $period);
        $hash    = hash_hmac('sha1', pack('J', $counter), self::base32Decode($secret), true);
        $offset  = ord($hash[19]) & 0x0F;
        $value   = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Accepts the current code and one step either side to tolerate clock drift.
     */
    public static function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6 || $secret === '') {
            return false;
        }

        $timestamp ??= time();

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $timestamp + ($i * 30)), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    public static function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    public static function base32Decode(string $base32): string
    {
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $base32) ?? '')) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
