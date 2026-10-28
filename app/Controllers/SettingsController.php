<?php

namespace App\Controllers;

use App\Libraries\SiteSettings;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Site settings: name, logo, favicon, accent colour, contact and company details.
 */
class SettingsController extends BaseController
{
    private const UPLOAD_DIR = 'assets/images/site';

    private const TEXT_FIELDS = [
        'site_name'       => 100,
        'contact_email'   => 191,
        'contact_phone'   => 50,
        'contact_address' => 255,
        'footer_text'     => 255,
        'org_name'        => 191,
        'org_tax_id'      => 50,
        'org_address'     => 255,
    ];

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        return view('admin/settings', [
            'settings'  => SiteSettings::all(),
            'pageTitle' => lang('App.settingsTitle'),
        ]);
    }

    public function save(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $back   = redirect()->to(base_url('admin/settings'))->withInput();
        $values = [];

        foreach (self::TEXT_FIELDS as $field => $max) {
            $values[$field] = mb_substr(trim((string) $this->request->getPost($field)), 0, $max);
        }

        if ($values['site_name'] === '') {
            return $back->with('settings_error', lang('App.settingsNameRequired'));
        }
        if ($values['contact_email'] !== '' && ! filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) {
            return $back->with('settings_error', lang('App.invalidEmail'));
        }

        $accent = strtolower(trim((string) $this->request->getPost('accent_color')));
        if (preg_match('/^#[0-9a-f]{6}$/', $accent) !== 1) {
            return $back->with('settings_error', lang('App.settingsColorInvalid'));
        }
        $values['accent_color'] = $accent;

        foreach (['logo_path' => ['png', 'jpg', 'jpeg', 'webp'], 'favicon_path' => ['png', 'ico']] as $field => $extensions) {
            $current = SiteSettings::get($field);

            if ($this->request->getPost('remove_' . $field) === '1') {
                $this->deleteUpload($current);
                $values[$field] = '';

                continue;
            }

            $file = $this->request->getFile($field);
            if ($file instanceof UploadedFile && $file->isValid() && ! $file->hasMoved()) {
                $stored = $this->storeImage($file, $extensions);
                if ($stored === null) {
                    return $back->with('settings_error', lang('App.settingsImageInvalid'));
                }
                $this->deleteUpload($current);
                $values[$field] = $stored;
            }
        }

        SiteSettings::save($values);
        $this->logAdminAction('settings_update', 'system', ['site_name' => $values['site_name']]);

        return redirect()->to(base_url('admin/settings'))->with('settings_info', lang('App.settingsSaved'));
    }

    /**
     * Saves an uploaded image under public/assets/images/site with a random name; returns its relative path.
     *
     * @param list<string> $extensions
     */
    private function storeImage(UploadedFile $file, array $extensions): ?string
    {
        $extension = strtolower((string) $file->getExtension());
        $mimes     = ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];

        if ($file->getSize() > 2 * 1024 * 1024 || ! in_array($extension, $extensions, true) || ! in_array($file->getMimeType(), $mimes, true)) {
            return null;
        }

        $directory = FCPATH . self::UPLOAD_DIR;
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $name = $file->getRandomName();
        $file->move($directory, $name, true);

        return self::UPLOAD_DIR . '/' . $name;
    }

    private function deleteUpload(string $path): void
    {
        // Only files this page uploaded are removed.
        if ($path !== '' && str_starts_with($path, self::UPLOAD_DIR . '/') && ! str_contains($path, '..')) {
            @unlink(FCPATH . $path);
        }
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
