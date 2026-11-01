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

    // Install page (pages/install_app.php): one-tap install where the browser offers it
    // (Chrome/Edge/Android); Safari has no prompt, so the written steps stay the only way there.
    var installPrompt = null;
    var isInstalled = function () {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    };

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        installPrompt = event;
        var button = document.querySelector('[data-pwa-install]');
        if (button) {
            button.hidden = false;
        }
    });

    window.addEventListener('appinstalled', function () {
        installPrompt = null;
        var button = document.querySelector('[data-pwa-install]');
        var note = document.querySelector('[data-pwa-installed]');
        if (button) {
            button.hidden = true;
        }
        if (note) {
            note.hidden = false;
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.querySelector('[data-pwa-install]');
        var note = document.querySelector('[data-pwa-installed]');

        if (note && isInstalled()) {
            note.hidden = false;
        }

        if (button) {
            button.hidden = installPrompt === null;
            button.addEventListener('click', function () {
                if (!installPrompt) {
                    return;
                }
                installPrompt.prompt();
                installPrompt.userChoice.finally(function () {
                    installPrompt = null;
                    button.hidden = true;
                });
            });
        }
    });

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
