<?php

namespace App\Controllers;

use App\Libraries\Updater;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Admin → Updates: installs new versions published as GitHub Releases.
 */
class UpdateController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $updater = new Updater();
        $current = $updater->currentVersion();
        $latest  = $updater->latest($this->request->getGet('refresh') === '1');

        return view('admin/update', [
            'current'           => $current,
            'latest'            => $latest,
            'hasUpdate'         => $latest !== null && Updater::isNewer($latest['tag'], $current),
            'isGitCheckout'     => $updater->isGitCheckout(),
            'pendingMigrations' => $this->pendingMigrations(),
            'pageTitle'         => lang('App.updaterTitle'),
        ]);
    }

    public function install(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $back    = redirect()->to(base_url('admin/update'));
        $updater = new Updater();
        $current = $updater->currentVersion();
        $latest  = $updater->latest(true);

        if ($latest === null) {
            return $back->with('update_error', lang('App.updaterUnreachable'));
        }
        if (! Updater::isNewer($latest['tag'], $current)) {
            return $back->with('update_info', lang('App.updaterUpToDate'));
        }

        $this->logAdminAction('admin_update_start', 'system', ['from' => $current, 'to' => $latest['tag']]);

        try {
            $backup = $updater->install($latest);
        } catch (Throwable $exception) {
            log_message('error', 'Update to {tag} failed: {message}', ['tag' => $latest['tag'], 'message' => $exception->getMessage()]);

            return $back->with('update_error', lang('App.updaterFailed') . ' ' . $exception->getMessage());
        }

        $this->logAdminAction('admin_update_done', 'system', ['from' => $current, 'to' => $latest['tag'], 'backup' => basename($backup)]);

        return $back->with('update_info', strtr(lang('App.updaterDone'), ['{version}' => $latest['tag'], '{backup}' => 'writable/backups/' . basename($backup)]));
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
