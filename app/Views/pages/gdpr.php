<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper legal-page">
    <section class="legal-hero">
        <p class="legal-kicker">GDPR</p>
        <h1><?= esc(lang('App.gdprTitle')) ?></h1>
        <p class="subtitle legal-subtitle"><?= esc(lang('App.gdprSubtitle')) ?></p>
        <p class="legal-updated"><?= esc(lang('App.gdprUpdatedLabel')) ?>: <?= esc(lang('App.gdprUpdatedDate')) ?></p>
    </section>

    <section class="legal-grid">
        <article class="legal-card">
            <h2><?= esc(lang('App.gdprIntroTitle')) ?></h2>
            <p><?= esc(lang('App.gdprIntroBody')) ?></p>
        </article>

        <article class="legal-card">
            <h2><?= esc(lang('App.gdprDataTitle')) ?></h2>
            <p><?= esc(lang('App.gdprDataBody')) ?></p>
        </article>

        <article class="legal-card">
            <h2><?= esc(lang('App.gdprPurposeTitle')) ?></h2>
            <p><?= esc(lang('App.gdprPurposeBody')) ?></p>
        </article>

        <article class="legal-card">
            <h2><?= esc(lang('App.gdprLawfulBasisTitle')) ?></h2>
            <p><?= esc(lang('App.gdprLawfulBasisBody')) ?></p>
        </article>

        <article class="legal-card">
            <h2><?= esc(lang('App.gdprRetentionTitle')) ?></h2>
            <p><?= esc(lang('App.gdprRetentionBody')) ?></p>
        </article>

        <article class="legal-card">
            <h2><?= esc(lang('App.gdprRightsTitle')) ?></h2>
            <p><?= esc(lang('App.gdprRightsBody')) ?></p>
        </article>
    </section>

    <section class="legal-card legal-card-wide">
        <h2><?= esc(lang('App.gdprContactTitle')) ?></h2>
        <p><?= esc(lang('App.gdprContactBody')) ?></p>
    </section>
</main>
<?= $this->endSection() ?>
