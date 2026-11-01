<?php

namespace App\Controllers;

use App\Models\FaqModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Help chat: the general questions and answers offered in the help chat, per language.
 */
class FaqAdminController extends BaseController
{
    private const LOCALES = ['el', 'en'];

    private FaqModel $faqModel;

    public function __construct()
    {
        $this->faqModel = new FaqModel();
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $editId = (int) $this->request->getGet('edit');

        return view('admin/faqs', [
            'faqs'      => $this->faqModel->orderBy('locale', 'ASC')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll(),
            'editing'   => $editId > 0 ? $this->faqModel->find($editId) : null,
            'locales'   => self::LOCALES,
            'pageTitle' => lang('App.faqAdminTitle'),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        return $this->persist(null);
    }

    public function update(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $faq = $this->faqModel->find($id);
        if ($faq === null) {
            return redirect()->to(base_url('admin/help-faqs'));
        }

        return $this->persist($faq);
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        if ($this->faqModel->find($id) !== null) {
            $this->faqModel->delete($id);
            $this->logAdminAction('faq_delete', 'system', ['id' => $id]);
        }

        return redirect()->to(base_url('admin/help-faqs'))->with('faq_info', lang('App.faqDeleted'));
    }

    private function persist(?array $faq): RedirectResponse
    {
        $backUrl = base_url('admin/help-faqs' . ($faq ? '?edit=' . (int) $faq['id'] : ''));
        $back    = static fn (string $error): RedirectResponse => redirect()->to($backUrl)->withInput()->with('faq_error', $error);

        $question = mb_substr(trim((string) $this->request->getPost('question')), 0, 191);
        $answer   = mb_substr(trim((string) $this->request->getPost('answer')), 0, 2000);
        $link     = mb_substr(trim((string) $this->request->getPost('link_url')), 0, 255);
        $locale   = (string) $this->request->getPost('locale');

        if ($question === '' || $answer === '') {
            return $back(lang('App.faqRequired'));
        }
        if ($link !== '' && preg_match('#^(https?://|/)#i', $link) !== 1) {
            return $back(lang('App.heroSlideLinkInvalid'));
        }

        $data = [
            'locale'     => in_array($locale, self::LOCALES, true) ? $locale : 'el',
            'question'   => $question,
            'answer'     => $answer,
            'link_url'   => $link,
            'link_label' => mb_substr(trim((string) $this->request->getPost('link_label')), 0, 60),
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ];

        if ($faq === null) {
            $this->faqModel->insert($data);
            $this->logAdminAction('faq_create', 'system', ['locale' => $data['locale']]);
            $message = lang('App.faqCreated');
        } else {
            $this->faqModel->update((int) $faq['id'], $data);
            $this->logAdminAction('faq_update', 'system', ['id' => (int) $faq['id']]);
            $message = lang('App.faqUpdated');
        }

        return redirect()->to(base_url('admin/help-faqs'))->with('faq_info', $message);
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
