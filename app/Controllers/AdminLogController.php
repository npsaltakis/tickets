<?php

namespace App\Controllers;

use App\Models\AdminLogModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class AdminLogController extends BaseController
{
    public function index(): string|RedirectResponse|ResponseInterface
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
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 25;
        $actions = $model
            ->select('action')
            ->groupBy('action')
            ->orderBy('action', 'ASC')
            ->findAll();
        $actionOptions = array_values(array_filter(array_map(static fn (array $row): string => (string) ($row['action'] ?? ''), $actions)));
        $builder = $this->buildFilteredLogBuilder($model, $actionFilter, $targetFilter, $query);
        $totalFiltered = (clone $builder)->countAllResults();
        $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
        $page = min($page, $totalPages);

        if ((string) $this->request->getGet('export') === 'csv') {
            $exportRows = $this->buildFilteredLogBuilder($model, $actionFilter, $targetFilter, $query)
                ->orderBy('created_at', 'DESC')
                ->limit(5000)
                ->get()
                ->getResultArray();

            return $this->exportCsv($exportRows);
        }

        $logs = $builder
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();
        $todayLogs = (new AdminLogModel())
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->countAllResults();
        $totalLogs = (new AdminLogModel())->countAllResults();
        $uniqueAdminRows = (new AdminLogModel())
            ->select('admin_email')
            ->where('admin_email IS NOT NULL', null, false)
            ->groupBy('admin_email')
            ->findAll();
        $latestLog = (new AdminLogModel())
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
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalFiltered,
                'totalPages' => $totalPages,
                'previousUrl' => $page > 1 ? $this->buildLogPageUrl($page - 1) : '',
                'nextUrl' => $page < $totalPages ? $this->buildLogPageUrl($page + 1) : '',
                'exportUrl' => $this->buildLogPageUrl($page, ['export' => 'csv']),
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

    public function clear(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $db = db_connect();
        if (! $db->tableExists('admin_logs')) {
            return redirect()->to(base_url('admin-logs'))->with('admin_logs_error', lang('App.adminLogsMigrationRequired'));
        }

        $deletedRows = (new AdminLogModel())->countAllResults();
        $db->table('admin_logs')->truncate();

        log_message('warning', 'admin_audit_logs_cleared {payload}', [
            'payload' => json_encode([
                'admin_id' => (int) (session()->get('user_id') ?? 0),
                'admin_email' => (string) (session()->get('user_email') ?? ''),
                'ip' => method_exists($this->request, 'getIPAddress') ? (string) $this->request->getIPAddress() : '',
                'deleted_rows' => $deletedRows,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return redirect()->to(base_url('admin-logs'))->with('admin_logs_info', strtr(lang('App.adminLogsClearSuccess'), [
            '{count}' => (string) $deletedRows,
        ]));
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }

    private function buildFilteredLogBuilder(AdminLogModel $model, string $actionFilter, string $targetFilter, string $query)
    {
        $builder = $model->builder();

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

        return $builder;
    }

    private function buildLogPageUrl(int $page, array $extra = []): string
    {
        $params = array_filter([
            'q' => trim((string) $this->request->getGet('q')),
            'action' => trim((string) $this->request->getGet('action')),
            'target' => trim((string) $this->request->getGet('target')),
            'page' => $page > 1 ? (string) $page : '',
        ], static fn (string $value): bool => $value !== '');

        $params = array_merge($params, $extra);

        return base_url('admin-logs') . ($params !== [] ? '?' . http_build_query($params) : '');
    }

    private function exportCsv(array $rows): ResponseInterface
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $this->response->setStatusCode(500)->setBody('Unable to export audit logs.');
        }

        fputcsv($handle, [
            lang('App.adminLogsCreatedAt'),
            lang('App.adminLogsAdmin'),
            lang('App.adminLogsAction'),
            lang('App.adminLogsTarget'),
            lang('App.adminLogsIp'),
            lang('App.adminLogsContext'),
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                (string) ($row['created_at'] ?? ''),
                (string) ($row['admin_email'] ?? ''),
                $this->formatActionLabel((string) ($row['action'] ?? '')),
                (string) ($row['target_type'] ?? ''),
                (string) ($row['ip_address'] ?? ''),
                (string) ($row['context'] ?? ''),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="audit-logs-' . date('Ymd-His') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . (string) $csv);
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
