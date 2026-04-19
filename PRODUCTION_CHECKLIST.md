# Production Checklist

Run this checklist before or after deploying changes to production.

## Required Commands

- Run `composer install --no-dev --optimize-autoloader` after pulling code that changes `composer.lock`.
- Run `php spark migrate` after pulling code that includes new migration files.
- Run `php spark migrate:status` to confirm all migrations show as migrated.

## Environment

- Set `CI_ENVIRONMENT = production` when testing is complete.
- Confirm `app.baseURL` points to the public HTTPS URL.
- Confirm PayPal uses live credentials and `PAYPAL_BASE_URL=https://api-m.paypal.com`.
- Confirm SMTP credentials are live and `email.fromEmail` matches the sending account.
- Confirm Turnstile production keys are configured.

## Security

- Make sure the web server document root points to `public/`.
- Make sure `.env`, `writable/`, `app/`, and `vendor/` are not directly public.
- Keep HTTPS enabled, especially for camera-based check-in.
- Rotate secrets if they were shared in chat, screenshots, logs, or commits.

## Smoke Tests

- Register a new user and verify email.
- Send a password reset email and reset the password.
- Book a free event and confirm ticket email.
- Book a donation event through live PayPal and confirm ticket email.
- Open `My Tickets` and export a ticket PDF.
- Log in as admin, open `Check-In`, scan a QR code, and verify duplicate scans show as already used.
- Open `Report`, select an event, and confirm check-in status/checked-in-at values appear.
- Open `Audit Logs` as admin and confirm new admin actions are recorded after the migration runs.
- Edit an event, disable bookings, and confirm the event remains visible but free/PayPal booking is blocked.
