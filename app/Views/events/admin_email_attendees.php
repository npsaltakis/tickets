<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper">
    <a class="back-link" href="<?= base_url('admin/events/' . $event['slug'] . '/tickets') ?>">&larr; <?= esc($event['title']) ?></a>

    <?php if (session()->getFlashdata('email_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('email_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('email_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('email_error')) ?></p>
    <?php endif; ?>

    <div class="card" style="max-width:640px;margin-top:16px">
        <h2 style="margin:0 0 4px;color:#f8fafc;font-size:1.1rem"><?= esc(lang('App.emailAttendeesTitle')) ?></h2>
        <p class="meta" style="margin:0 0 20px"><?= esc(lang('App.emailAttendeesSubtitle')) ?></p>

        <form method="post" action="<?= base_url('admin/events/' . $event['slug'] . '/email-attendees') ?>" class="auth-form">
            <?= csrf_field() ?>
            <label class="auth-label"><?= esc(lang('App.emailAttendeesSubjectLabel')) ?></label>
            <input name="subject" type="text" class="auth-input" value="<?= esc((string) old('subject')) ?>" required>

            <label class="auth-label"><?= esc(lang('App.emailAttendeesMessageLabel')) ?></label>
            <textarea name="message" class="auth-input event-textarea" style="min-height:180px" required><?= esc((string) old('message')) ?></textarea>

            <p class="field-hint"><?= esc(lang('App.emailAttendeesHint')) ?></p>

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.emailAttendeesSend')) ?></button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
