<?php

namespace App\Libraries;

use DOMDocument;
use DOMXPath;
use Throwable;

/**
 * Editable About / GDPR / Terms / Privacy pages.
 *
 * Body text uses a small, safe markup (no HTML is ever stored or output raw):
 *   ## Heading        starts a new card
 *   blank line        new paragraph
 *   - item            list item
 *   **bold**          bold
 *   [text](https://…) link (http, https, mailto or a site path starting with /)
 */
class PageContent
{
    /** slug => [view, kicker language key or literal, page title language key] */
    public const PAGES = [
        'about'   => ['pages/about', 'App.footerAbout', 'App.aboutPageTitle'],
        'gdpr'    => ['pages/gdpr', 'GDPR', 'App.gdprPageTitle'],
        'terms'   => ['pages/terms', 'App.termsTitleShort', 'App.termsPageTitle'],
        'privacy' => ['pages/privacy', 'App.privacyTitleShort', 'App.privacyPageTitle'],
    ];

    public const LOCALES = ['el', 'en'];

    private function table(): \CodeIgniter\Database\BaseBuilder
    {
        return db_connect()->table(db_connect()->prefixTable('pages'));
    }

    public function find(string $slug, string $locale): ?array
    {
        try {
            return $this->table()->where('slug', $slug)->where('locale', $locale)->get()->getRowArray() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The saved page for the visitor's language. A language that was not edited keeps its built-in text,
     * so visitors never get the page in the other language.
     */
    public function forDisplay(string $slug, string $locale): ?array
    {
        return $this->find($slug, in_array($locale, self::LOCALES, true) ? $locale : self::LOCALES[0]);
    }

    public function save(string $slug, string $locale, string $title, string $subtitle, string $body, int $userId): void
    {
        $now  = date('Y-m-d H:i:s');
        $data = ['title' => $title, 'subtitle' => $subtitle, 'body' => $body, 'updated_by' => $userId, 'updated_at' => $now];

        if ($this->find($slug, $locale) === null) {
            $this->table()->insert($data + ['slug' => $slug, 'locale' => $locale, 'created_at' => $now]);
        } else {
            $this->table()->where('slug', $slug)->where('locale', $locale)->update($data);
        }
    }

    public function reset(string $slug, string $locale): void
    {
        $this->table()->where('slug', $slug)->where('locale', $locale)->delete();
    }

    /**
     * Latest edit date of the given pages (any language), or null when none was edited.
     */
    public function lastUpdated(array $slugs): ?string
    {
        try {
            $row = $this->table()->selectMax('updated_at', 'latest')->whereIn('slug', $slugs)->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return ! empty($row['latest']) ? (string) $row['latest'] : null;
    }

    public function kicker(string $slug): string
    {
        $kicker = self::PAGES[$slug][1];

        return str_starts_with($kicker, 'App.') ? lang($kicker) : $kicker;
    }

    /**
     * Splits the body into cards and renders each one to safe HTML.
     *
     * @return list<array{heading: string, html: string}>
     */
    public function render(string $body): array
    {
        $cards   = [];
        $current = null;

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            if (preg_match('/^##\s+(.+)$/', trim($line), $m) === 1) {
                if ($current !== null) {
                    $cards[] = $current;
                }
                $current = ['heading' => trim($m[1]), 'lines' => []];

                continue;
            }

            $current ??= ['heading' => '', 'lines' => []];
            $current['lines'][] = $line;
        }
        if ($current !== null) {
            $cards[] = $current;
        }

        return array_values(array_filter(array_map(fn (array $card): array => [
            'heading' => $card['heading'],
            'html'    => $this->renderBlocks($card['lines']),
        ], $cards), static fn (array $card): bool => $card['heading'] !== '' || $card['html'] !== ''));
    }

    /**
     * Built-in text of a page as editable markup (title, subtitle, body), read from its current view.
     *
     * @return array{title: string, subtitle: string, body: string}
     */
    public function defaults(string $slug, string $locale): array
    {
        $language = service('language');
        $previous = $language->getLocale();
        $language->setLocale($locale);

        try {
            $html = view(self::PAGES[$slug][0], ['pageTitle' => '']);
        } finally {
            $language->setLocale($previous);
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $text  = static fn ($node): string => trim(preg_replace('/\s+/u', ' ', (string) ($node?->textContent ?? '')) ?? '');

        $title    = $text($xpath->query('//section[contains(@class,"legal-hero")]//h1')->item(0));
        $subtitle = $text($xpath->query('//section[contains(@class,"legal-hero")]//p[contains(@class,"subtitle")]')->item(0));
        $blocks   = [];

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " legal-card ")]') as $card) {
            $lines = [];
            foreach ($card->childNodes as $child) {
                if ($child->nodeName === 'h2') {
                    $lines[] = '## ' . $text($child);
                } elseif ($child->nodeName === 'p') {
                    $lines[] = $text($child);
                } elseif ($child->nodeName === 'ul' || $child->nodeName === 'ol') {
                    foreach ($child->childNodes as $item) {
                        if ($item->nodeName === 'li') {
                            $lines[] = '- ' . $text($item);
                        }
                    }
                }
            }
            $blocks[] = implode("\n\n", array_filter($lines, static fn (string $l): bool => $l !== '' && $l !== '- '));
        }

        return ['title' => $title, 'subtitle' => $subtitle, 'body' => implode("\n\n", $blocks)];
    }

    /**
     * @param list<string> $lines
     */
    private function renderBlocks(array $lines): string
    {
        $html      = '';
        $paragraph = [];
        $list      = [];

        $flushParagraph = function () use (&$html, &$paragraph): void {
            if ($paragraph !== []) {
                $html .= '<p>' . implode('<br>', array_map([$this, 'inline'], $paragraph)) . '</p>';
                $paragraph = [];
            }
        };
        $flushList = function () use (&$html, &$list): void {
            if ($list !== []) {
                $html .= '<ul>' . implode('', array_map(fn (string $item): string => '<li>' . $this->inline($item) . '</li>', $list)) . '</ul>';
                $list = [];
            }
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $flushParagraph();
                $flushList();
            } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $m) === 1) {
                $flushParagraph();
                $list[] = $m[1];
            } else {
                $flushList();
                $paragraph[] = $line;
            }
        }
        $flushParagraph();
        $flushList();

        return $html;
    }

    /**
     * Escapes the text first, then adds bold and safe links.
     */
    private function inline(string $text): string
    {
        $out = esc($text);
        $out = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $out) ?? $out;

        return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function (array $m): string {
            $url = html_entity_decode($m[2], ENT_QUOTES);
            if (preg_match('#^(https?://|mailto:|/)#i', $url) !== 1 || str_starts_with($url, '//')) {
                return $m[0];
            }

            $external = preg_match('#^https?://#i', $url) === 1;

            return '<a href="' . esc($url, 'attr') . '"' . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $m[1] . '</a>';
        }, $out) ?? $out;
    }
}
