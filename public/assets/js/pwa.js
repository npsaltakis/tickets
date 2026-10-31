(function () {
    var swMeta = document.querySelector('meta[name="sw-url"]');
    var userMeta = document.querySelector('meta[name="pwa-user"]');

    if (!swMeta || !('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register(swMeta.content).catch(function () {});
    });

    // The offline copy of My Tickets (public/sw.js) belongs to one account: drop it when
    // nobody is signed in, or when another account signs in on this device.
    var user = userMeta ? userMeta.content : '';
    var saved = null;
    try {
        saved = window.localStorage.getItem('pwa-user');
        if (user !== '') {
            window.localStorage.setItem('pwa-user', user);
        } else {
            window.localStorage.removeItem('pwa-user');
        }
    } catch (error) {
        saved = null;
    }

    if (window.caches && (user === '' || (saved !== null && saved !== user))) {
        window.caches.delete('user-tickets');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var banner = document.querySelector('[data-offline-banner]');
        if (!banner) {
            return;
        }

        var update = function () {
            banner.hidden = navigator.onLine;
        };
        update();
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
    });
})();
