<?php

namespace App\Controllers;

use App\Libraries\SiteSettings;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Installable web app: manifest, home-screen icons built from the site logo, and the offline page
 * the service worker (public/sw.js) shows when a page cannot be loaded.
 */
class PwaController extends BaseController
{
    private const ICON_SIZES = [180, 192, 512];

    public function manifest(): ResponseInterface
    {
        $siteName = SiteSettings::get('site_name');
        $accent   = SiteSettings::get('accent_color');
        if (preg_match('/^#[0-9a-f]{6}$/i', $accent) !== 1) {
            $accent = SiteSettings::DEFAULTS['accent_color'];
        }

        $manifest = [
            'id'               => base_url('/'),
            'name'             => $siteName,
            'short_name'       => mb_substr($siteName, 0, 12, 'UTF-8'),
            'lang'             => service('request')->getLocale(),
            'start_url'        => base_url('/'),
            'scope'            => base_url('/'),
            'display'          => 'standalone',
            'background_color' => '#ffffff',
            'theme_color'      => $accent,
            'icons'            => [
                ['src' => base_url('pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => base_url('pwa/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => base_url('pwa/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts'        => [
                ['name' => lang('App.myEventsTitle'), 'url' => base_url('my-events')],
            ],
        ];

        return $this->response
            ->setContentType('application/manifest+json')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    public function icon(string $size): ResponseInterface
    {
        return $this->renderIcon((int) $size, false);
    }

    public function maskableIcon(string $size): ResponseInterface
    {
        return $this->renderIcon((int) $size, true);
    }

    public function offline(): string
    {
        return view('pwa/offline');
    }

    /**
     * The logo centred on a square. Maskable icons keep the logo inside the 80% safe zone,
     * because launchers crop them to a circle or rounded shape.
     */
    private function renderIcon(int $size, bool $maskable): ResponseInterface
    {
        if (! in_array($size, self::ICON_SIZES, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $logoFile = $this->logoFile();
        $cacheFile = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'pwa-icon-'
            . md5($logoFile . '|' . filemtime($logoFile) . '|' . $size . '|' . (int) $maskable) . '.png';

        if (! is_file($cacheFile)) {
            $logo = @imagecreatefromstring((string) file_get_contents($logoFile));
            if ($logo === false) {
                $logo = imagecreatefrompng(FCPATH . 'assets/images/logo.png');
            }

            // Background = the logo's top-left pixel, so a logo on a coloured square blends in; white when transparent.
            $corner = imagecolorsforindex($logo, imagecolorat($logo, 0, 0));
            $canvas = imagecreatetruecolor($size, $size);
            $background = $corner['alpha'] > 63
                ? imagecolorallocate($canvas, 255, 255, 255)
                : imagecolorallocate($canvas, $corner['red'], $corner['green'], $corner['blue']);
            imagefill($canvas, 0, 0, $background);

            $box   = (int) round($size * ($maskable ? 0.7 : 0.9));
            $scale = min($box / imagesx($logo), $box / imagesy($logo));
            $width  = (int) round(imagesx($logo) * $scale);
            $height = (int) round(imagesy($logo) * $scale);
            imagecopyresampled($canvas, $logo, intdiv($size - $width, 2), intdiv($size - $height, 2), 0, 0, $width, $height, imagesx($logo), imagesy($logo));

            imagepng($canvas, $cacheFile);
        }

        return $this->response
            ->setContentType('image/png')
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($cacheFile));
    }

    /**
     * The uploaded logo when it is a raster image GD can read, otherwise the bundled one.
     */
    private function logoFile(): string
    {
        $path = SiteSettings::get('logo_path');
        $file = $path !== '' ? FCPATH . ltrim($path, '/\\') : '';

        if ($file !== '' && is_file($file) && preg_match('/\.(png|jpe?g|gif|webp)$/i', $file) === 1) {
            return $file;
        }

        return FCPATH . 'assets/images/logo.png';
    }
}
