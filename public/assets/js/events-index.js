(() => {
    const grid = document.getElementById('events-grid');
    const sentinel = document.getElementById('events-scroll-sentinel');
    const status = document.getElementById('events-scroll-status');
    const statusText = document.getElementById('events-scroll-text');
    const searchInput = document.getElementById('events-search');
    const searchHint = document.getElementById('events-search-hint');
    const searchEmpty = document.getElementById('events-search-empty');

    if (!grid || !sentinel || !status || !statusText || !searchInput) {
        return;
    }

    const batchSize = Number.parseInt(grid.dataset.batchSize || '12', 10);
    const searchMinLength = Number.parseInt(searchInput.dataset.minLength || '3', 10);
    const feedUrl = grid.dataset.feedUrl || '';
    const searchEmptyLabel = grid.dataset.searchEmptyLabel || '';
    const defaultStatusLabel = status.dataset.loadLabel || statusText.textContent;
    const doneStatusLabel = status.dataset.doneLabel || statusText.textContent;

    let activeQuery = '';
    let nextOffset = Number.parseInt(grid.dataset.initialCount || '0', 10);
    let hasMore = grid.dataset.hasMore === '1';
    let loading = false;
    let requestToken = 0;
    let abortController = null;
    let searchDebounceId = 0;

    const syncScrollUi = () => {
        const hasCards = grid.children.length > 0;

        if (!hasCards) {
            status.classList.add('is-hidden');
            sentinel.classList.add('is-hidden');
            return;
        }

        status.classList.remove('is-hidden');

        if (loading) {
            status.classList.remove('is-finished');
            status.classList.add('is-loading');
            statusText.textContent = defaultStatusLabel;
            sentinel.classList.add('is-hidden');
            return;
        }

        status.classList.remove('is-loading');

        if (hasMore) {
            status.classList.remove('is-finished');
            statusText.textContent = defaultStatusLabel;
            sentinel.classList.remove('is-hidden');
            return;
        }

        status.classList.add('is-finished');
        statusText.textContent = doneStatusLabel;
        sentinel.classList.add('is-hidden');
    };

    const setEmptyState = (visible) => {
        if (!searchEmpty) {
            return;
        }

        searchEmpty.textContent = searchEmptyLabel;
        searchEmpty.classList.toggle('is-hidden', !visible);
    };

    const applyQueryState = (query) => {
        if (query === '') {
            searchHint?.classList.remove('is-hidden');
            return;
        }

        searchHint?.classList.add('is-hidden');
    };

    const fetchBatch = async ({ reset = false, query = '' } = {}) => {
        if (!feedUrl || (!reset && (loading || !hasMore))) {
            return;
        }

        if (reset && abortController) {
            abortController.abort();
        }

        loading = true;
        const token = ++requestToken;
        abortController = new AbortController();
        const offset = reset ? 0 : nextOffset;
        applyQueryState(query);
        setEmptyState(false);
        syncScrollUi();

        const params = new URLSearchParams({
            offset: String(offset),
            limit: String(batchSize),
        });

        if (query !== '') {
            params.set('q', query);
        }

        try {
            const response = await fetch(`${feedUrl}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                signal: abortController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            if (token !== requestToken) {
                return;
            }

            if (reset) {
                grid.innerHTML = payload.html || '';
            } else {
                grid.insertAdjacentHTML('beforeend', payload.html || '');
            }

            nextOffset = Number.parseInt(String(payload.nextOffset ?? 0), 10);
            hasMore = Boolean(payload.hasMore);
            activeQuery = query;
            setEmptyState(grid.children.length === 0 && query !== '');
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Event feed fetch failed', error);
            }
        } finally {
            if (token === requestToken) {
                loading = false;
                abortController = null;
                syncScrollUi();
            }
        }
    };

    const handleSearchInput = () => {
        const rawValue = searchInput.value.trim();
        window.clearTimeout(searchDebounceId);

        searchDebounceId = window.setTimeout(() => {
            if (rawValue !== '' && rawValue.length < searchMinLength) {
                if (activeQuery !== '' || nextOffset !== Number.parseInt(grid.dataset.initialCount || '0', 10)) {
                    hasMore = true;
                    fetchBatch({ reset: true, query: '' });
                } else {
                    applyQueryState('');
                    setEmptyState(false);
                    syncScrollUi();
                }
                return;
            }

            const nextQuery = rawValue.length >= searchMinLength ? rawValue : '';
            if (nextQuery === activeQuery && nextQuery !== '') {
                return;
            }

            hasMore = true;
            fetchBatch({ reset: true, query: nextQuery });
        }, 250);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                fetchBatch({ query: activeQuery });
            }
        });
    }, {
        rootMargin: '240px 0px',
    });

    observer.observe(sentinel);
    syncScrollUi();
    searchInput.addEventListener('input', handleSearchInput);
})();

