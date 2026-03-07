<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <h1>All Events</h1>
    <p class="subtitle">Η κεντρική σελίδα με όλα τα events.</p>

    <?php if (empty($events)): ?>
        <div class="empty">
            No events.
        </div>
    <?php else: ?>
        <section class="grid">
            <?php foreach ($events as $event): ?>
                <?php
                $status = strtolower((string) ($event['status'] ?? 'inactive'));
                $eventUrl = !empty($event['slug']) ? base_url('events/' . $event['slug']) : '#';
                ?>
                <a class="card-link" href="<?= esc($eventUrl) ?>">
                    <article class="card">
                        <?php if (!empty($event['image'])): ?>
                            <?php
                            $rawImage = (string) $event['image'];
                            $imageUrl = preg_match('#^https?://#i', $rawImage) ? $rawImage : base_url(ltrim($rawImage, '/'));
                            ?>
                            <img class="event-image" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>">
                        <?php else: ?>
                            <div class="event-image event-image-placeholder">No image</div>
                        <?php endif; ?>

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
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?= $this->endSection() ?>
