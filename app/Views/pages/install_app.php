<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper legal-page install-app-page">
    <section class="legal-hero">
        <p class="legal-kicker"><?= esc(lang('App.footerInstallApp')) ?></p>
        <h1><?= esc(lang('App.installAppTitle')) ?></h1>
        <p class="subtitle legal-subtitle"><?= esc(lang('App.installAppSubtitle')) ?></p>
        <p class="install-app-installed" data-pwa-installed hidden><?= esc(lang('App.installAppInstalled')) ?></p>
        <button type="button" class="auth-link-btn install-app-button" data-pwa-install hidden><?= esc(lang('App.installAppButton')) ?></button>
    </section>

    <section class="legal-grid">
        <article class="legal-card">
            <h2><?= esc(lang('App.installAppAndroidTitle')) ?></h2>
            <ol>
                <li><?= esc(lang('App.installAppAndroidStep1')) ?></li>
                <li><?= esc(lang('App.installAppAndroidStep2')) ?></li>
                <li><?= esc(lang('App.installAppAndroidStep3')) ?></li>
            </ol>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.installAppIosTitle')) ?></h2>
            <ol>
                <li><?= esc(lang('App.installAppIosStep1')) ?></li>
                <li><?= esc(lang('App.installAppIosStep2')) ?></li>
                <li><?= esc(lang('App.installAppIosStep3')) ?></li>
            </ol>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.installAppDesktopTitle')) ?></h2>
            <ol>
                <li><?= esc(lang('App.installAppDesktopStep1')) ?></li>
                <li><?= esc(lang('App.installAppDesktopStep2')) ?></li>
            </ol>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.installAppWhyTitle')) ?></h2>
            <p><?= esc(lang('App.installAppWhyBody')) ?></p>
        </article>
    </section>
</main>
<?= $this->endSection() ?>
