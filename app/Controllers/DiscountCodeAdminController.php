<?php

namespace App\Controllers;

use App\Models\DiscountCodeModel;
use App\Models\EventModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

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

        $code     = strtoupper(trim((string) $this->request->getPost('code')));
        $type     = trim((string) $this->request->getPost('type'));
        $value    = (float) $this->request->getPost('value');
        $maxUses  = $this->request->getPost('max_uses');
        $eventId  = (int) $this->request->getPost('event_id');
        $expires  = trim((string) $this->request->getPost('expires_at'));
        $desc     = trim((string) $this->request->getPost('description'));

        if ($code === '' || ! in_array($type, ['percent', 'fixed'], true) || $value <= 0) {
            return redirect()->back()->with('dc_error', lang('App.discountCodesInvalid'));
        }

        if ($this->discountModel->where('code', $code)->first() !== null) {
            return redirect()->back()->with('dc_error', lang('App.discountCodesExists'));
        }

        $this->discountModel->insert([
            'code'        => $code,
            'description' => $desc !== '' ? $desc : null,
            'type'        => $type,
            'value'       => $value,
            'max_uses'    => $maxUses !== '' && $maxUses !== null ? (int) $maxUses : null,
            'event_id'    => $eventId > 0 ? $eventId : null,
            'expires_at'  => $expires !== '' ? $expires . ':00' : null,
            'is_active'   => 1,
        ]);

        $this->logAdminAction('discount_code_create', 'system', ['code' => $code]);

        return redirect()->to(base_url('admin/discount-codes'))->with('dc_info', lang('App.discountCodesCreated'));
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

    public function validateCode(): ResponseInterface
    {
        $code    = strtoupper(trim((string) $this->request->getPost('code')));
        $eventId = (int) $this->request->getPost('event_id');
        $amount  = (float) $this->request->getPost('amount');

        $discount = $this->discountModel->findValid($code, $eventId);
        if ($discount === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => lang('App.discountCodesNotFound')]);
        }

        $newAmount = $this->discountModel->applyDiscount($discount, $amount);

        return $this->response->setJSON([
            'valid'       => true,
            'code'        => $discount['code'],
            'type'        => $discount['type'],
            'value'       => (float) $discount['value'],
            'new_amount'  => $newAmount,
            'description' => $discount['description'] ?? '',
        ]);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
