# Tickets

Event ticketing platform built with CodeIgniter 4.

## Overview

The application supports:

- public event listing
- user registration and login
- email verification on registration
- free event booking
- donation-based booking with PayPal
- user ticket history
- admin event creation and editing
- admin ticket reporting

## Main Features

### Authentication

- user registration
- login / logout
- email verification after registration
- forgot-password flow
- Cloudflare Turnstile on registration

### Events

- homepage shows only active events
- event detail page with booking flow
- admin create event
- admin edit event
- auto-generated slug from title
- image URL or file upload support

### Booking

#### Free events

- user selects seat count
- tickets are issued immediately
- confirmation email is sent after booking

#### Donation events

- user selects seat count and donation amount
- minimum donation is enforced per event
- PayPal create/capture flow is used
- tickets and payments are stored only after successful capture
- confirmation email is sent after payment

### User Area

- `My Events` / `My Tickets` page
- grouped tickets per event
- ticket codes and booking details

### Admin Area

- event management from admin-visible actions
- `Report` page in top navigation
- `Ticket Report` tab with per-event summary
- `Ticket Codes` tab with event-specific codes list
- DataTables export to Excel and PDF

## Event Fields

Current event form includes:

- title
- slug (generated from title)
- description
- image URL/path or uploaded image
- location
- address
- capacity
- start date and time
- end date and time
- event type (`free` or `donation`)
- minimum donation
- status
- information phone (optional)
- information URL (optional)

## Tech Stack

- PHP 8.2+
- CodeIgniter 4
- MySQL / MariaDB
- jQuery DataTables
- PayPal Orders API
- Cloudflare Turnstile
- Mailpit for local email testing

## Local Setup

1. Install dependencies:

```bash
composer install
```

2. Create your environment file:

```bash
copy env .env
```

3. Configure in `.env`:

- base URL
- database connection
- email settings
- Turnstile keys
- PayPal keys

4. Run migrations:

```bash
php spark migrate
```

5. Start the local server:

```bash
php spark serve
```

## Environment Variables

### App / Database

Configure your normal CodeIgniter app and database settings in `.env`.

### Turnstile

```env
turnstile.siteKey =
turnstile.secretKey =
```

### PayPal

The code currently supports both naming styles below:

```env
paypal.clientId =
paypal.secret =
paypal.baseUrl = https://api-m.sandbox.paypal.com
```

or

```env
paypal_clientId =
paypal_secret =
PAYPAL_BASE_URL = https://api-m.sandbox.paypal.com
```

Production PayPal base URL:

```env
PAYPAL_BASE_URL = https://api-m.paypal.com
```

## Local Email Testing

For local development, use Mailpit as SMTP catcher.

Registration verification emails, password reset emails, and booking confirmation emails can all be tested there.

## Important Routes

- `/` homepage events listing
- `/login` login page
- `/register` registration page
- `/verify-email` email verification endpoint
- `/events/{slug}` event detail page
- `/events/create` admin create event
- `/events/{slug}/edit` admin edit event
- `/my-events` user tickets page
- `/report` admin report page

## Reporting

The admin report page includes:

- event-level ticket summary
- detailed ticket codes per selected event
- DataTables pagination
- rows-per-page selector
- Excel export
- PDF export

## Notes

- uploaded event images are stored under `public/assets/images/{user_id}/`
- page-specific JavaScript lives under `public/assets/js`
- local project notes are kept in ignored files under `docs/`
