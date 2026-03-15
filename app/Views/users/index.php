<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper users-page">
    <div class="events-header users-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.navUsers')) ?></span>
            <h1><?= esc(lang('App.usersTitle')) ?></h1>
            <p class="subtitle"><?= esc(lang('App.usersSubtitle')) ?></p>
        </div>
        <a href="<?= base_url('users/create') ?>" class="admin-event-btn"><?= esc(lang('App.usersCreateButton')) ?></a>
    </div>

    <?php if (session()->getFlashdata('users_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('users_error')) ?></p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('users_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('users_info')) ?></p>
    <?php endif; ?>

    <section class="card users-card">
        <?php if (empty($users)): ?>
            <p><?= esc(lang('App.usersEmpty')) ?></p>
        <?php else: ?>
            <div class="report-table-wrap">
                <table class="admin-table users-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.usersName')) ?></th>
                            <th><?= esc(lang('App.usersEmail')) ?></th>
                            <th><?= esc(lang('App.usersRole')) ?></th>
                            <th><?= esc(lang('App.usersStatus')) ?></th>
                            <th><?= esc(lang('App.usersCreatedAt')) ?></th>
                            <th><?= esc(lang('App.usersActions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $fullName = trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')));
                            $status = (string) ($user['status'] ?? 'inactive');
                            $role = (string) ($user['role'] ?? 'client');
                            $statusKey = 'App.usersStatus' . ucfirst($status === 'banned' ? 'Blocked' : $status);
                            ?>
                            <tr>
                                <td><strong><?= esc($fullName !== '' ? $fullName : '-') ?></strong></td>
                                <td><?= esc((string) ($user['email'] ?? '-')) ?></td>
                                <td><span class="table-pill table-pill--role"><?= esc(lang('App.usersRole' . ucfirst($role))) ?></span></td>
                                <td><span class="status <?= esc($status === 'banned' ? 'cancelled' : $status) ?>"><?= esc(lang($statusKey)) ?></span></td>
                                <td><?= esc(! empty($user['created_at']) ? date('d/m/Y H:i', strtotime((string) $user['created_at'])) : '-') ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a href="<?= base_url('users/' . (int) $user['id'] . '/edit') ?>" class="auth-link-btn admin-action-link"><?= esc(lang('App.usersEditButton')) ?></a>

                                        <?php if ((string) ($user['status'] ?? '') === 'banned'): ?>
                                            <form method="post" action="<?= base_url('users/' . (int) $user['id'] . '/unblock') ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="auth-link-btn admin-action-btn admin-action-btn--success"><?= esc(lang('App.usersUnblockButton')) ?></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?= base_url('users/' . (int) $user['id'] . '/block') ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="auth-link-btn admin-action-btn admin-action-btn--warn"><?= esc(lang('App.usersBlockButton')) ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" action="<?= base_url('users/' . (int) $user['id'] . '/delete') ?>" onsubmit="return confirm('<?= esc(lang('App.usersDeleteConfirm'), 'js') ?>');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="auth-link-btn admin-action-btn admin-action-btn--danger"><?= esc(lang('App.usersDeleteButton')) ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
