<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
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
                <table
                    id="users-table"
                    class="display admin-table users-table js-users-table"
                    data-search-label="<?= esc(lang('App.reportSearch')) ?>"
                    data-empty-label="<?= esc(lang('App.reportEmptyTable')) ?>"
                    data-info-label="<?= esc(lang('App.reportInfo')) ?>"
                    data-info-empty-label="<?= esc(lang('App.reportInfoEmpty')) ?>"
                    data-zero-records-label="<?= esc(lang('App.reportZeroRecords')) ?>"
                    data-length-menu-label="<?= esc(lang('App.reportLengthMenu')) ?>"
                    data-order-column="4"
                    data-order-direction="desc"
                >
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
                            $loginLockedUntil = isset($user['login_locked_until']) ? (int) $user['login_locked_until'] : 0;
                            ?>
                            <tr>
                                <td><strong><?= esc($fullName !== '' ? $fullName : '-') ?></strong></td>
                                <td><?= esc((string) ($user['email'] ?? '-')) ?></td>
                                <td><span class="table-pill table-pill--role"><?= esc(lang('App.usersRole' . ucfirst($role))) ?></span></td>
                                <td>
                                    <span class="status <?= esc($status === 'banned' ? 'cancelled' : $status) ?>"><?= esc(lang($statusKey)) ?></span>
                                    <?php if ($loginLockedUntil > 0): ?>
                                        <div class="users-lock-meta">
                                            <?= esc(lang('App.usersLoginLockedUntil')) ?>: <?= esc(date('d/m/Y H:i', $loginLockedUntil)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
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

                                        <?php if ((string) ($user['status'] ?? '') === 'inactive'): ?>
                                            <form method="post" action="<?= base_url('users/' . (int) $user['id'] . '/resend-verification') ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="auth-link-btn admin-action-btn admin-action-btn--info"><?= esc(lang('App.usersResendVerificationButton')) ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" action="<?= base_url('users/' . (int) $user['id'] . '/delete') ?>" data-confirm-action data-confirm-message="<?= esc(lang('App.usersDeleteConfirm'), 'attr') ?>">
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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="<?= base_url('assets/js/users-index.js') ?>?v=<?= esc($assetVersion('assets/js/users-index.js')) ?>"></script>
<?= $this->endSection() ?>
