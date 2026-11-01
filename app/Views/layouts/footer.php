<footer class="site-footer">
    <div class="top-nav-inner footer-inner-shell">
        <?php if (! url_is('login')): ?>
        <div class="footer-menu-card">
            <nav class="footer-menu" aria-label="Footer menu">
                <a class="footer-menu-item footer-menu-link" href="<?= base_url('gdpr') ?>"><?= esc(lang('App.footerGdpr')) ?></a>
                <a class="footer-menu-item footer-menu-link" href="<?= base_url('about') ?>"><?= esc(lang('App.footerAbout')) ?></a>
                <a class="footer-menu-item footer-menu-link" href="<?= base_url('contact') ?>"><?= esc(lang('App.footerContact')) ?></a>
                <a class="footer-menu-item footer-menu-link footer-install-link" href="<?= base_url('install-app') ?>"><?= esc(lang('App.footerInstallApp')) ?></a>
            </nav>
        </div>
        <?php endif; ?>
        <div class="footer-copy-wrap">
            <?php $footerText = \App\Libraries\SiteSettings::get('footer_text'); ?>
            <div class="footer-copy"><?= esc($footerText !== '' ? $footerText : '© ' . date('Y') . ' ' . lang('App.siteTitle')) ?></div>
        </div>
    </div>
</footer>
<div id="cookie-notice" class="cookie-notice" hidden role="dialog" aria-live="polite" aria-label="<?= esc(lang('App.cookieNoticeTitle'), 'attr') ?>">
    <p><?= esc(lang('App.cookieNoticeText')) ?> <a href="<?= base_url('privacy-policy') ?>"><?= esc(lang('App.cookieNoticeMore')) ?></a></p>
    <button type="button" class="auth-link-btn" data-cookie-accept><?= esc(lang('App.cookieNoticeAccept')) ?></button>
</div>
<div class="help-chat" data-help-chat data-menu-url="<?= esc(base_url('help/menu'), 'attr') ?>" data-answer-url="<?= esc(base_url('help/answer'), 'attr') ?>" data-error-text="<?= esc(lang('App.helpError'), 'attr') ?>">
    <button type="button" class="help-chat-toggle" data-help-toggle aria-expanded="false" aria-controls="help-chat-panel" aria-label="<?= esc(lang('App.helpOpen'), 'attr') ?>">
        <svg class="help-chat-icon-open" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
        <svg class="help-chat-icon-close" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
    </button>
    <section class="help-chat-panel" id="help-chat-panel" role="dialog" aria-label="<?= esc(lang('App.helpTitle'), 'attr') ?>" hidden>
        <header class="help-chat-header">
            <strong><?= esc(lang('App.helpTitle')) ?></strong>
            <span class="help-chat-subtitle"><?= esc(lang('App.helpSubtitle')) ?></span>
        </header>
        <div class="help-chat-log" data-help-log aria-live="polite">
            <div class="help-chat-msg help-chat-msg--bot"><?= esc(lang('App.helpWelcome')) ?></div>
        </div>
        <div class="help-chat-questions" data-help-questions></div>
    </section>
</div>
    <script src="<?= base_url('assets/js/help-chat.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/help-chat.js') ?: time() ?>"></script>
    <script src="<?= base_url('assets/js/cookie-notice.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/cookie-notice.js') ?: time() ?>"></script>
    <script src="<?= base_url('assets/js/ui-actions.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/ui-actions.js') ?: time() ?>"></script>
</body>
</html>
