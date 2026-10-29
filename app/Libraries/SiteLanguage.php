<?php

namespace App\Libraries;

use CodeIgniter\Language\Language;

/**
 * Language lines may contain `%site%` and `%currency%`, replaced with the site name and the currency
 * symbol from Site settings, so neither is written into the translation files.
 */
class SiteLanguage extends Language
{
    public function getLine(string $line, array $args = [])
    {
        $output = parent::getLine($line, $args);

        if (is_string($output) && str_contains($output, '%site%')) {
            $output = str_replace('%site%', SiteSettings::get('site_name'), $output);
        }
        if (is_string($output) && str_contains($output, '%currency%')) {
            $output = str_replace('%currency%', trim(SiteSettings::currencySymbol()), $output);
        }

        return $output;
    }
}
