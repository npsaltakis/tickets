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

    <script>window.baseUrl = '<?= base_url('/') ?>';
    window.calendarMonthNames = <?= json_encode([
        lang('App.calMonthJan'), lang('App.calMonthFeb'), lang('App.calMonthMar'),
        lang('App.calMonthApr'), lang('App.calMonthMay'), lang('App.calMonthJun'),
        lang('App.calMonthJul'), lang('App.calMonthAug'), lang('App.calMonthSep'),
        lang('App.calMonthOct'), lang('App.calMonthNov'), lang('App.calMonthDec'),
    ], JSON_UNESCAPED_UNICODE) ?>;</script>

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
        <div class="view-toggle">
            <button id="view-grid" class="view-toggle-btn is-active" title="<?= esc(lang('App.viewGrid')) ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
            </button>
            <button id="view-cal" class="view-toggle-btn" title="<?= esc(lang('App.viewCalendar')) ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            </button>
        </div>
    </div>

    <div id="calendar-wrap" class="calendar-wrap" style="display:none"></div>

    <?php if (!empty($categories)): ?>
        <div class="category-filter">
            <a href="<?= base_url('/') ?>" class="category-pill <?= ($activeCatId ?? 0) === 0 ? 'is-active' : '' ?>">
                <?= esc(lang('App.categoryAll')) ?>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= base_url('/?cat=' . (int)$cat['id']) ?>" class="category-pill <?= ($activeCatId ?? 0) === (int)$cat['id'] ? 'is-active' : '' ?>" style="--cat-color:<?= esc($cat['color'] ?? '#14b8a6') ?>">
                    <?= esc($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
<script src="<?= base_url('assets/js/calendar.js') ?>?v=<?= esc($assetVersion('assets/js/calendar.js')) ?>"></script>
<?= $this->endSection() ?>




