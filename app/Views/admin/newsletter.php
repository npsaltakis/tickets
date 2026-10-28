<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper">
    <?php if (session()->getFlashdata('newsletter_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('newsletter_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('newsletter_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('newsletter_error')) ?></p>
    <?php endif; ?>

    <div class="card" style="max-width:720px">
        <h2 style="margin:0 0 4px;color:#f8fafc;font-size:1.1rem"><?= esc(lang('App.newsletterTitle')) ?></h2>
        <p class="meta" style="margin:0 0 6px"><?= esc(strtr(lang('App.newsletterSubscribers'), ['{n}' => (string) $subscriberCount])) ?></p>
        <p class="meta" style="margin:0 0 20px"><?= esc(strtr(lang('App.newsletterPaceHint'), ['{n}' => (string) $batchSize])) ?></p>

        <form method="post" action="<?= base_url('admin/newsletter') ?>" class="auth-form" data-confirm="<?= esc(strtr(lang('App.newsletterConfirm'), ['{n}' => (string) $subscriberCount]), 'attr') ?>">
            <?= csrf_field() ?>
            <label class="auth-label" for="nl_subject"><?= esc(lang('App.emailAttendeesSubjectLabel')) ?></label>
            <input id="nl_subject" name="subject" type="text" class="auth-input" maxlength="200" value="<?= esc((string) old('subject')) ?>" required>

            <label class="auth-label" for="nl_message"><?= esc(lang('App.emailAttendeesMessageLabel')) ?></label>
            <textarea id="nl_message" name="message" class="auth-input event-textarea" style="min-height:200px" required><?= esc((string) old('message')) ?></textarea>
            <p class="field-hint"><?= esc(lang('App.newsletterMessageHint')) ?></p>

            <label class="auth-label" for="nl_event"><?= esc(lang('App.newsletterEventLabel')) ?></label>
            <select id="nl_event" name="event_id" class="auth-input">
                <option value="0"><?= esc(lang('App.newsletterNoEvent')) ?></option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= (int) $event['id'] ?>" <?= (string) old('event_id') === (string) $event['id'] ? 'selected' : '' ?>>
                        <?= esc($event['title']) ?><?= ! empty($event['start_date']) ? ' — ' . esc(date('d/m/Y', strtotime((string) $event['start_date']))) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="booking-consent" for="nl_test">
                <input id="nl_test" name="test_only" type="checkbox" value="1" checked>
                <span><?= esc(lang('App.newsletterTestOnly')) ?></span>
            </label>

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.newsletterSend')) ?></button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;overflow:auto">
        <h2 style="margin:0 0 12px;color:#f8fafc;font-size:1.1rem"><?= esc(lang('App.newsletterHistory')) ?></h2>
        <?php if (empty($campaigns)): ?>
            <div class="empty"><?= esc(lang('App.newsletterHistoryEmpty')) ?></div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= esc(lang('App.newsletterColDate')) ?></th>
                        <th><?= esc(lang('App.emailAttendeesSubjectLabel')) ?></th>
                        <th><?= esc(lang('App.newsletterColRecipients')) ?></th>
                        <th><?= esc(lang('App.newsletterStatusSent')) ?></th>
                        <th><?= esc(lang('App.newsletterStatusPending')) ?></th>
                        <th><?= esc(lang('App.newsletterStatusFailed')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c): ?>
                        <tr>
                            <td><?= esc(date('d/m/Y H:i', strtotime((string) $c['created_at']))) ?></td>
                            <td><?= esc($c['subject']) ?></td>
                            <td><?= (int) $c['recipients'] ?></td>
                            <td><?= (int) $c['sent'] ?></td>
                            <td><?= (int) $c['pending'] ?></td>
                            <td><?= (int) $c['failed'] > 0 ? '<strong style="color:#f87171">' . (int) $c['failed'] . '</strong>' : '0' ?></td>
                            <td><a class="admin-action-btn" href="<?= base_url('admin/newsletter/' . (int) $c['id']) ?>"><?= esc(lang('App.newsletterDetails')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
