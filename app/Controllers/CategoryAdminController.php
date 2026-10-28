<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\HTTP\RedirectResponse;

class CategoryAdminController extends BaseController
{
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $editId = (int) $this->request->getGet('edit');

        return view('admin/categories', [
            'categories' => $this->categoryModel->orderBy('name', 'ASC')->findAll(),
            'editing'    => $editId > 0 ? $this->categoryModel->find($editId) : null,
            'pageTitle'  => lang('App.categoriesPageTitle'),
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $category = $this->categoryModel->find($id);
        if ($category === null) {
            return redirect()->to(base_url('admin/categories'));
        }

        $name  = trim((string) $this->request->getPost('name'));
        $color = trim((string) $this->request->getPost('color'));

        if ($name === '') {
            return redirect()->to(base_url('admin/categories?edit=' . $id))->with('cat_error', lang('App.categoriesNameRequired'));
        }

        // Category slugs are not used in URLs (filters use the id), so they follow the name.
        $this->categoryModel->update($id, [
            'name'  => $name,
            'slug'  => $this->makeSlug($name, $id),
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : ($category['color'] ?? '#14b8a6'),
        ]);

        $this->logAdminAction('category_update', 'system', ['id' => $id, 'from' => $category['name'], 'to' => $name]);

        return redirect()->to(base_url('admin/categories'))->with('cat_info', lang('App.categoriesUpdated'));
    }

    public function store(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $name  = trim((string) $this->request->getPost('name'));
        $color = trim((string) $this->request->getPost('color'));

        if ($name === '') {
            return redirect()->back()->with('cat_error', lang('App.categoriesNameRequired'));
        }

        $slug = $this->makeSlug($name);
        $this->categoryModel->insert([
            'name'  => $name,
            'slug'  => $slug,
            'color' => $color !== '' ? $color : '#14b8a6',
        ]);

        $this->logAdminAction('category_create', 'system', ['name' => $name]);

        return redirect()->to(base_url('admin/categories'))->with('cat_info', lang('App.categoriesCreated'));
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        db_connect()->table(db_connect()->prefixTable('events'))
            ->where('category_id', $id)
            ->set(['category_id' => null])
            ->update();

        $this->categoryModel->delete($id);
        $this->logAdminAction('category_delete', 'system', ['id' => $id]);

        return redirect()->to(base_url('admin/categories'))->with('cat_info', lang('App.categoriesDeleted'));
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }

    private function makeSlug(string $name, ?int $ignoreId = null): string
    {
        // Greek (and other scripts) are transliterated to Latin, as for event slugs.
        $value = trim($name);
        if (function_exists('transliterator_transliterate')) {
            $value = (string) (transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value) ?: $value);
        } elseif (function_exists('iconv')) {
            $value = (string) (iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value);
        }

        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)) ?? '', '-');
        $slug = trim(substr($slug, 0, 180), '-');
        $base = $slug !== '' ? $slug : 'category';
        $slug = $base;
        $i    = 2;

        while (($existing = $this->categoryModel->where('slug', $slug)->first()) !== null && (int) $existing['id'] !== $ignoreId) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
