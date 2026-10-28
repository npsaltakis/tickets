# Εγκατάσταση σε νέο πελάτη (Plesk + Git, χωρίς SSH)

Αντικατέστησε το `<domain>` με το domain του πελάτη (π.χ. `example.gr`).
Στο Plesk ο φάκελος του site είναι συνήθως `<domain>/` ή `httpdocs/`. Όλες οι διαδρομές παρακάτω είναι μέσα σε αυτόν.

## 1. Plesk

1. **Git** → Προσθήκη αποθετηρίου: `https://github.com/npsaltakis/tickets.git`, κλάδος `release`, διαδρομή ο φάκελος του site.
   Μετά από κάθε push: «Εξαγωγή τώρα» → «Ανάπτυξη τώρα».
2. **Ρυθμίσεις φιλοξενίας**: ριζικός κατάλογος `<φάκελος>/public`, PHP 8.3, SSL (Let's Encrypt), HTTP → HTTPS, HSTS.
3. **PHP Composer**: «Εγκατάσταση» (production). Αλλιώς ανέβασμα του `vendor/` με FTP.
4. **Βάσεις δεδομένων**: νέα βάση + χρήστης.

## 2. Installer

Άνοιξε `https://<domain>/install` και συμπλήρωσε:
- URL site, **όνομα site**, email επικοινωνίας
- στοιχεία βάσης
- λογαριασμό admin

Ο installer γράφει το `.env`, φτιάχνει τους πίνακες και τον admin, και κλειδώνει. Κάν' το αμέσως μετά το deploy.

## 3. Ρυθμίσεις μετά την εγκατάσταση

- **Admin → Ρυθμίσεις Site**: λογότυπο, favicon, χρώμα, footer, επικοινωνία, στοιχεία εταιρείας.
- **`.env`** (Διαχείριση αρχείων, δίπλα στο `app/`, όχι στο `public/`): ξεσχολίασε και συμπλήρωσε
  - SMTP (`email.*`, `ADMIN_NOTIFY_EMAIL`). Αν το πιστοποιητικό του mail server είναι του hosting, βάλε στο `email.SMTPHost` το όνομα του server (π.χ. `linuxzoneXX.grserver.gr`), όχι το domain.
  - Cloudflare Turnstile (`turnstile.siteKey`, `turnstile.secretKey`): widget με hostname `<domain>`.
  - Προαιρετικά: PayPal, Google σύνδεση, Google Wallet (παρακάτω).

## 4. Cron (Plesk → Προγραμματισμένες εργασίες → «Εκτέλεση σεναρίου PHP», σενάριο `<φάκελος>/spark`)

| Ορίσματα | Cron |
|---|---|
| `emails:process` | `*/5 * * * *` |
| `reminders:send` | `0 * * * *` |
| `health:check` | `*/15 * * * *` |
| `db:backup` | `30 3 * * *` |
| `data:purge` | `0 4 * * 0` |

Ρυθμός αποστολής emails: `emailQueue.batchSize` στο `.env` (προεπιλογή 20 ανά εκτέλεση).

## 5. Προαιρετικές συνδέσεις

- **Σύνδεση με Google**: Google Cloud → OAuth client (Web), redirect `https://<domain>/auth/google/callback` → `google.clientId`, `google.clientSecret`.
- **Google Wallet**: Wallet console (Issuer ID) + service account με κλειδί JSON στο `<φάκελος>/keys/wallet.json` → `googleWallet.issuerId`, `googleWallet.keyFile = keys/wallet.json`.
- **PayPal**: `paypal.clientId`, `paypal.secret`, `PAYPAL_BASE_URL`, webhook `https://<domain>/paypal/webhook` → `paypal.webhookId`.
- **Λίστα αναμονής**: κλειστή από προεπιλογή, ανοίγει με `waitlist.enabled = true`.

## Ενημερώσεις

Push στο `release` → Plesk Git «Ανάπτυξη» → Admin → Dashboard → «Εκτέλεση migrations» αν υπάρχουν.
Αν άλλαξε το `composer.lock`: Plesk → PHP Composer → «Εγκατάσταση».
