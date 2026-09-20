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
    <script src="<?= base_url('assets/js/ui-actions.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/ui-actions.js') ?: time() ?>"></script>
</body>
</html>
