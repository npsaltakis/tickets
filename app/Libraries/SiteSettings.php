<?php

namespace App\Libraries;

use Throwable;

/**
 * Site settings stored in the `settings` table, so each client can brand the site from the admin
 * without code changes. Values are loaded once per request.
 */
class SiteSettings
{
    /**
     * Every setting with its default. Company details fall back to the older ORG_* .env keys.
     */
    public const DEFAULTS = [
        'site_name'       => 'Tickets',
        'logo_path'       => '',
        'favicon_path'    => '',
        'accent_color'    => '#14b8a6',
        'contact_email'   => '',
        'contact_phone'   => '',
        'contact_address' => '',
        'footer_text'     => '',
        'org_name'        => '',
        'org_tax_id'      => '',
        'org_address'     => '',
        'currency'        => 'EUR',
    ];

    /**
     * Currencies offered in the settings (all supported by PayPal with two decimals) and their symbols.
     */
    public const CURRENCIES = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'CHF' => 'CHF ',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'SEK' => 'SEK ',
        'NOK' => 'NOK ',
        'DKK' => 'DKK ',
        'PLN' => 'PLN ',
        'CZK' => 'CZK ',
    ];

    private static ?array $values = null;

    public static function get(string $key): string
    {
        $values = self::all();

        return (string) ($values[$key] ?? self::DEFAULTS[$key] ?? '');
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }

        $values = self::DEFAULTS;
        $values['org_name']    = trim((string) env('ORG_NAME', ''));
        $values['org_tax_id']  = trim((string) env('ORG_TAX_ID', ''));
        $values['org_address'] = trim((string) env('ORG_ADDRESS', ''));

        // Before installation there is no .env: do not open (and cache) a shared connection with empty credentials,
        // the installer still has to point it at the real database.
        if (! is_file(ROOTPATH . '.env')) {
            return self::$values = $values;
        }

        try {
            $db = db_connect();
            if ($db->tableExists('settings')) {
                foreach ($db->table($db->prefixTable('settings'))->get()->getResultArray() as $row) {
                    if (array_key_exists((string) $row['key'], self::DEFAULTS) && (string) $row['value'] !== '') {
                        $values[(string) $row['key']] = (string) $row['value'];
                    }
                }
            }
        } catch (Throwable) {
            // No database yet (e.g. during installation): defaults are used.
        }

        return self::$values = $values;
    }

    /**
     * @param array<string, string> $values
     */
    public static function save(array $values): void
    {
        $db    = db_connect();
        $table = $db->prefixTable('settings');
        $now   = date('Y-m-d H:i:s');

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            $db->table($table)->replace(['key' => $key, 'value' => trim((string) $value), 'updated_at' => $now]);
        }

        self::$values = null;
    }

    /**
     * ISO code of the site currency (used for prices, PayPal orders and reports).
     */
    public static function currency(): string
    {
        $code = self::get('currency');

        return array_key_exists($code, self::CURRENCIES) ? $code : 'EUR';
    }

    public static function currencySymbol(?string $code = null): string
    {
        $code ??= self::currency();

        return self::CURRENCIES[$code] ?? $code . ' ';
    }

    /**
     * An amount with the currency symbol, e.g. €12.50. Payments keep the currency they were made in.
     */
    public static function money(float $amount, ?string $code = null): string
    {
        return self::currencySymbol($code) . number_format($amount, 2);
    }

    /**
     * Public URL of an uploaded image setting (logo/favicon), or '' when none is set.
     */
    public static function assetUrl(string $key): string
    {
        $path = self::get($key);

        return $path === '' ? '' : base_url($path);
    }
}
