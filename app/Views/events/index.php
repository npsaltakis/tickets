<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <h1>All Events</h1>
    <p class="subtitle">Η κεντρική σελίδα με όλα τα events.</p>

    <?php if (empty($events)): ?>
        <div class="empty">
            Δεν υπάρχουν καταχωρημένα events.
        </div>
    <?php else: ?>
        <section class="grid">
            <?php foreach ($events as $event): ?>
                <?php $status = strtolower((string) ($event['status'] ?? 'inactive')); ?>
                <article class="card">
                    <div class="row">
                        <h2 class="title"><?= esc($event['title']) ?></h2>
                        <span class="status <?= esc($status) ?>"><?= esc($status) ?></span>
                    </div>

                    <p class="meta">
                        Date: <?= esc(date('d/m/Y H:i', strtotime($event['event_date']))) ?>
                    </p>

                    <?php if (!empty($event['location'])): ?>
                        <p class="meta">Location: <?= esc($event['location']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($event['description'])): ?>
                        <?php $description = (string) $event['description']; ?>
                        <?php $shortDescription = strlen($description) > 140 ? substr($description, 0, 140) . '...' : $description; ?>
                        <p class="meta"><?= esc($shortDescription) ?></p>
                    <?php endif; ?>

                    <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
                        <span class="pill">
                            Donation from €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?>
                        </span>
                    <?php else: ?>
                        <span class="pill">Free Event</span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?= $this->endSection() ?>
