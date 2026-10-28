# Εγκατάσταση στο live (Plesk + Git, χωρίς SSH)

Domain: `blackboxkoyintaseat.com` · Branch: `release` · Repo: `https://github.com/npsaltakis/tickets.git`

## 1. Plesk: PHP & SSL

- **Ρυθμίσεις PHP**: PHP 8.3 (ή ≥ 8.2), με τα extensions `intl`, `mbstring`, `mysqli`, `gd`, `curl`, `zlib`.
- **SSL/TLS**: Let's Encrypt για το domain (και `www`). Ενεργοποιήστε την ανακατεύθυνση HTTP → HTTPS.

## 2. Plesk: Git

Τοποθεσίες Web & Τομείς → domain → **Git** → Προσθήκη αποθετηρίου:

- **Απομακρυσμένο αποθετήριο**: `https://github.com/npsaltakis/tickets.git`
  - Αν το repo είναι private: χρησιμοποιήστε το SSH URL `git@github.com:npsaltakis/tickets.git` και προσθέστε το public key που δείχνει το Plesk στο GitHub → Settings → Deploy keys (μόνο ανάγνωση).
- **Κλάδος**: `release`
- **Λειτουργία ανάπτυξης**: Χειροκίνητη (για αρχή).
- **Διαδρομή ανάπτυξης**: `/httpdocs`

Πατήστε **Ανάπτυξη**. Ο κώδικας μπαίνει στο `httpdocs/`, χωρίς τα `vendor/` και `.env`.

## 3. Plesk: Document root

Ρυθμίσεις φιλοξενίας → **Ριζικός κατάλογος εγγράφων**: `httpdocs/public`

Έτσι τα `.env`, `app/`, `vendor/` και `writable/` δεν είναι προσβάσιμα από το web.

## 4. vendor/

Επιλογή Α, αν το Plesk έχει ενεργά τα «Πρόσθετες ενέργειες ανάπτυξης» στο Git:

```
/opt/plesk/php/8.3/bin/php /usr/lib/plesk-9.0/composer.phar install --no-dev --optimize-autoloader --no-interaction
```

Επιλογή Β: ανεβάστε με FTP τον φάκελο `deploy/build/vendor/` στο `httpdocs/vendor/`. Χρειάζεται ξανά μόνο όταν αλλάζει το `composer.lock`.

## 5. Βάση

Βάσεις δεδομένων → Προσθήκη βάσης: όνομα βάσης, χρήστης και κωδικός. Οι πίνακες **δεν** δημιουργούνται εδώ, τους φτιάχνει ο installer.

## 6. .env

Διαχείριση αρχείων → `httpdocs/` → νέο αρχείο `.env` με το περιεχόμενο του `deploy/env.production`, συμπληρωμένο:

- `app.baseURL = 'https://blackboxkoyintaseat.com/'`
- στοιχεία της βάσης από το βήμα 5
- `install.token`: ένα τυχαίο κλειδί 16+ χαρακτήρων
- SMTP, Turnstile (προσθέστε το domain στο widget), PayPal **sandbox** για δοκιμές

## 7. Installer

Ανοίξτε `https://blackboxkoyintaseat.com/install`:

1. Όλοι οι έλεγχοι πρέπει να είναι ✓. Αν κάποιος φάκελος `writable/*` δεν είναι εγγράψιμος, δώστε του δικαιώματα 775 από τη Διαχείριση αρχείων.
2. Συμπληρώστε το install token και τα στοιχεία του admin, και πατήστε **Εγκατάσταση**.
3. Δημιουργούνται όλοι οι πίνακες και ο admin. Μετά ο installer κλειδώνει: το `/install` δίνει 404.

## 8. Προγραμματισμένες εργασίες (cron)

Εργαλεία & Ρυθμίσεις / domain → **Προγραμματισμένες εργασίες** → «Εκτέλεση σεναρίου PHP», σενάριο `httpdocs/spark`:

| Ορίσματα | Συχνότητα |
|---|---|
| `emails:process 100` | κάθε 5 λεπτά (`*/5 * * * *`) |
| `reminders:send 48` | ωριαία (`0 * * * *`) |
| `health:check` | κάθε 15 λεπτά (`*/15 * * * *`) |
| `db:backup` | καθημερινά (`30 3 * * *`) |
| `data:purge` | εβδομαδιαία (`0 4 * * 0`) |

## 9. PayPal webhook

Στο PayPal Developer (sandbox app): webhook στο `https://blackboxkoyintaseat.com/paypal/webhook` για τα `PAYMENT.CAPTURE.COMPLETED` και `PAYMENT.CAPTURE.REFUNDED`. Βάλτε το Webhook ID στο `paypal.webhookId` του `.env`.

## 10. Smoke tests

Admin → Dashboard → **Test Email**, εγγραφή νέου χρήστη και επιβεβαίωση email, reset κωδικού, δωρεάν κράτηση, κράτηση με PayPal sandbox, My Tickets, Check-in με QR (θέλει HTTPS), Report, Audit Logs.

## Ενημερώσεις αργότερα

1. Push στο `release`, και στο Plesk → Git → **Ανάπτυξη**.
2. Admin → Dashboard → «Ενημερώσεις βάσης» → **Εκτέλεση migrations**, αν υπάρχουν εκκρεμή (πάρτε backup πρώτα).
3. Αν άλλαξε το `composer.lock`: composer (επιλογή Α) ή ξανά ανέβασμα του `vendor/` (επιλογή Β).
