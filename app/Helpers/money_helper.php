<?php

use App\Libraries\SiteSettings;

if (! function_exists('money')) {
    /**
     * An amount with the currency symbol, e.g. €12.50 (site currency unless a payment's own code is given).
     */
    function money(float|int|string|null $amount, ?string $currency = null): string
    {
        return SiteSettings::money((float) $amount, $currency !== null && $currency !== '' ? $currency : null);
    }
}
