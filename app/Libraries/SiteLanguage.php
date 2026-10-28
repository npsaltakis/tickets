<?php

namespace App\Libraries;

use CodeIgniter\Language\Language;

/**
 * Language lines may contain `%site%`, replaced with the site name from Site settings,
 * so the brand is never written into the translation files.
 */
class SiteLanguage extends Language
{
    public function getLine(string $line, array $args = [])
    {
        $output = parent::getLine($line, $args);

        if (is_string($output) && str_contains($output, '%site%')) {
            return str_replace('%site%', SiteSettings::get('site_name'), $output);
        }

        return $output;
    }
}
