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

    public function createCampaign(string $subject, ?int $eventId, int $createdBy): int
    {
        $db = db_connect();
        $db->table($db->prefixTable('newsletter_campaigns'))->insert([
            'subject'    => mb_substr($subject, 0, 255),
            'event_id'   => $eventId,
            'recipients' => 0,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    public function setRecipients(int $campaignId, int $recipients): void
    {
        $db = db_connect();
        $db->table($db->prefixTable('newsletter_campaigns'))->where('id', $campaignId)->update([
            'recipients' => $recipients,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Latest campaigns with sent / pending / failed counts from the email queue.
     *
     * @return list<array<string, mixed>>
     */
    public function campaigns(int $limit = 50): array
    {
        $db       = db_connect();
        $campaign = $db->prefixTable('newsletter_campaigns');
        $queue    = $db->prefixTable('email_queue');
        $max      = EmailQueue::MAX_ATTEMPTS;

        return $db->query(
            "SELECT c.id, c.subject, c.recipients, c.created_at,
                    SUM(q.sent_at IS NOT NULL) AS sent,
                    SUM(q.sent_at IS NULL AND q.attempts < ?) AS pending,
                    SUM(q.sent_at IS NULL AND q.attempts >= ?) AS failed
             FROM {$campaign} c
             LEFT JOIN {$queue} q ON q.campaign_id = c.id
             GROUP BY c.id, c.subject, c.recipients, c.created_at
             ORDER BY c.id DESC
             LIMIT ?",
            [$max, $max, $limit]
        )->getResultArray();
    }

    public function campaign(int $campaignId): ?array
    {
        $db = db_connect();

        return $db->table($db->prefixTable('newsletter_campaigns'))->where('id', $campaignId)->get()->getRowArray();
    }

    /**
     * Recipients of one campaign, optionally only 'sent', 'pending' or 'failed'.
     *
     * @return list<array<string, mixed>>
     */
    public function recipients(int $campaignId, string $status = ''): array
    {
        $db      = db_connect();
        $builder = $db->table($db->prefixTable('email_queue'))
            ->select('id, to_email, attempts, last_error, sent_at, updated_at')
            ->where('campaign_id', $campaignId);

        if ($status === 'sent') {
            $builder->where('sent_at IS NOT NULL', null, false);
        } elseif ($status === 'pending') {
            $builder->where('sent_at', null)->where('attempts <', EmailQueue::MAX_ATTEMPTS);
        } elseif ($status === 'failed') {
            $builder->where('sent_at', null)->where('attempts >=', EmailQueue::MAX_ATTEMPTS);
        }

        return $builder->orderBy('id', 'ASC')->get()->getResultArray();
    }

    /**
     * Puts the failed emails of a campaign back in the queue; returns how many.
     */
    public function retryFailed(int $campaignId): int
    {
        $db = db_connect();
        $db->table($db->prefixTable('email_queue'))
            ->where('campaign_id', $campaignId)
            ->where('sent_at', null)
            ->where('attempts >=', EmailQueue::MAX_ATTEMPTS)
            ->update(['attempts' => 0, 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')]);

        return $db->affectedRows();
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
