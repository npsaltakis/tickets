(() => {
    const grid = document.getElementById('events-grid');
    const sentinel = document.getElementById('events-scroll-sentinel');
    const status = document.getElementById('events-scroll-status');
    const statusText = document.getElementById('events-scroll-text');

    if (!grid || !sentinel || !status || !statusText) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll('[data-event-card]'));
    const batchSize = Number.parseInt(grid.dataset.batchSize || '12', 10);
    let visibleCount = cards.filter((card) => !card.classList.contains('is-lazy-hidden')).length;
    let loading = false;

    const loadNextBatch = () => {
        if (loading || visibleCount >= cards.length) {
            if (visibleCount >= cards.length) {
                status.classList.add('is-finished');
                statusText.textContent = status.dataset.doneLabel || statusText.textContent;
                sentinel.classList.add('is-hidden');
            }
            return;
        }

        loading = true;
        status.classList.add('is-loading');

        window.requestAnimationFrame(() => {
            const nextCards = cards.slice(visibleCount, visibleCount + batchSize);
            nextCards.forEach((card, index) => {
                window.setTimeout(() => {
                    card.classList.remove('is-lazy-hidden');
                    card.classList.add('is-lazy-visible');
                }, index * 40);
            });

            visibleCount += nextCards.length;
            loading = false;
            status.classList.remove('is-loading');

            if (visibleCount >= cards.length) {
                status.classList.add('is-finished');
                statusText.textContent = status.dataset.doneLabel || statusText.textContent;
                sentinel.classList.add('is-hidden');
            }
        });
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                loadNextBatch();
            }
        });
    }, {
        rootMargin: '240px 0px'
    });

    observer.observe(sentinel);
})();
