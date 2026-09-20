// The CSP forbids inline scripts, so server values reach JS through <meta name="base-url">.
(function () {
    const meta = document.querySelector('meta[name="base-url"]');
    window.baseUrl = meta ? meta.getAttribute('content') : '/';
})();
