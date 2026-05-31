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

        return view('admin/categories', [
            'categories' => $this->categoryModel->orderBy('name', 'ASC')->findAll(),
            'pageTitle'  => lang('App.categoriesPageTitle'),
        ]);
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

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $name) ?? $name, '-'));
        $base = $slug;
        $i    = 1;
        while ($this->categoryModel->where('slug', $slug)->first() !== null) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
