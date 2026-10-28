# Εγκατάσταση στο live (Plesk + Git, χωρίς SSH)

Domain: `blackboxkoyintaseat.com` · Branch: `release` · Repo: `https://github.com/npsaltakis/tickets.git` (public, χωρίς διαπιστευτήρια)

Ο φάκελος του site στο Plesk είναι `blackboxkoyintaseat.com/`. Όλες οι διαδρομές παρακάτω είναι μέσα σε αυτόν.

1. **Git**: κλάδος `release`, «Ανάπτυξη».
2. **Ρυθμίσεις φιλοξενίας**: ριζικός κατάλογος `blackboxkoyintaseat.com/public`, SSL Let's Encrypt, ανακατεύθυνση HTTP → HTTPS. PHP ≥ 8.2.
3. **vendor/**: ανέβασμα με FTP του `deploy/build/vendor/` στο `vendor/`. Ξανά μόνο όταν αλλάζει το `composer.lock`.
4. **Βάση**: Βάσεις δεδομένων → νέα βάση + χρήστης.
5. **Installer**: `https://blackboxkoyintaseat.com/install`. Συμπληρώνεις URL, βάση και admin. Γράφει μόνος του το `.env`, φτιάχνει τους πίνακες και τον admin, και μετά κλειδώνει (404).
   Κάν' το αμέσως μετά το deploy: μέχρι να ολοκληρωθεί, η σελίδα είναι ανοιχτή σε όποιον τη βρει.
6. **.env**: Διαχείριση αρχείων → `.env`. Ξεσχολίασε και συμπλήρωσε email (SMTP), Turnstile και PayPal (sandbox για δοκιμές).
7. **Cron** (Προγραμματισμένες εργασίες → σενάριο PHP `blackboxkoyintaseat.com/spark`):

| Ορίσματα | Συχνότητα |
|---|---|
| `emails:process 100` | `*/5 * * * *` |
| `reminders:send 48` | `0 * * * *` |
| `health:check` | `*/15 * * * *` |
| `db:backup` | `30 3 * * *` |
| `data:purge` | `0 4 * * 0` |

8. **PayPal webhook**: `https://blackboxkoyintaseat.com/paypal/webhook` (`PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.REFUNDED`) → `paypal.webhookId` στο `.env`.

## Ενημερώσεις

Push στο `release` → Plesk Git «Ανάπτυξη» → Admin Dashboard → «Εκτέλεση migrations» αν υπάρχουν εκκρεμή.
