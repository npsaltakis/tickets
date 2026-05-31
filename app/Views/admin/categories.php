<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper admin-categories-page">

    <?php if (session()->getFlashdata('cat_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('cat_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('cat_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('cat_error')) ?></p>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

        <div class="card">
            <?php if (empty($categories)): ?>
                <div class="empty"><?= esc(lang('App.categoriesEmpty')) ?></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.categoriesName')) ?></th>
                            <th><?= esc(lang('App.categoriesSlug')) ?></th>
                            <th><?= esc(lang('App.categoriesColor')) ?></th>
                            <th><?= esc(lang('App.adminEventsActions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= esc($cat['color'] ?? '#14b8a6') ?>;margin-right:8px;vertical-align:middle"></span>
                                    <?= esc($cat['name']) ?>
                                </td>
                                <td class="meta"><?= esc($cat['slug']) ?></td>
                                <td><code style="font-size:0.82rem;color:var(--muted)"><?= esc($cat['color'] ?? '') ?></code></td>
                                <td>
                                    <form method="post" action="<?= base_url('admin/categories/' . (int)$cat['id'] . '/delete') ?>" style="margin:0" onsubmit="return confirm('<?= esc(lang('App.categoriesDeleteConfirm'), 'attr') ?>')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="admin-action-btn admin-action-btn--danger"><?= esc(lang('App.eventDeleteButton')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="margin:0 0 16px;font-size:1rem;color:#f8fafc"><?= esc(lang('App.categoriesCreateTitle')) ?></h2>
            <form method="post" action="<?= base_url('admin/categories') ?>" class="auth-form">
                <?= csrf_field() ?>
                <label class="auth-label" for="cat_name"><?= esc(lang('App.categoriesName')) ?></label>
                <input id="cat_name" name="name" type="text" class="auth-input" required>

                <label class="auth-label" for="cat_color"><?= esc(lang('App.categoriesColor')) ?></label>
                <input id="cat_color" name="color" type="color" value="#14b8a6" style="width:100%;height:40px;border-radius:8px;border:1px solid var(--border);background:var(--surface);cursor:pointer;padding:2px">

                <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.categoriesCreate')) ?></button>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
