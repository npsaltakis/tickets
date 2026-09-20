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

    public function push(string $to, string $subject, string $htmlBody): void
    {
        $to = trim($to);
        if ($to === '') {
            return;
        }

        db_connect()->table($this->table)->insert([
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

                $error = 'send() returned false';
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
                'last_error' => mb_substr((string) $error, 0, 255),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $sent;
    }
}
