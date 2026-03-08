(() => {
    const seatsInput = document.getElementById('seats');
    const seatsError = document.getElementById('seats-error');

    if (!seatsInput || seatsInput.disabled) {
        return;
    }

    const minSeats = Number(seatsInput.min || 1);
    const maxSeats = Number(seatsInput.max || 1);
    const limitTemplate = seatsInput.dataset.limitMessage || 'Only {max} seats available.';
    const donationBooking = document.getElementById('donation-booking');
    const bookingError = document.getElementById('booking-error');
    const donationInput = document.getElementById('donation_amount');
    let lastDetailedError = '';

    const setError = (message) => {
        lastDetailedError = message || '';

        if (bookingError) {
            bookingError.textContent = lastDetailedError;
        }
    };

    const validateSeats = () => {
        const rawValue = seatsInput.value;
        let value = Number(rawValue);

        if (!Number.isFinite(value)) {
            value = minSeats;
        }

        if (value < minSeats) {
            value = minSeats;
        }

        if (value > maxSeats) {
            value = maxSeats;
            seatsError.textContent = limitTemplate.replace('{max}', String(maxSeats));
        } else {
            seatsError.textContent = '';
        }

        seatsInput.value = String(value);

        return value;
    };

    seatsInput.addEventListener('input', validateSeats);
    seatsInput.addEventListener('change', validateSeats);

    if (!donationBooking || !donationInput) {
        return;
    }

    const minDonation = Number(donationBooking.dataset.minDonation || 0);
    const minMessageTemplate = donationBooking.dataset.minMessage || 'Minimum donation is {min}.';
    const paypalErrorMessage = donationBooking.dataset.paypalError || 'Something went wrong with PayPal.';
    const createOrderUrl = donationBooking.dataset.createOrderUrl;
    const captureOrderUrl = donationBooking.dataset.captureOrderUrl;

    const readJsonResponse = async (response) => {
        const rawText = await response.text();

        if (!rawText) {
            return {};
        }

        try {
            return JSON.parse(rawText);
        } catch (error) {
            console.error('Invalid JSON response from booking endpoint.', error, rawText);

            return {
                message: paypalErrorMessage,
            };
        }
    };

    const validateDonation = () => {
        const rawValue = donationInput.value.trim();
        const value = Number(rawValue);

        if (!Number.isFinite(value)) {
            setError(minMessageTemplate.replace('{min}', minDonation.toFixed(2)));
            return null;
        }

        if (value < minDonation) {
            setError(minMessageTemplate.replace('{min}', minDonation.toFixed(2)));
            return null;
        }

        setError('');
        donationInput.value = value.toFixed(2);

        return value;
    };

    donationInput.addEventListener('input', () => {
        if (bookingError) {
            bookingError.textContent = '';
        }

        lastDetailedError = '';
    });

    if (!window.paypal || !createOrderUrl || !captureOrderUrl) {
        return;
    }

    window.paypal.Buttons({
        style: {
            layout: 'vertical',
            shape: 'rect',
            label: 'paypal',
        },
        createOrder: async () => {
            const seats = validateSeats();
            const donationAmount = validateDonation();

            if (!seats || donationAmount === null) {
                throw new Error('Validation failed');
            }

            const body = new URLSearchParams();
            body.set('seats', String(seats));
            body.set('donation_amount', donationAmount.toFixed(2));

            const response = await fetch(createOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: body.toString(),
            });

            const data = await readJsonResponse(response);

            if (!response.ok || !data.id) {
                setError(data.message || paypalErrorMessage);
                throw new Error(data.message || paypalErrorMessage);
            }

            setError('');
            return data.id;
        },
        onApprove: async (data) => {
            const paypalOrderId = data?.orderID || data?.orderId || '';

            if (!paypalOrderId) {
                setError('Missing PayPal order id in browser callback.');
                console.error('PayPal onApprove payload missing order id:', data);
                return;
            }

            const body = new URLSearchParams();
            body.set('order_id', paypalOrderId);

            const response = await fetch(captureOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: body.toString(),
            });

            const result = await readJsonResponse(response);

            if (!response.ok || !result.redirectUrl) {
                setError(result.message || paypalErrorMessage);
                return;
            }

            window.location.href = result.redirectUrl;
        },
        onError: (error) => {
            console.error('PayPal checkout error:', error);

            if (!lastDetailedError) {
                setError(paypalErrorMessage);
            }
        },
    }).render('#paypal-button-container');
})();
