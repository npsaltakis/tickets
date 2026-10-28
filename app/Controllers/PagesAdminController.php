<?php

namespace App\Controllers;

use App\Libraries\PageContent;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Pages: edit the About / GDPR / Terms / Privacy text per language.
 */
class PagesAdminController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $content = new PageContent();
        $rows    = [];
        foreach (array_keys(PageContent::PAGES) as $slug) {
            foreach (PageContent::LOCALES as $locale) {
                $rows[] = ['slug' => $slug, 'locale' => $locale, 'saved' => $content->find($slug, $locale)];
            }
        }

        return view('admin/pages', ['rows' => $rows, 'pageTitle' => lang('App.pagesAdminTitle')]);
    }

    public function edit(string $slug, string $locale): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }
        $this->assertPage($slug, $locale);

        $content = new PageContent();
        $saved   = $content->find($slug, $locale);

        return view('admin/page_edit', [
            'slug'      => $slug,
            'locale'    => $locale,
            'saved'     => $saved,
            'values'    => $saved ?? $content->defaults($slug, $locale),
            'pageTitle' => lang('App.pagesAdminTitle'),
        ]);
    }

    public function save(string $slug, string $locale): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }
        $this->assertPage($slug, $locale);

        $title    = mb_substr(trim((string) $this->request->getPost('title')), 0, 191);
        $subtitle = mb_substr(trim((string) $this->request->getPost('subtitle')), 0, 255);
        $body     = trim(str_replace("\r\n", "\n", (string) $this->request->getPost('body')));
        $back     = redirect()->to(base_url('admin/pages/' . $slug . '/' . $locale))->withInput();

        if ($title === '' || $body === '') {
            return $back->with('pages_error', lang('App.pagesRequired'));
        }

        (new PageContent())->save($slug, $locale, $title, $subtitle, $body, (int) session()->get('user_id'));
        $this->logAdminAction('page_update', 'system', ['slug' => $slug, 'locale' => $locale]);

        return redirect()->to(base_url('admin/pages/' . $slug . '/' . $locale))->with('pages_info', lang('App.pagesSaved'));
    }

    public function reset(string $slug, string $locale): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }
        $this->assertPage($slug, $locale);

        (new PageContent())->reset($slug, $locale);
        $this->logAdminAction('page_reset', 'system', ['slug' => $slug, 'locale' => $locale]);

        return redirect()->to(base_url('admin/pages'))->with('pages_info', lang('App.pagesResetDone'));
    }

    private function assertPage(string $slug, string $locale): void
    {
        if (! isset(PageContent::PAGES[$slug]) || ! in_array($locale, PageContent::LOCALES, true)) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
