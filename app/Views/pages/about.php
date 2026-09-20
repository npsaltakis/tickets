<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper legal-page">
    <section class="legal-hero">
        <p class="legal-kicker"><?= esc(lang('App.footerAbout')) ?></p>
        <h1><?= esc(lang('App.aboutTitle')) ?></h1>
        <p class="subtitle legal-subtitle"><?= esc(lang('App.aboutSubtitle')) ?></p>
    </section>

    <section class="legal-grid">
        <article class="legal-card">
            <h2><?= esc(lang('App.aboutSection1Title')) ?></h2>
            <p><?= esc(lang('App.aboutSection1Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.aboutSection2Title')) ?></h2>
            <p><?= esc(lang('App.aboutSection2Body')) ?></p>
        </article>
        <article class="legal-card">
            <h2><?= esc(lang('App.aboutSection3Title')) ?></h2>
            <p><?= esc(lang('App.aboutSection3Body')) ?> <a href="<?= base_url('contact') ?>"><?= esc(lang('App.footerContact')) ?></a></p>
        </article>
    </section>
</main>
<?= $this->endSection() ?>
