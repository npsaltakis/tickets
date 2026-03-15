<?php foreach ($events as $event): ?>
    <?php
    $status = strtolower((string) ($event['status'] ?? 'inactive'));
    $eventUrl = ! empty($event['slug']) ? base_url('events/' . $event['slug']) : '#';
    $startDate = $event['start_date'] ?? null;
    $endDate = $event['end_date'] ?? null;
    $remainingSeats = isset($event['remaining_seats']) ? (int) $event['remaining_seats'] : (int) ($event['capacity'] ?? 0);
    ?>
    <a class="card-link" href="<?= esc($eventUrl) ?>" data-event-card>
        <article class="card">
            <?php if (! empty($event['image'])): ?>
                <?php
                $rawImage = (string) $event['image'];
                $imageUrl = preg_match('#^https?://#i', $rawImage) ? $rawImage : base_url(ltrim($rawImage, '/'));
                ?>
                <img class="event-image" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>">
            <?php else: ?>
                <div class="event-image event-image-placeholder"><?= esc(lang('App.noImage')) ?></div>
            <?php endif; ?>

            <div class="row">
                <h2 class="title"><?= esc($event['title']) ?></h2>
                <span class="status <?= esc($status) ?>"><?= esc($status) ?></span>
            </div>

            <?php if (! empty($startDate)): ?>
                <p class="meta">
                    <?= esc(lang('App.startDate')) ?>: <?= esc(date('d/m/Y H:i', strtotime((string) $startDate))) ?>
                </p>
            <?php endif; ?>

            <?php if (! empty($endDate)): ?>
                <p class="meta">
                    <?= esc(lang('App.endDate')) ?>: <?= esc(date('d/m/Y H:i', strtotime((string) $endDate))) ?>
                </p>
            <?php endif; ?>

            <?php if (! empty($event['location'])): ?>
                <p class="meta"><?= esc(lang('App.location')) ?>: <?= esc($event['location']) ?></p>
            <?php endif; ?>

            <p class="meta"><?= esc(lang('App.seatsRemaining')) ?>: <?= esc((string) $remainingSeats) ?></p>

            <?php if (! empty($event['description'])): ?>
                <?php $description = (string) $event['description']; ?>
                <?php $shortDescription = strlen($description) > 140 ? substr($description, 0, 140) . '...' : $description; ?>
                <p class="meta"><?= esc($shortDescription) ?></p>
            <?php endif; ?>

            <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
                <span class="pill">
                    <?= esc(lang('App.donationFrom')) ?> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?>
                </span>
            <?php else: ?>
                <span class="pill"><?= esc(lang('App.freeEvent')) ?></span>
            <?php endif; ?>
        </article>
    </a>
<?php endforeach; ?>

