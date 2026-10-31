<?php

namespace App\Controllers;

use App\Filters\CheckBlockedIp;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

/**
 * Admin → Security: blocked IPs, whitelisted IPs and the per-IP login attempt log
 * written by App\Filters\LogLoginAttempt.
 */
class SecurityAdminController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $db = db_connect();

        try {
            // Expired automatic blocks no longer apply, so they are cleared before listing.
            $db->table('blocked_ips')
                ->where('blocked_by', 'system')
                ->where('blocked_at <', date('Y-m-d H:i:s', strtotime('-' . CheckBlockedIp::BLOCK_TTL_HOURS . ' hours')))
                ->delete();

            $data = [
                'tableReady'     => true,
                'blockedIps'     => $db->table('blocked_ips')->orderBy('blocked_at', 'DESC')->get()->getResultArray(),
                'whitelistedIps' => $db->table('whitelisted_ips')->orderBy('added_at', 'DESC')->get()->getResultArray(),
                'attemptsByIp'   => $db->table('login_attempts')
                    ->select('ip_address, COUNT(*) AS total, SUM(success = 0) AS failed, SUM(success = 1) AS succeeded, MAX(attempted_at) AS last_attempt')
                    ->groupBy('ip_address')
                    ->orderBy('failed', 'DESC')
                    ->limit(30)
                    ->get()->getResultArray(),
                'recentAttempts' => $db->table('login_attempts')->orderBy('attempted_at', 'DESC')->limit(50)->get()->getResultArray(),
            ];
        } catch (Throwable) {
            $data = ['tableReady' => false, 'blockedIps' => [], 'whitelistedIps' => [], 'attemptsByIp' => [], 'recentAttempts' => []];
        }

        return view('admin/security', $data + [
            'currentIp' => $this->request->getIPAddress(),
            'pageTitle' => lang('App.securityTitle'),
        ]);
    }

    public function blockIp(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $ip     = trim((string) $this->request->getPost('ip_address'));
        $reason = trim((string) $this->request->getPost('reason'));

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->back('sec_error', lang('App.securityInvalidIp'));
        }

        $db = db_connect();
        if (CheckBlockedIp::isWhitelisted($ip)) {
            return $this->back('sec_error', lang('App.securityIpWhitelisted'));
        }

        // A manual block replaces an automatic one for the same IP (ip_address is unique).
        $db->table('blocked_ips')->where('ip_address', $ip)->where('blocked_by', 'system')->delete();
        if ($db->table('blocked_ips')->where('ip_address', $ip)->countAllResults() > 0) {
            return $this->back('sec_error', lang('App.securityIpAlreadyBlocked'));
        }

        $db->table('blocked_ips')->insert([
            'ip_address' => $ip,
            'reason'     => mb_substr($reason !== '' ? $reason : 'Manually blocked by admin', 0, 255),
            'blocked_by' => 'admin',
            'blocked_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAdminAction('ip_block', 'security', ['ip' => $ip, 'reason' => $reason]);

        return $this->back('sec_info', lang('App.securityIpBlocked'));
    }

    public function unblock(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $db  = db_connect();
        $row = $db->table('blocked_ips')->where('id', $id)->get()->getRowArray();
        $db->table('blocked_ips')->where('id', $id)->delete();

        $this->logAdminAction('ip_unblock', 'security', ['ip' => $row['ip_address'] ?? '']);

        return $this->back('sec_info', lang('App.securityIpUnblocked'));
    }

    public function addWhitelist(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $ip   = trim((string) $this->request->getPost('ip_address'));
        $note = trim((string) $this->request->getPost('note'));

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->back('sec_error', lang('App.securityInvalidIp'));
        }

        $db = db_connect();
        if (CheckBlockedIp::isWhitelisted($ip)) {
            return $this->back('sec_error', lang('App.securityIpAlreadyWhitelisted'));
        }

        $db->table('whitelisted_ips')->insert([
            'ip_address' => $ip,
            'note'       => mb_substr($note, 0, 255),
            'added_by'   => (string) (session()->get('user_email') ?: 'admin'),
            'added_at'   => date('Y-m-d H:i:s'),
        ]);
        $db->table('blocked_ips')->where('ip_address', $ip)->delete();

        $this->logAdminAction('ip_whitelist_add', 'security', ['ip' => $ip, 'note' => $note]);

        return $this->back('sec_info', lang('App.securityIpWhitelistAdded'));
    }

    public function removeWhitelist(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $db  = db_connect();
        $row = $db->table('whitelisted_ips')->where('id', $id)->get()->getRowArray();
        $db->table('whitelisted_ips')->where('id', $id)->delete();

        $this->logAdminAction('ip_whitelist_remove', 'security', ['ip' => $row['ip_address'] ?? '']);

        return $this->back('sec_info', lang('App.securityIpWhitelistRemoved'));
    }

    public function clearAttempts(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $days = max(1, (int) ($this->request->getPost('days') ?? 7));
        db_connect()->table('login_attempts')
            ->where('attempted_at <', date('Y-m-d H:i:s', strtotime('-' . $days . ' days')))
            ->delete();

        $this->logAdminAction('login_attempts_clear', 'security', ['older_than_days' => $days]);

        return $this->back('sec_info', strtr(lang('App.securityAttemptsCleared'), ['{days}' => (string) $days]));
    }

    private function back(string $key, string $message): RedirectResponse
    {
        return redirect()->to(base_url('admin/security'))->with($key, $message);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
