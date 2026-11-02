<?php

namespace App\Libraries;

use Throwable;

/**
 * Notifications for each signed-in user: shown under the bell in the site header and on /notifications.
 *
 * Like AdminNotifications, a row stores its type and the values its text needs; the text comes from the
 * App.unotif_<type> language line when it is shown. Each row belongs to one user and has its own read_at.
 * Recording never breaks the action that caused it: errors (e.g. table not migrated yet) are swallowed.
 */
class UserNotifications
{
    public const TYPES = [
        'booking_confirmed', 'ticket_received', 'event_updated', 'event_cancelled', 'waitlist_seat', 'event_reminder',
    ];

    /**
     * Notifications older than this are removed when a user marks theirs as read.
     */
    private const KEEP_DAYS = 180;

    /**
     * @param list<int>                  $userIds Each user gets their own row; duplicates and 0 are skipped.
     * @param array<string, scalar|null> $data    Values for the {placeholders} of App.unotif_<type>.
     */
    public static function add(array $userIds, string $type, array $data = [], ?string $link = null): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return;
        }

        $now  = date('Y-m-d H:i:s');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $rows = array_map(static fn (int $userId) => [
            'user_id'    => $userId,
            'type'       => $type,
            'data'       => $json,
            'link'       => $link,
            'created_at' => $now,
        ], $userIds);

        try {
            db_connect()->table('user_notifications')->insertBatch($rows);
        } catch (Throwable $exception) {
            log_message('warning', 'User notification not recorded: {message}', ['message' => $exception->getMessage()]);
        }
    }

    /**
     * Newest first, each with 'text' and 'unread' added.
     *
     * @return list<array<string, mixed>>
     */
    public static function latest(int $userId, int $limit, int $offset = 0): array
    {
        try {
            $rows = db_connect()->table('user_notifications')
                ->where('user_id', $userId)
                ->orderBy('id', 'DESC')
                ->limit($limit, $offset)
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['text']   = self::text($row);
            $row['unread'] = empty($row['read_at']);
        }

        return $rows;
    }

    public static function total(int $userId): int
    {
        try {
            return db_connect()->table('user_notifications')->where('user_id', $userId)->countAllResults();
        } catch (Throwable) {
            return 0;
        }
    }

    public static function unreadCount(int $userId): int
    {
        try {
            return db_connect()->table('user_notifications')->where('user_id', $userId)->where('read_at', null)->countAllResults();
        } catch (Throwable) {
            return 0;
        }
    }

    public static function markAllRead(int $userId): void
    {
        try {
            $db = db_connect();
            $db->table('user_notifications')->where('user_id', $userId)->where('read_at', null)->update(['read_at' => date('Y-m-d H:i:s')]);
            $db->table('user_notifications')->where('user_id', $userId)->where('created_at <', date('Y-m-d H:i:s', strtotime('-' . self::KEEP_DAYS . ' days')))->delete();
        } catch (Throwable) {
            // Read state is a convenience; nothing to undo.
        }
    }

    /**
     * The notification's sentence in the current language, with its values filled in.
     */
    public static function text(array $row): string
    {
        $type = (string) ($row['type'] ?? '');
        if (! in_array($type, self::TYPES, true)) {
            return $type;
        }

        $data    = json_decode((string) ($row['data'] ?? ''), true);
        $replace = [];
        foreach (is_array($data) ? $data : [] as $key => $value) {
            $replace['{' . $key . '}'] = (string) $value;
        }

        return strtr(lang('App.unotif_' . $type), $replace);
    }
}
