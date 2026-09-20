<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('csv_safe')) {
    /**
     * Neutralises spreadsheet formula injection (=, +, -, @, tab, CR at the start of a cell).
     */
    function csv_safe(mixed $value): string
    {
        $text = (string) $value;

        if ($text !== '' && preg_match('/^[=+\-@	]/', $text) === 1 && ! is_numeric($text)) {
            return "'" . $text;
        }

        return $text;
    }
}
