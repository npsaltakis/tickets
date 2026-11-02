<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$typeLabels = [
    'db'          => lang('App.backupTypeDb'),
    'full'        => lang('App.backupTypeFull'),
    'pre-restore' => lang('App.backupTypePreRestore'),
    'uploaded'    => lang('App.backupTypeUploaded'),
    'code'        => lang('App.backupTypeCode'),
];
$formatSize = static fn (int $bytes): string => $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
?>
<div class="wrapper">
    <?php if (session()->getFlashdata('backup_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('backup_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('backup_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('backup_error')) ?></p>
    <?php endif; ?>

    <section class="card backup-card">
        <h2><?= esc(lang('App.backupCreateTitle')) ?></h2>
        <p class="meta"><?= esc(lang('App.backupCreateHelp')) ?></p>
        <div class="backup-actions">
            <form method="post" action="<?= base_url('admin/backups/create') ?>" data-submit-once data-backup-progress>
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="db">
                <button type="submit" class="admin-event-btn"><?= esc(lang('App.backupCreateDb')) ?></button>
            </form>
            <form method="post" action="<?= base_url('admin/backups/create') ?>" data-submit-once data-backup-progress>
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="full">
                <button type="submit" class="admin-event-btn"><?= esc(lang('App.backupCreateFull')) ?></button>
            </form>
        </div>
        <div class="backup-progress" id="backup-progress" hidden data-failed="<?= esc(lang('App.backupFailed'), 'attr') ?>" data-starting="<?= esc(lang('App.backupProgressStart'), 'attr') ?>">
            <div class="backup-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="<?= esc(lang('App.backupCreateTitle'), 'attr') ?>">
                <div class="backup-progress-bar"></div>
            </div>
            <div class="backup-progress-info">
                <span class="backup-progress-message" aria-live="polite"></span>
                <strong class="backup-progress-percent">0%</strong>
            </div>
            <p class="meta"><?= esc(lang('App.backupProgressKeepOpen')) ?></p>
        </div>
        <p class="meta"><?= esc(lang('App.backupCronHelp')) ?> <code>php spark db:backup</code></p>
    </section>

    <section class="card backup-card">
        <h2><?= esc(lang('App.backupListTitle')) ?></h2>
        <?php if (empty($backups)): ?>
            <div class="empty"><?= esc(lang('App.backupEmpty')) ?></div>
        <?php else: ?>
            <table class="admin-table backup-table">
                <thead>
                    <tr>
                        <th><?= esc(lang('App.backupFile')) ?></th>
                        <th><?= esc(lang('App.type')) ?></th>
                        <th><?= esc(lang('App.backupDate')) ?></th>
                        <th><?= esc(lang('App.backupSize')) ?></th>
                        <th><?= esc(lang('App.adminEventsActions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td class="backup-name"><?= esc($backup['name']) ?></td>
                        <td data-label="<?= esc(lang('App.type'), 'attr') ?>"><span class="table-pill"><?= esc($typeLabels[$backup['type']] ?? $backup['type']) ?></span></td>
                        <td data-label="<?= esc(lang('App.backupDate'), 'attr') ?>"><?= esc(date('d/m/Y H:i', $backup['time'])) ?></td>
                        <td data-label="<?= esc(lang('App.backupSize'), 'attr') ?>"><?= esc($formatSize($backup['size'])) ?></td>
                        <td class="backup-actions-cell"><div class="backup-row-actions">
                            <a class="admin-event-btn admin-event-btn--secondary" href="<?= esc(base_url('admin/backups/download/' . $backup['name'])) ?>"><?= esc(lang('App.backupDownload')) ?></a>
                            <?php if ($backup['type'] !== 'code'): ?>
                                <details class="backup-restore">
                                    <summary class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.backupRestore')) ?></summary>
                                    <form method="post" action="<?= esc(base_url('admin/backups/restore/' . $backup['name'])) ?>" data-submit-once>
                                        <?= csrf_field() ?>
                                        <p class="meta"><?= esc(lang($backup['type'] === 'full' || str_ends_with($backup['name'], '.zip') ? 'App.backupRestoreWarnFull' : 'App.backupRestoreWarnDb')) ?></p>
                                        <label class="auth-label" for="confirm-<?= esc(md5($backup['name']), 'attr') ?>"><?= esc(lang('App.backupConfirmLabel')) ?></label>
                                        <input id="confirm-<?= esc(md5($backup['name']), 'attr') ?>" name="confirm_word" type="text" class="auth-input" autocomplete="off" required>
                                        <button type="submit" class="admin-event-btn backup-danger"><?= esc(lang('App.backupRestoreNow')) ?></button>
                                    </form>
                                </details>
                            <?php endif; ?>
                            <form method="post" action="<?= esc(base_url('admin/backups/delete/' . $backup['name'])) ?>" data-confirm="<?= esc(lang('App.backupDeleteConfirm'), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.backupDelete')) ?></button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card backup-card">
        <h2><?= esc(lang('App.backupUploadTitle')) ?></h2>
        <p class="meta"><?= esc(lang('App.backupUploadHelp')) ?><?= $uploadLimit !== '' ? ' ' . esc(strtr(lang('App.backupUploadLimit'), ['{size}' => $uploadLimit])) : '' ?></p>
        <form method="post" action="<?= base_url('admin/backups/upload') ?>" enctype="multipart/form-data" class="backup-upload" data-submit-once>
            <?= csrf_field() ?>
            <input type="file" name="backup_file" class="auth-input" accept=".zip,.gz,.sql" required>
            <label class="auth-label" for="upload-confirm"><?= esc(lang('App.backupConfirmLabel')) ?></label>
            <input id="upload-confirm" name="confirm_word" type="text" class="auth-input" autocomplete="off" required>
            <button type="submit" class="admin-event-btn backup-danger"><?= esc(lang('App.backupUploadRestore')) ?></button>
        </form>
    </section>

    <section class="card backup-card">
        <h2><?= esc(lang('App.backupSettingsTitle')) ?></h2>
        <form method="post" action="<?= base_url('admin/backups/settings') ?>" class="backup-settings">
            <?= csrf_field() ?>
            <label class="auth-label" for="keep_days"><?= esc(lang('App.backupKeepDays')) ?></label>
            <input id="keep_days" name="keep_days" type="number" class="auth-input" min="1" max="365" value="<?= (int) $keepDays ?>" required>
            <button type="submit" class="admin-event-btn"><?= esc(lang('App.backupSave')) ?></button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/admin-backups.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/admin-backups.js') ?: time() ?>"></script>
<?= $this->endSection() ?>
