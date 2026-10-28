<?php

namespace App\Libraries;

use Throwable;

/**
 * Small database-backed email queue so bulk sends never depend on one request's time limit.
 * Messages are flushed right away in small batches and the rest is delivered by `php spark emails:process`.
 */
class EmailQueue
{
    public const MAX_ATTEMPTS = 5;

    private string $table;

    public function __construct()
    {
        $this->table = db_connect()->prefixTable('email_queue');
    }

    /**
     * Emails sent per run (and per immediate flush), kept low so the host's outgoing-mail limit is not hit.
     * Tune with `emailQueue.batchSize` in .env.
     */
    public static function batchSize(): int
    {
        return max(1, (int) env('emailQueue.batchSize', 20));
    }

    public function push(string $to, string $subject, string $htmlBody, ?int $campaignId = null): void
    {
        $to = trim($to);
        if ($to === '') {
            return;
        }

        db_connect()->table($this->table)->insert([
            'campaign_id' => $campaignId,
            'to_email'   => $to,
            'subject'    => mb_substr($subject, 0, 255),
            'body'       => $htmlBody,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function pendingCount(): int
    {
        return db_connect()->table($this->table)
            ->where('sent_at', null)
            ->where('attempts <', self::MAX_ATTEMPTS)
            ->countAllResults();
    }

    /**
     * Sends up to $limit pending messages, returning how many were delivered.
     */
    public function flush(int $limit = 30): int
    {
        $db   = db_connect();
        $rows = $db->table($this->table)
            ->where('sent_at', null)
            ->where('attempts <', self::MAX_ATTEMPTS)
            ->orderBy('id', 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        $sent = 0;

        foreach ($rows as $row) {
            $error = null;

            try {
                $mail = service('email');
                $mail->clear();
                $mail->setTo((string) $row['to_email']);
                $mail->setSubject((string) $row['subject']);
                $mail->setMailType('html');
                $mail->setMessage((string) $row['body']);

                if ($mail->send(false)) {
                    $sent++;
                    $db->table($this->table)->where('id', (int) $row['id'])->update([
                        'sent_at'    => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    continue;
                }

                // Keep the mail server's own reply (e.g. a rate-limit or rejected address) instead of a generic text.
                $debug = trim(preg_replace('/\s+/', ' ', strip_tags((string) $mail->printDebugger([]))) ?? '');
                $error = preg_match_all('/\b[45]\d{2}[ -][^<]{3,160}?(?=\s(?:[1-5]\d{2}[ -]|\w+:|The following|Unable to)|$)/', $debug, $replies) > 0
                    ? (string) end($replies[0])
                    : ($debug !== '' ? $debug : 'send() returned false');
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }

            log_message('error', 'Queued email {id} to {to} failed: {error}', [
                'id'    => $row['id'],
                'to'    => $row['to_email'],
                'error' => $error,
            ]);

            $db->table($this->table)->where('id', (int) $row['id'])->update([
                'attempts'   => (int) $row['attempts'] + 1,
                // The server's error is at the end of the SMTP transcript, so keep the tail.
                'last_error' => mb_strlen((string) $error) > 255 ? '…' . mb_substr((string) $error, -254) : (string) $error,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $sent;
    }
}
