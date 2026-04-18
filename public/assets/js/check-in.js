document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('[data-check-in-scanner]');

    if (!root || typeof window.Html5Qrcode === 'undefined') {
        return;
    }

    var startButton = document.getElementById('check-in-camera-start');
    var stopButton = document.getElementById('check-in-camera-stop');
    var statusNode = document.getElementById('check-in-camera-status');
    var readerNode = document.getElementById('check-in-camera-reader');
    var codeInput = document.getElementById('ticket_code');
    var form = document.getElementById('check-in-form');
    var scanner = null;
    var scannerRunning = false;

    function setStatus(message) {
        if (statusNode) {
            statusNode.textContent = message;
        }
    }

    function setUiState(running) {
        scannerRunning = running;
        if (startButton) {
            startButton.disabled = running;
        }
        if (stopButton) {
            stopButton.disabled = !running;
        }
        if (readerNode) {
            readerNode.hidden = !running;
        }
    }

    async function stopScanner(statusMessage) {
        if (!scanner || !scannerRunning) {
            setUiState(false);
            if (statusMessage) {
                setStatus(statusMessage);
            }
            return;
        }

        try {
            await scanner.stop();
            await scanner.clear();
        } catch (error) {
        }

        setUiState(false);
        if (statusMessage) {
            setStatus(statusMessage);
        }
    }

    async function startScanner() {
        if (scannerRunning) {
            return;
        }

        scanner = new Html5Qrcode('check-in-camera-reader');
        setUiState(true);
        setStatus(root.dataset.cameraReady || 'Camera is ready to scan.');

        try {
            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 240, height: 240 },
                    rememberLastUsedCamera: true,
                    aspectRatio: 1.333334,
                },
                async function (decodedText) {
                    var normalized = String(decodedText || '').trim().toUpperCase();

                    if (!normalized) {
                        return;
                    }

                    if (codeInput) {
                        codeInput.value = normalized;
                    }

                    await stopScanner(root.dataset.cameraStopped || 'Camera stopped.');

                    if (form) {
                        form.requestSubmit();
                    }
                },
                function () {
                }
            );
        } catch (error) {
            await stopScanner(root.dataset.cameraError || 'Camera access failed.');
        }
    }

    if (startButton) {
        startButton.addEventListener('click', function () {
            startScanner();
        });
    }

    if (stopButton) {
        stopButton.addEventListener('click', function () {
            stopScanner(root.dataset.cameraStopped || 'Camera stopped.');
        });
    }

    window.addEventListener('pagehide', function () {
        stopScanner('');
    });
});
