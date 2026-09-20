<?php

namespace App\Controllers;

use App\Models\DiscountCodeModel;
use App\Models\EventModel;
use CodeIgniter\HTTP\RedirectResponse;

class DiscountCodeAdminController extends BaseController
{
    private DiscountCodeModel $discountModel;

    public function __construct()
    {
        $this->discountModel = new DiscountCodeModel();
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $codes  = $this->discountModel->orderBy('created_at', 'DESC')->findAll();
        $events = (new EventModel())->orderBy('title', 'ASC')->findAll();

        return view('admin/discount_codes', [
            'codes'     => $codes,
            'events'    => $events,
            'pageTitle' => lang('App.discountCodesPageTitle'),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $code      = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $this->request->getPost('code'))) ?? '');
        $type      = trim((string) $this->request->getPost('type'));
        $value     = (float) $this->request->getPost('value');
        $maxUses   = $this->request->getPost('max_uses');
        $perUser   = $this->request->getPost('max_uses_per_user');
        $eventId   = (int) $this->request->getPost('event_id');
        $expires   = trim((string) $this->request->getPost('expires_at'));
        $starts    = trim((string) $this->request->getPost('starts_at'));
        $desc      = trim((string) $this->request->getPost('description'));
        $bulkCount = max(1, min(200, (int) $this->request->getPost('bulk_count')));

        if (! in_array($type, ['percent', 'fixed'], true) || $value <= 0 || ($type === 'percent' && $value > 100)) {
            return redirect()->back()->with('dc_error', lang('App.discountCodesInvalid'));
        }

        if ($bulkCount === 1 && $code === '') {
            return redirect()->back()->with('dc_error', lang('App.discountCodesInvalid'));
        }

        $shared = [
            'description'       => $desc !== '' ? $desc : null,
            'type'              => $type,
            'value'             => $value,
            'max_uses'          => $maxUses !== '' && $maxUses !== null ? (int) $maxUses : null,
            'max_uses_per_user' => $perUser !== '' && $perUser !== null ? max(1, (int) $perUser) : null,
            'event_id'          => $eventId > 0 ? $eventId : null,
            'expires_at'        => $expires !== '' ? str_replace('T', ' ', $expires) . ':00' : null,
            'starts_at'         => $starts !== '' ? str_replace('T', ' ', $starts) . ':00' : null,
            'is_active'         => 1,
        ];

        $created = [];

        if ($bulkCount === 1) {
            if ($this->discountModel->where('code', $code)->first() !== null) {
                return redirect()->back()->with('dc_error', lang('App.discountCodesExists'));
            }

            $this->discountModel->insert(['code' => $code] + $shared);
            $created[] = $code;
        } else {
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

            while (count($created) < $bulkCount) {
                $suffix = '';
                for ($i = 0; $i < 8; $i++) {
                    $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }

                $candidate = ($code !== '' ? $code . '-' : '') . $suffix;
                if ($this->discountModel->where('code', $candidate)->first() !== null) {
                    continue;
                }

                $this->discountModel->insert(['code' => $candidate] + $shared);
                $created[] = $candidate;
            }
        }

        $code = $created[0] . (count($created) > 1 ? ' (+' . (count($created) - 1) . ')' : '');
        $this->logAdminAction('discount_code_create', 'system', ['code' => $code]);

        return redirect()->to(base_url('admin/discount-codes'))->with('dc_info', strtr(lang('App.discountCodesCreatedN'), ['{n}' => (string) count($created)]))
            ->with('dc_new_codes', $created);
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $this->discountModel->delete($id);
        $this->logAdminAction('discount_code_delete', 'system', ['id' => $id]);

        return redirect()->to(base_url('admin/discount-codes'))->with('dc_info', lang('App.discountCodesDeleted'));
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
