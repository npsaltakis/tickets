<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php /* An edited About / GDPR / Terms / Privacy page; same layout as the built-in ones. */ ?>
<main class="wrapper legal-page">
    <section class="legal-hero">
        <p class="legal-kicker"><?= esc($kicker) ?></p>
        <h1><?= esc($page['title']) ?></h1>
        <?php if (trim((string) ($page['subtitle'] ?? '')) !== ''): ?>
            <p class="subtitle legal-subtitle"><?= esc($page['subtitle']) ?></p>
        <?php endif; ?>
        <?php if (! empty($page['updated_at'])): ?>
            <p class="legal-updated"><?= esc(lang('App.gdprUpdatedLabel')) ?>: <?= esc(date('d/m/Y', strtotime((string) $page['updated_at']))) ?></p>
        <?php endif; ?>
    </section>

    <section class="legal-grid">
        <?php foreach ($cards as $card): ?>
            <article class="legal-card">
                <?php if ($card['heading'] !== ''): ?>
                    <h2><?= esc($card['heading']) ?></h2>
                <?php endif; ?>
                <?= $card['html'] /* built by PageContent::render(), which escapes all text */ ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>
<?= $this->endSection() ?>
