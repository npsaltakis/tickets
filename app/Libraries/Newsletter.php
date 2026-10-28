<?php

namespace App\Libraries;

/**
 * Newsletter recipients and the email body (one language, as written by the admin, with an unsubscribe link).
 */
class Newsletter
{
    /**
     * Active users who opted in.
     *
     * @return list<array{id: int|string, email: string, first_name: string, newsletter_token: string}>
     */
    public function subscribers(): array
    {
        return db_connect()->table(db_connect()->prefixTable('users'))
            ->select('id, email, first_name, newsletter_token')
            ->where('newsletter_opt_in', 1)
            ->where('status', 'active')
            ->where('newsletter_token IS NOT NULL', null, false)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function unsubscribeUrl(string $token): string
    {
        return base_url('newsletter/unsubscribe/' . rawurlencode($token));
    }

    public function buildEmail(string $subject, string $message, string $greeting, string $actionUrl, string $buttonLabel, string $unsubscribeUrl, string $unsubscribeText): string
    {
        $paragraphs = '';
        foreach (preg_split('/\R{2,}/', trim($message)) ?: [] as $paragraph) {
            $paragraphs .= '<p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#334155;">' . nl2br(esc(trim($paragraph))) . '</p>';
        }

        $button = $actionUrl === '' ? '' : '<p style="margin:20px 0 6px;"><a href="' . esc($actionUrl, 'attr') . '" style="display:inline-block;padding:13px 18px;border-radius:12px;background:#1d4ed8;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">' . esc($buttonLabel) . '</a></p>';

        return '<div style="font-family:Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);padding:32px 16px;">'
            . '<div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,0.25);">'
            . '<div style="padding:28px 32px;background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%);color:#ffffff;">'
            . '<div style="font-size:12px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;opacity:0.78;">' . esc(lang('App.siteTitle')) . '</div>'
            . '<div style="margin-top:8px;font-size:28px;line-height:1.2;font-weight:700;">' . esc($subject) . '</div>'
            . '</div>'
            . '<div style="padding:32px;">'
            . ($greeting !== '' ? '<h2 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#0f172a;">' . esc($greeting) . '</h2>' : '')
            . $paragraphs
            . $button
            . '</div>'
            . '<div style="padding:18px 32px;border-top:1px solid #e2e8f0;font-size:12px;line-height:1.6;color:#64748b;">'
            . esc($unsubscribeText) . ' <a href="' . esc($unsubscribeUrl, 'attr') . '" style="color:#1d4ed8;">' . esc($unsubscribeUrl) . '</a>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}
