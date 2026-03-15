<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper legal-page">
    <section class="legal-hero">
        <p class="legal-kicker"><?= esc(lang('App.termsTitleShort')) ?></p>
        <h1><?= esc(lang('App.termsTitle')) ?></h1>
        <p class="subtitle legal-subtitle"><?= esc(lang('App.termsSubtitle')) ?></p>
        <p class="legal-updated"><?= esc(lang('App.gdprUpdatedLabel')) ?>: <?= esc(lang('App.gdprUpdatedDate')) ?></p>
    </section>

    <section class="legal-grid">
        <article class="legal-card">
            <h2><?= esc(lang('App.termsSection1Title')) ?></h2>
            <p><?= esc(lang('App.termsSection1Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.termsSection2Title')) ?></h2>
            <p><?= esc(lang('App.termsSection2Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.termsSection3Title')) ?></h2>
            <p><?= esc(lang('App.termsSection3Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.termsSection4Title')) ?></h2>
            <p><?= esc(lang('App.termsSection4Body')) ?></p>
        </article>
    </section>
</main>
<?= $this->endSection() ?>
