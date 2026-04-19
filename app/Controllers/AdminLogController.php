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
                'actions' => [],
                'filters' => [],
                'stats' => [],
                'tableReady' => false,
            ]);
        }

        $model = new AdminLogModel();
        $actionFilter = trim((string) $this->request->getGet('action'));
        $targetFilter = trim((string) $this->request->getGet('target'));
        $query = trim((string) $this->request->getGet('q'));
        $actions = $model
            ->select('action')
            ->groupBy('action')
            ->orderBy('action', 'ASC')
            ->findAll();
        $actionOptions = array_values(array_filter(array_map(static fn (array $row): string => (string) ($row['action'] ?? ''), $actions)));
        $builder = $model->builder();
        $builder->orderBy('created_at', 'DESC')->limit(250);

        if ($actionFilter !== '') {
            $builder->where('action', $actionFilter);
        }

        if ($targetFilter !== '') {
            $builder->where('target_type', $targetFilter);
        }

        if ($query !== '') {
            $builder
                ->groupStart()
                ->like('admin_email', $query)
                ->orLike('ip_address', $query)
                ->orLike('action', $query)
                ->orLike('target_type', $query)
                ->orLike('context', $query)
                ->groupEnd();
        }

        $logs = $builder->get()->getResultArray();
        $todayLogs = $model
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->countAllResults();
        $totalLogs = $model->countAllResults();
        $uniqueAdminRows = $model
            ->select('admin_email')
            ->where('admin_email IS NOT NULL', null, false)
            ->groupBy('admin_email')
            ->findAll();
        $latestLog = $model
            ->select('created_at')
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->first();

        foreach ($logs as &$log) {
            $context = json_decode((string) ($log['context'] ?? ''), true);
            $context = is_array($context) ? $context : [];
            $log['context_items'] = $this->formatContextItems($context);
            $log['action_label'] = $this->formatActionLabel((string) ($log['action'] ?? ''));
            $log['action_class'] = $this->formatActionClass((string) ($log['action'] ?? ''));
            $log['created_at_label'] = ! empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime((string) $log['created_at'])) : '-';
        }
        unset($log);

        return view('admin_logs/index', [
            'pageTitle' => lang('App.adminLogsPageTitle'),
            'logs' => $logs,
            'actions' => $actionOptions,
            'filters' => [
                'action' => $actionFilter,
                'target' => $targetFilter,
                'q' => $query,
            ],
            'stats' => [
                'total' => $totalLogs,
                'today' => $todayLogs,
                'admins' => count($uniqueAdminRows),
                'latest' => ! empty($latestLog['created_at']) ? date('d/m/Y H:i', strtotime((string) $latestLog['created_at'])) : '-',
            ],
            'tableReady' => true,
        ]);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }

    private function formatContextItems(array $context): array
    {
        $items = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $items[] = [
                'label' => ucwords(str_replace('_', ' ', (string) $key)),
                'value' => is_scalar($value) ? (string) $value : '-',
            ];
        }

        return $items;
    }

    private function formatActionLabel(string $action): string
    {
        $key = 'App.adminLogAction' . str_replace(' ', '', ucwords(str_replace('_', ' ', $action)));
        $label = lang($key);

        if ($label !== $key) {
            return $label;
        }

        return ucwords(str_replace('_', ' ', $action));
    }

    private function formatActionClass(string $action): string
    {
        if (str_contains($action, 'delete') || str_contains($action, 'block')) {
            return 'danger';
        }

        if (str_contains($action, 'check_in')) {
            return 'success';
        }

        if (str_contains($action, 'duplicate') || str_contains($action, 'update')) {
            return 'info';
        }

        return 'neutral';
    }
}
