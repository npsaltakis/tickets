<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeEventSlugs extends Migration
{
    public function up()
    {
        $events = $this->db->table('events')
            ->select('id, title, slug')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $usedSlugs = [];

        foreach ($events as $event) {
            $eventId = (int) ($event['id'] ?? 0);
            $currentSlug = (string) ($event['slug'] ?? '');
            $safeSlug = $this->normalizeSlug($currentSlug);

            if ($safeSlug === 'event' || $safeSlug !== $currentSlug) {
                $safeSlug = $this->normalizeSlug((string) ($event['title'] ?? 'event'));
            }

            $uniqueSlug = $this->makeUniqueSlug($safeSlug, $usedSlugs);
            $usedSlugs[] = $uniqueSlug;

            if ($eventId > 0 && $uniqueSlug !== $currentSlug) {
                $this->db->table('events')
                    ->where('id', $eventId)
                    ->update(['slug' => $uniqueSlug]);
            }
        }
    }

    public function down()
    {
        // Slug normalization is intentionally not reversible.
    }

    private function normalizeSlug(string $value): string
    {
        $value = trim($value);

        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
            if (is_string($transliterated)) {
                $value = $transliterated;
            }
        } elseif (function_exists('iconv')) {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($transliterated)) {
                $value = $transliterated;
            }
        }

        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 180);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'event';
    }

    private function makeUniqueSlug(string $baseSlug, array $usedSlugs): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (in_array($slug, $usedSlugs, true)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
