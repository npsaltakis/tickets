(() => {
    const seatsInput = document.getElementById('seats');
    const seatsError = document.getElementById('seats-error');

    if (!seatsInput || seatsInput.disabled) {
        return;
    }

    const min = Number(seatsInput.min || 1);
    const max = Number(seatsInput.max || 1);
    const limitTemplate = seatsInput.dataset.limitMessage || 'Only {max} seats available.';

    const validateSeats = () => {
        const rawValue = seatsInput.value;
        let value = Number(rawValue);

        if (!Number.isFinite(value)) {
            value = min;
        }

        if (value < min) {
            value = min;
        }

        if (value > max) {
            value = max;
            seatsError.textContent = limitTemplate.replace('{max}', String(max));
        } else {
            seatsError.textContent = '';
        }

        seatsInput.value = String(value);
    };

    seatsInput.addEventListener('input', validateSeats);
    seatsInput.addEventListener('change', validateSeats);
})();
