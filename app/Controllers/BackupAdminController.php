<?php

namespace App\Controllers;

use App\Libraries\AdminNotifications;
use App\Libraries\Backups;
use App\Libraries\SiteSettings;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Admin → Backups: list, create, download, delete and restore backups (App\Libraries\Backups).
 * A restore asks for a typed confirmation word and saves the current database first.
 */
class BackupAdminController extends BaseController
{
    private Backups $backups;

    public function __construct()
    {
        $this->backups = new Backups();
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        return view('admin/backups', [
            'backups'     => $this->backups->all(),
            'keepDays'    => Backups::keepDays(),
            'uploadLimit' => $this->uploadLimit(),
            'pageTitle'   => lang('App.backupTitle'),
        ]);
    }

    public function create(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        @set_time_limit(0);
        $full = $this->request->getPost('type') === 'full';

        try {
            $name = $full ? $this->backups->createFull() : $this->backups->createDatabase();
        } catch (Throwable $exception) {
            log_message('error', 'Backup failed: {message}', ['message' => $exception->getMessage()]);
            AdminNotifications::add('backup_failed', ['error' => $exception->getMessage()], 'admin/backups');

            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupFailed') . ' ' . $exception->getMessage());
        }

        $this->backups->prune(Backups::keepDays());
        $this->logAdminAction('backup_create', 'system', ['file' => $name]);
        AdminNotifications::add('backup_created', ['file' => $name, 'name' => (string) session()->get('user_name')], 'admin/backups');

        return redirect()->to(base_url('admin/backups'))->with('backup_info', strtr(lang('App.backupCreated'), ['{file}' => $name]));
    }

    public function download(string $name): DownloadResponse|RedirectResponse
    {
        $path = $this->isAdmin() ? $this->backups->path($name) : null;
        if ($path === null) {
            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupNotFound'));
        }

        $this->logAdminAction('backup_download', 'system', ['file' => $name]);

        return $this->response->download($path, null)->setFileName($name);
    }

    public function delete(string $name): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        if (! $this->backups->delete($name)) {
            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupNotFound'));
        }

        $this->logAdminAction('backup_delete', 'system', ['file' => $name]);

        return redirect()->to(base_url('admin/backups'))->with('backup_info', lang('App.backupDeleted'));
    }

    public function restore(string $name): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        if (! $this->confirmed()) {
            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupConfirmMismatch'));
        }

        return $this->runRestore($name);
    }

    /**
     * A backup from another server (or downloaded earlier): kept as uploaded-<time> and restored.
     */
    public function upload(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        if (! $this->confirmed()) {
            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupConfirmMismatch'));
        }

        $file = $this->request->getFile('backup_file');
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupUploadFailed') . ($file !== null ? ' ' . $file->getErrorString() : ''));
        }

        try {
            $name = $this->backups->storeUpload($file->getTempName(), $file->getClientName());
        } catch (Throwable $exception) {
            return redirect()->to(base_url('admin/backups'))->with('backup_error', $exception->getMessage());
        }

        return $this->runRestore($name);
    }

    public function settings(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $days = (int) $this->request->getPost('keep_days');
        SiteSettings::save(['backup_keep_days' => (string) max(1, min(365, $days))]);
        $this->logAdminAction('backup_settings', 'system', ['keep_days' => $days]);

        return redirect()->to(base_url('admin/backups'))->with('backup_info', lang('App.backupSettingsSaved'));
    }

    private function runRestore(string $name): RedirectResponse
    {
        @set_time_limit(0);

        try {
            $safety = $this->backups->restore($name);
        } catch (Throwable $exception) {
            log_message('error', 'Restore of {file} failed: {message}', ['file' => $name, 'message' => $exception->getMessage()]);
            AdminNotifications::add('restore_failed', ['file' => $name, 'error' => $exception->getMessage()], 'admin/backups');

            return redirect()->to(base_url('admin/backups'))->with('backup_error', lang('App.backupRestoreFailed') . ' ' . $exception->getMessage());
        }

        // Recorded in the restored database, so it shows up after the restore.
        $this->logAdminAction('backup_restore', 'system', ['file' => $name, 'safety_copy' => $safety]);
        AdminNotifications::add('backup_restored', ['file' => $name, 'name' => (string) session()->get('user_name')], 'admin/backups');

        return redirect()->to(base_url('admin/backups'))->with('backup_info', strtr(lang('App.backupRestored'), ['{file}' => $name, '{safety}' => $safety]));
    }

    /**
     * The admin typed the confirmation word (in either language) before a restore.
     */
    private function confirmed(): bool
    {
        $typed = mb_strtoupper(trim((string) $this->request->getPost('confirm_word')));

        return in_array($typed, ['ΕΠΑΝΑΦΟΡΑ', 'RESTORE'], true);
    }

    /**
     * The largest backup PHP accepts as an upload (the smaller of upload_max_filesize and post_max_size), e.g. "64 MB".
     */
    private function uploadLimit(): string
    {
        $toBytes = static function (string $value): int {
            $number = (int) $value;

            return match (strtoupper(substr(trim($value), -1))) {
                'G'     => $number * 1073741824,
                'M'     => $number * 1048576,
                'K'     => $number * 1024,
                default => $number,
            };
        };

        $limits = array_filter([$toBytes((string) ini_get('upload_max_filesize')), $toBytes((string) ini_get('post_max_size'))]);

        return $limits === [] ? '' : round(min($limits) / 1048576) . ' MB';
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
