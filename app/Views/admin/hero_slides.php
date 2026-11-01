<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$field = static fn (string $key, $default = ''): string => (string) old($key, $editing[$key] ?? $default);
$imageUrl = static function (string $path): string {
    return $path === '' ? '' : (preg_match('#^https?://#i', $path) ? $path : base_url(ltrim($path, '/')));
};
$type = $field('type', 'image');
?>
<div class="wrapper">

    <?php if (session()->getFlashdata('hero_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('hero_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('hero_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('hero_error')) ?></p>
    <?php endif; ?>

    <p class="field-hint"><?= esc(lang('App.heroSlidesHint')) ?></p>

    <div class="admin-split">

        <div class="card">
            <?php if (empty($slides)): ?>
                <div class="empty"><?= esc(lang('App.heroSlidesEmpty')) ?></div>
            <?php else: ?>
                <?php /* Three columns only, so the buttons stay in view on phones and tablets. */ ?>
                <table class="admin-table hero-admin-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.heroSlideImage')) ?></th>
                            <th><?= esc(lang('App.heroSlideTitle')) ?></th>
                            <th><?= esc(lang('App.adminEventsActions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $slide): ?>
                            <?php
                            $isEvent = $slide['type'] === 'event';
                            $title = (string) ($slide['title'] ?? '');
                            if ($title === '' && $isEvent) {
                                $title = (string) ($eventTitles[(int) $slide['event_id']] ?? lang('App.heroSlideEventMissing'));
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php if (! empty($slide['image'])): ?>
                                        <img class="hero-admin-thumb" src="<?= esc($imageUrl((string) $slide['image']), 'attr') ?>" alt="" width="96" height="40" loading="lazy">
                                    <?php elseif ($isEvent): ?>
                                        <span class="meta"><?= esc(lang('App.heroSlideEventImage')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= esc($title) ?>
                                    <div class="meta hero-admin-meta">
                                        <?= esc(lang('App.heroSlideOrder')) ?> <?= (int) $slide['sort_order'] ?>
                                        · <?= esc(lang($isEvent ? 'App.heroSlideTypeEvent' : 'App.heroSlideTypeImage')) ?>
                                        · <?= esc(lang((int) $slide['is_active'] === 1 ? 'App.heroSlideActive' : 'App.heroSlideInactive')) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="hero-admin-actions">
                                        <a class="admin-action-btn hero-admin-icon-btn" href="<?= base_url('admin/hero-slides?edit=' . (int) $slide['id']) ?>" title="<?= esc(lang('App.categoriesEdit'), 'attr') ?>" aria-label="<?= esc(lang('App.categoriesEdit') . ': ' . $title, 'attr') ?>">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </a>
                                        <form method="post" action="<?= base_url('admin/hero-slides/' . (int) $slide['id'] . '/delete') ?>" data-confirm="<?= esc(lang('App.heroSlideDeleteConfirm'), 'attr') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="admin-action-btn admin-action-btn--danger hero-admin-icon-btn" title="<?= esc(lang('App.categoriesDelete'), 'attr') ?>" aria-label="<?= esc(lang('App.categoriesDelete') . ': ' . $title, 'attr') ?>">
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
            <h2 class="hero-admin-form-title"><?= esc(lang($editing ? 'App.heroSlideEditTitle' : 'App.heroSlideCreateTitle')) ?></h2>
            <form method="post" action="<?= base_url($editing ? 'admin/hero-slides/' . (int) $editing['id'] . '/update' : 'admin/hero-slides') ?>" enctype="multipart/form-data" class="auth-form">
                <?= csrf_field() ?>

                <label class="auth-label" for="hero_type"><?= esc(lang('App.heroSlideType')) ?></label>
                <select id="hero_type" name="type" class="auth-input">
                    <option value="image" <?= $type === 'image' ? 'selected' : '' ?>><?= esc(lang('App.heroSlideTypeImage')) ?></option>
                    <option value="event" <?= $type === 'event' ? 'selected' : '' ?>><?= esc(lang('App.heroSlideTypeEvent')) ?></option>
                </select>

                <label class="auth-label" for="hero_event"><?= esc(lang('App.heroSlideEvent')) ?></label>
                <select id="hero_event" name="event_id" class="auth-input">
                    <option value="">—</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= (int) $event['id'] ?>" <?= (int) $field('event_id', 0) === $event['id'] ? 'selected' : '' ?>>
                            <?= esc($event['title'] . (! empty($event['start_date']) ? ' (' . date('d/m/Y', strtotime((string) $event['start_date'])) . ')' : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field-hint"><?= esc(lang('App.heroSlideEventHint')) ?></p>

                <label class="auth-label" for="hero_image"><?= esc(lang('App.heroSlideImage')) ?></label>
                <?php if (! empty($editing['image'])): ?>
                    <img class="hero-admin-preview" src="<?= esc($imageUrl((string) $editing['image']), 'attr') ?>" alt="" width="320" height="120">
                    <label class="booking-consent" for="remove_image"><input id="remove_image" name="remove_image" type="checkbox" value="1"><span><?= esc(lang('App.settingsRemove')) ?></span></label>
                <?php endif; ?>
                <input id="hero_image" name="image" type="file" class="auth-input" accept="image/jpeg,image/png,image/webp">
                <p class="field-hint"><?= esc(lang('App.heroSlideImageHint')) ?></p>

                <label class="auth-label" for="hero_title"><?= esc(lang('App.heroSlideTitle')) ?></label>
                <input id="hero_title" name="title" type="text" class="auth-input" maxlength="191" value="<?= esc($field('title'), 'attr') ?>">
                <p class="field-hint"><?= esc(lang('App.heroSlideTitleHint')) ?></p>

                <label class="auth-label" for="hero_link"><?= esc(lang('App.heroSlideLink')) ?></label>
                <input id="hero_link" name="link_url" type="text" class="auth-input" maxlength="255" placeholder="https://… ή /events/…" value="<?= esc($field('link_url'), 'attr') ?>">

                <label class="auth-label" for="hero_button"><?= esc(lang('App.heroSlideButton')) ?></label>
                <input id="hero_button" name="button_text" type="text" class="auth-input" maxlength="60" placeholder="<?= esc(lang('App.heroViewEvent'), 'attr') ?>" value="<?= esc($field('button_text'), 'attr') ?>">

                <p class="field-hint"><?= esc(lang('App.heroSlideTextHint')) ?></p>

                <label class="auth-label" for="hero_order"><?= esc(lang('App.heroSlideOrder')) ?></label>
                <input id="hero_order" name="sort_order" type="number" class="auth-input" value="<?= esc($field('sort_order', '0'), 'attr') ?>">

                <label class="booking-consent" for="hero_active"><input id="hero_active" name="is_active" type="checkbox" value="1" <?= $field('is_active', '1') === '1' ? 'checked' : '' ?>><span><?= esc(lang('App.heroSlideActive')) ?></span></label>

                <button type="submit" class="book-btn auth-submit"><?= esc(lang($editing ? 'App.categoriesSave' : 'App.categoriesCreate')) ?></button>
                <?php if ($editing): ?>
                    <a class="auth-link-btn" href="<?= base_url('admin/hero-slides') ?>"><?= esc(lang('App.categoriesCancel')) ?></a>
                <?php endif; ?>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
