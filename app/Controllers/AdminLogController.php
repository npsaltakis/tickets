<?php

namespace App\Controllers;

use App\Models\AdminLogModel;
use CodeIgniter\HTTP\RedirectResponse;

class AdminLogController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $db = db_connect();
        if (! $db->tableExists('admin_logs')) {
            return view('admin_logs/index', [
                'pageTitle' => lang('App.adminLogsPageTitle'),
                'logs' => [],
                'tableReady' => false,
            ]);
        }

        $logs = (new AdminLogModel())
            ->orderBy('created_at', 'DESC')
            ->limit(250)
            ->findAll();

        foreach ($logs as &$log) {
            $context = json_decode((string) ($log['context'] ?? ''), true);
            $log['context_pretty'] = json_encode(
                is_array($context) ? $context : [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            );
        }
        unset($log);

        return view('admin_logs/index', [
            'pageTitle' => lang('App.adminLogsPageTitle'),
            'logs' => $logs,
            'tableReady' => true,
        ]);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
