<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?><?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php $isAdmin = session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin'; ?>

    <div class="events-header">
        <div>
            <h1><?= esc(lang('App.eventsPageTitle')) ?></h1>
            <p class="subtitle"><?= esc(lang('App.eventsPageSubtitle')) ?></p>
        </div>

        <?php if ($isAdmin): ?>
            <div class="admin-home-actions">
                <a href="<?= base_url('events/create') ?>" class="admin-event-btn"><?= esc(lang('App.adminNewEventButton')) ?></a>
                <a href="<?= base_url('events/deleted') ?>" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.deletedEventsButton')) ?></a>
                <form method="post" action="<?= base_url('admin/test-email') ?>" class="event-inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.adminTestEmailButton')) ?></button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="events-toolbar">
        <div class="events-search-box">
            <input
                id="events-search"
                class="auth-input events-search-input"
                type="search"
                placeholder="<?= esc(lang('App.eventsSearchPlaceholder'), 'attr') ?>"
                autocomplete="off"
                data-min-length="3">
            <p class="events-search-hint" id="events-search-hint"><?= esc(lang('App.eventsSearchHint')) ?></p>
        </div>
    </div>

    <?php if (session()->getFlashdata('login_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('login_error')) ?></p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('event_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('event_info')) ?></p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('event_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
    <?php endif; ?>

    <?php if (empty($events)): ?>
        <div class="empty">
            <?= esc(lang('App.eventsEmpty')) ?>
        </div>
    <?php else: ?>
        <section
            class="grid events-grid"
            id="events-grid"
            data-batch-size="<?= esc((string) $batchSize) ?>"
            data-feed-url="<?= esc(base_url('events/feed'), 'attr') ?>"
            data-search-empty-label="<?= esc(lang('App.eventsSearchEmpty'), 'attr') ?>"
            data-initial-count="<?= esc((string) count($events)) ?>"
            data-has-more="<?= $hasMore ? '1' : '0' ?>">
            <?= view('events/_event_cards', ['events' => $events]) ?>
        </section>

        <p class="events-search-empty is-hidden" id="events-search-empty"><?= esc(lang('App.eventsSearchEmpty')) ?></p>

        <div
            class="events-scroll-status<?= $hasMore ? '' : ' is-finished' ?>"
            id="events-scroll-status"
            data-load-label="<?= esc(lang('App.eventsLoadMore'), 'attr') ?>"
            data-done-label="<?= esc(lang('App.eventsAllLoaded'), 'attr') ?>">
            <span id="events-scroll-text"><?= esc($hasMore ? lang('App.eventsLoadMore') : lang('App.eventsAllLoaded')) ?></span>
        </div>
        <div class="events-scroll-sentinel<?= $hasMore ? '' : ' is-hidden' ?>" id="events-scroll-sentinel" aria-hidden="true"></div>
    <?php endif; ?>
</main>
<script src="<?= base_url('assets/js/events-index.js') ?>?v=<?= esc($assetVersion('assets/js/events-index.js')) ?>"></script>
<?= $this->endSection() ?>




