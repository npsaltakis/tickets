<footer class="site-footer">
    <div class="top-nav-inner footer-inner-shell">
        <div class="footer-menu-card">
            <nav class="footer-menu" aria-label="Footer menu">
                <a class="footer-menu-item footer-menu-link" href="<?= base_url('gdpr') ?>"><?= esc(lang('App.footerGdpr')) ?></a>
                <a class="footer-menu-item footer-menu-link" href="<?= base_url('about') ?>"><?= esc(lang('App.footerAbout')) ?></a>
                <a class="footer-menu-item footer-menu-link" href="<?= base_url('contact') ?>"><?= esc(lang('App.footerContact')) ?></a>
            </nav>
        </div>
        <div class="footer-copy-wrap">
            <div class="footer-copy"><?= esc(lang('App.footerCopyright')) ?></div>
        </div>
    </div>
</footer>
<div id="cookie-notice" class="cookie-notice" hidden role="dialog" aria-live="polite" aria-label="<?= esc(lang('App.cookieNoticeTitle'), 'attr') ?>">
    <p><?= esc(lang('App.cookieNoticeText')) ?> <a href="<?= base_url('privacy-policy') ?>"><?= esc(lang('App.cookieNoticeMore')) ?></a></p>
    <button type="button" class="auth-link-btn" data-cookie-accept><?= esc(lang('App.cookieNoticeAccept')) ?></button>
</div>
    <script src="<?= base_url('assets/js/cookie-notice.js') ?>"></script>
    <script src="<?= base_url('assets/js/ui-actions.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/ui-actions.js') ?: time() ?>"></script>
</body>
</html>
