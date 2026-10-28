<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper">
    <?php if (session()->getFlashdata('pages_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('pages_info')) ?></p>
    <?php endif; ?>

    <div class="card" style="overflow:auto">
        <h2 style="margin:0 0 4px;color:#f8fafc;font-size:1.1rem"><?= esc(lang('App.pagesAdminTitle')) ?></h2>
        <p class="meta" style="margin:0 0 16px"><?= esc(lang('App.pagesAdminHint')) ?></p>
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= esc(lang('App.pagesColPage')) ?></th>
                    <th><?= esc(lang('App.pagesColLanguage')) ?></th>
                    <th><?= esc(lang('App.status')) ?></th>
                    <th><?= esc(lang('App.pagesColUpdated')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc(lang('App.pagesName' . ucfirst($row['slug']))) ?></td>
                        <td><?= esc(strtoupper($row['locale'])) ?></td>
                        <td>
                            <span class="status <?= $row['saved'] ? 'active' : 'inactive' ?>"><?= esc(lang($row['saved'] ? 'App.pagesEdited' : 'App.pagesDefault')) ?></span>
                        </td>
                        <td class="meta"><?= $row['saved'] ? esc(date('d/m/Y H:i', strtotime((string) $row['saved']['updated_at']))) : '—' ?></td>
                        <td style="display:flex;gap:8px;flex-wrap:wrap">
                            <a class="admin-action-btn" href="<?= base_url('admin/pages/' . $row['slug'] . '/' . $row['locale']) ?>"><?= esc(lang('App.categoriesEdit')) ?></a>
                            <a class="admin-action-btn" href="<?= base_url($row['slug'] === 'privacy' ? 'privacy-policy' : $row['slug']) . '?lang=' . $row['locale'] ?>" target="_blank" rel="noopener"><?= esc(lang('App.pagesView')) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
