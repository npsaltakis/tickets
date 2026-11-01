<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$field = static fn (string $key, $default = ''): string => (string) old($key, $editing[$key] ?? $default);
$localeName = static fn (string $code): string => $code === 'en' ? 'English' : 'Ελληνικά';
?>
<div class="wrapper">

    <?php if (session()->getFlashdata('faq_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('faq_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('faq_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('faq_error')) ?></p>
    <?php endif; ?>

    <p class="field-hint"><?= esc(lang('App.faqAdminHint')) ?></p>

    <div class="admin-split">

        <div class="card">
            <?php if (empty($faqs)): ?>
                <div class="empty"><?= esc(lang('App.faqEmpty')) ?></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.faqQuestion')) ?></th>
                            <th><?= esc(lang('App.faqLocale')) ?></th>
                            <th><?= esc(lang('App.heroSlideOrder')) ?></th>
                            <th><?= esc(lang('App.heroSlideActive')) ?></th>
                            <th><?= esc(lang('App.adminEventsActions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $faq): ?>
                            <tr>
                                <td><?= esc((string) $faq['question']) ?></td>
                                <td><?= esc($localeName((string) $faq['locale'])) ?></td>
                                <td><?= (int) $faq['sort_order'] ?></td>
                                <td><?= esc(lang((int) $faq['is_active'] === 1 ? 'App.heroSlideActive' : 'App.heroSlideInactive')) ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a class="admin-action-link admin-icon-btn" href="<?= base_url('admin/help-faqs?edit=' . (int) $faq['id']) ?>" title="<?= esc(lang('App.categoriesEdit'), 'attr') ?>" aria-label="<?= esc(lang('App.categoriesEdit') . ': ' . $faq['question'], 'attr') ?>">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </a>
                                        <form method="post" action="<?= base_url('admin/help-faqs/' . (int) $faq['id'] . '/delete') ?>" data-confirm="<?= esc(lang('App.faqDeleteConfirm'), 'attr') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="admin-action-btn admin-action-btn--danger admin-icon-btn" title="<?= esc(lang('App.categoriesDelete'), 'attr') ?>" aria-label="<?= esc(lang('App.categoriesDelete') . ': ' . $faq['question'], 'attr') ?>">
                                                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 class="hero-admin-form-title"><?= esc(lang($editing ? 'App.faqEditTitle' : 'App.faqCreateTitle')) ?></h2>
            <form method="post" action="<?= base_url($editing ? 'admin/help-faqs/' . (int) $editing['id'] . '/update' : 'admin/help-faqs') ?>" class="auth-form">
                <?= csrf_field() ?>

                <label class="auth-label" for="faq_locale"><?= esc(lang('App.faqLocale')) ?></label>
                <select id="faq_locale" name="locale" class="auth-input">
                    <?php foreach ($locales as $code): ?>
                        <option value="<?= esc($code, 'attr') ?>" <?= $field('locale', 'el') === $code ? 'selected' : '' ?>><?= esc($localeName($code)) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="auth-label" for="faq_question"><?= esc(lang('App.faqQuestion')) ?> *</label>
                <input id="faq_question" name="question" type="text" class="auth-input" maxlength="191" required value="<?= esc($field('question'), 'attr') ?>">

                <label class="auth-label" for="faq_answer"><?= esc(lang('App.faqAnswer')) ?> *</label>
                <textarea id="faq_answer" name="answer" class="auth-input" rows="5" maxlength="2000" required><?= esc($field('answer')) ?></textarea>

                <label class="auth-label" for="faq_link"><?= esc(lang('App.heroSlideLink')) ?></label>
                <input id="faq_link" name="link_url" type="text" class="auth-input" maxlength="255" placeholder="/contact" value="<?= esc($field('link_url'), 'attr') ?>">

                <label class="auth-label" for="faq_link_label"><?= esc(lang('App.heroSlideButton')) ?></label>
                <input id="faq_link_label" name="link_label" type="text" class="auth-input" maxlength="60" value="<?= esc($field('link_label'), 'attr') ?>">
                <p class="field-hint"><?= esc(lang('App.faqLinkHint')) ?></p>

                <label class="auth-label" for="faq_order"><?= esc(lang('App.heroSlideOrder')) ?></label>
                <input id="faq_order" name="sort_order" type="number" class="auth-input" value="<?= esc($field('sort_order', '0'), 'attr') ?>">

                <label class="booking-consent" for="faq_active"><input id="faq_active" name="is_active" type="checkbox" value="1" <?= $field('is_active', '1') === '1' ? 'checked' : '' ?>><span><?= esc(lang('App.heroSlideActive')) ?></span></label>

                <button type="submit" class="book-btn auth-submit"><?= esc(lang($editing ? 'App.categoriesSave' : 'App.categoriesCreate')) ?></button>
                <?php if ($editing): ?>
                    <a class="auth-link-btn" href="<?= base_url('admin/help-faqs') ?>"><?= esc(lang('App.categoriesCancel')) ?></a>
                <?php endif; ?>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
