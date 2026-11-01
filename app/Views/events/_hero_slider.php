<?php if (! empty($heroSlides)): ?>
<?php $count = count($heroSlides); ?>
<section class="hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="<?= esc(lang('App.heroSliderLabel'), 'attr') ?>">
    <div class="hero-track">
        <?php foreach (array_values($heroSlides) as $i => $slide): ?>
            <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-hero-slide role="group" aria-roledescription="slide" aria-label="<?= esc(($i + 1) . ' / ' . $count, 'attr') ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
                <?php /* Only the image and the button are shown; the title describes the image for screen readers. */ ?>
                <img class="hero-image" src="<?= esc($slide['image']) ?>" alt="<?= esc($slide['title'], 'attr') ?>" width="1600" height="600" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
                <?php if ($slide['link'] !== '' && $slide['button'] !== ''): ?>
                    <div class="hero-overlay">
                        <a class="hero-cta" href="<?= esc($slide['link'], 'attr') ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?><?= $slide['title'] !== '' ? ' aria-label="' . esc($slide['button'] . ': ' . $slide['title'], 'attr') . '"' : '' ?>><?= esc($slide['button']) ?></a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($count > 1): ?>
        <button type="button" class="hero-arrow hero-arrow--prev" data-hero-prev aria-label="<?= esc(lang('App.heroPrev'), 'attr') ?>">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </button>
        <button type="button" class="hero-arrow hero-arrow--next" data-hero-next aria-label="<?= esc(lang('App.heroNext'), 'attr') ?>">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        </button>
        <div class="hero-dots">
            <?php for ($i = 0; $i < $count; $i++): ?>
                <button type="button" class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>" data-hero-dot="<?= $i ?>" aria-label="<?= esc(lang('App.heroGoTo', [$i + 1]), 'attr') ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
