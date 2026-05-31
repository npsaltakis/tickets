<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper">

    <?php if (session()->getFlashdata('dc_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('dc_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('dc_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('dc_error')) ?></p>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

        <div class="card" style="overflow:auto">
            <?php if (empty($codes)): ?>
                <div class="empty"><?= esc(lang('App.discountCodesEmpty')) ?></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.discountCodesCode')) ?></th>
                            <th><?= esc(lang('App.discountCodesType')) ?></th>
                            <th><?= esc(lang('App.discountCodesValue')) ?></th>
                            <th><?= esc(lang('App.discountCodesUses')) ?></th>
                            <th><?= esc(lang('App.discountCodesExpires')) ?></th>
                            <th><?= esc(lang('App.adminEventsActions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $code): ?>
                            <tr>
                                <td>
                                    <code style="font-weight:700;color:#93c5fd"><?= esc($code['code']) ?></code>
                                    <?php if (!empty($code['description'])): ?>
                                        <div class="meta" style="font-size:0.8rem"><?= esc($code['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="table-pill"><?= esc($code['type']) ?></span></td>
                                <td><?= $code['type'] === 'percent' ? esc($code['value']) . '%' : '€' . esc(number_format((float)$code['value'], 2)) ?></td>
                                <td><?= esc($code['used_count']) ?><?= $code['max_uses'] !== null ? ' / ' . esc($code['max_uses']) : '' ?></td>
                                <td class="meta" style="font-size:0.82rem;white-space:nowrap">
                                    <?= !empty($code['expires_at']) ? esc(date('d/m/Y H:i', strtotime((string)$code['expires_at']))) : '—' ?>
                                </td>
                                <td>
                                    <form method="post" action="<?= base_url('admin/discount-codes/' . (int)$code['id'] . '/delete') ?>" style="margin:0" onsubmit="return confirm('Delete code <?= esc($code['code'], 'attr') ?>?')">
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
            <h2 style="margin:0 0 16px;font-size:1rem;color:#f8fafc"><?= esc(lang('App.discountCodesCreate')) ?></h2>
            <form method="post" action="<?= base_url('admin/discount-codes') ?>" class="auth-form">
                <?= csrf_field() ?>

                <label class="auth-label"><?= esc(lang('App.discountCodesCode')) ?></label>
                <input name="code" type="text" class="auth-input" placeholder="SUMMER20" required style="text-transform:uppercase">

                <label class="auth-label"><?= esc(lang('App.discountCodesDescription')) ?></label>
                <input name="description" type="text" class="auth-input" placeholder="<?= esc(lang('App.discountCodesDescPlaceholder')) ?>">

                <label class="auth-label"><?= esc(lang('App.discountCodesType')) ?></label>
                <select name="type" class="auth-input">
                    <option value="percent"><?= esc(lang('App.discountCodesPercent')) ?></option>
                    <option value="fixed"><?= esc(lang('App.discountCodesFixed')) ?></option>
                </select>

                <label class="auth-label"><?= esc(lang('App.discountCodesValue')) ?></label>
                <input name="value" type="number" min="0.01" step="0.01" class="auth-input" required>

                <label class="auth-label"><?= esc(lang('App.discountCodesMaxUses')) ?></label>
                <input name="max_uses" type="number" min="1" class="auth-input" placeholder="<?= esc(lang('App.discountCodesUnlimited')) ?>">

                <label class="auth-label"><?= esc(lang('App.discountCodesEvent')) ?></label>
                <select name="event_id" class="auth-input">
                    <option value=""><?= esc(lang('App.discountCodesAllEvents')) ?></option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= esc($ev['id']) ?>"><?= esc($ev['title']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="auth-label"><?= esc(lang('App.discountCodesExpires')) ?></label>
                <input name="expires_at" type="datetime-local" class="auth-input">

                <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.discountCodesCreate')) ?></button>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
