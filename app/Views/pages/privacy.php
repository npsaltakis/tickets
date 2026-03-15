<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper legal-page">
    <section class="legal-hero">
        <p class="legal-kicker"><?= esc(lang('App.privacyTitleShort')) ?></p>
        <h1><?= esc(lang('App.privacyTitle')) ?></h1>
        <p class="subtitle legal-subtitle"><?= esc(lang('App.privacySubtitle')) ?></p>
        <p class="legal-updated"><?= esc(lang('App.gdprUpdatedLabel')) ?>: <?= esc(lang('App.gdprUpdatedDate')) ?></p>
    </section>

    <section class="legal-grid">
        <article class="legal-card">
            <h2><?= esc(lang('App.privacySection1Title')) ?></h2>
            <p><?= esc(lang('App.privacySection1Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.privacySection2Title')) ?></h2>
            <p><?= esc(lang('App.privacySection2Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.privacySection3Title')) ?></h2>
            <p><?= esc(lang('App.privacySection3Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.privacySection4Title')) ?></h2>
            <p><?= esc(lang('App.privacySection4Body')) ?></p>
        </article>
    </section>
</main>
<?= $this->endSection() ?>
