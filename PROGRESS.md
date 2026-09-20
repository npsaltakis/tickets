# Ticketing System — Progress

CodeIgniter 4 event ticketing platform (free + PayPal donation events, Greek/English).

## Implemented

**Accounts & security**
- Register (Turnstile), email verification, login throttling, password reset, session regeneration.
- Roles: `admin`, `staff` (door staff: check-in only), `client`.
- Optional TOTP two-factor authentication (profile → 2FA), 5 attempts per login.
- Profile: name/password, email change with confirmation link, personal data export (JSON), account deletion (anonymisation).
- CSRF, CSP (no inline scripts/handlers — see `public/assets/js/ui-actions.js` and `app-config.js`), secure headers, audit logs.

**Booking**
- Atomic booking with a row lock on the event (`BookingService`) — no overbooking, per-user seat limit (`booking.maxSeatsPerUser`).
- PayPal donations, idempotent capture, discount codes (preview + atomic usage), auto-refund if a paid booking cannot be fulfilled.
- PayPal webhook (`/paypal/webhook`, signature verified) for `PAYMENT.CAPTURE.COMPLETED` / `REFUNDED`.
- Booked seats are final: no customer cancellation and no refunds (whether the customer attends or not). The only automatic refund is a safety net when a payment is captured but the seats sold out at the same moment.
- Waiting list (notified when the event capacity is increased or a refund is reported by PayPal), ticket transfer to another user, printable donation receipt.

**Admin**
- Events CRUD, duplicate, soft delete/restore, bulk actions, categories, discount codes, tickets per event (manual issue, CSV, print QR), users (incl. bulk block/unblock/delete), analytics, audit logs.
- Guards: capacity cannot drop below issued tickets; event type locked while tickets exist; holders are emailed when date/place/access change.
- Check-in with QR scanner (date window checks), staff role.

**Email**
- Bilingual transactional emails, reminders (once per ticket), DB-backed email queue (mass email, event change notices).

**Front-end**
- Search, category + type/format/date filters, calendar view, cookie notice, contact & about pages, SEO/sitemap.

## Scheduled jobs (cron)
- `php spark reminders:send 48` — hourly.
- `php spark emails:process 100` — every few minutes.

## Tests
`vendor/bin/phpunit` — unit tests always run; MySQL-based booking-flow and access-control tests run when `database.tests.*` env vars point to a throw-away MySQL database (CI does this, see `.github/workflows/tests.yml`).

## Not implemented
- Real PayPal sandbox end-to-end run (refund, webhook) — only covered by unit/DB tests.
- Tax invoices (receipt is a plain donation receipt), Apple/Google Wallet passes, multiple ticket types per event, recurring events, error monitoring service, homepage caching.
