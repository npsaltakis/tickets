<?php

namespace App\Libraries;

use Throwable;

/**
 * Notifications for admins: shown under the bell in the admin topbar and in Admin → Notifications.
 *
 * A notification stores its type and the values its text needs (data); the text itself comes from the
 * App.notif_<type> language line when it is shown, so each admin reads it in their own language.
 * Read state is per admin (users.notifications_seen_at): anything created after it is unread.
 * Recording never breaks the action that caused it: errors (e.g. table not migrated yet) are swallowed.
 */
class AdminNotifications
{
    public const TYPES = [
        'user_registered', 'user_deleted',
        'event_created', 'event_cancelled', 'event_deleted', 'event_full', 'event_almost_full',
        'booking_new', 'contact_message',
        'backup_created', 'backup_failed', 'backup_restored', 'restore_failed',
    ];

    /**
     * Notifications older than this are removed when an admin marks them as read.
     */
    private const KEEP_DAYS = 180;

    /**
     * @param array<string, scalar|null> $data Values for the {placeholders} of App.notif_<type>.
     */
    public static function add(string $type, array $data = [], ?string $link = null): void
    {
        try {
            $actorId = (int) (session()->get('user_id') ?? 0);
            db_connect()->table('admin_notifications')->insert([
                'type'       => $type,
                'data'       => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'link'       => $link,
                'actor_id'   => $actorId > 0 ? $actorId : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            log_message('warning', 'Admin notification not recorded: {message}', ['message' => $exception->getMessage()]);
        }
    }

    /**
     * Newest first, each with 'text' and 'unread' added for the given admin.
     *
     * @return list<array<string, mixed>>
     */
    public static function latest(int $userId, int $limit, int $offset = 0): array
    {
        try {
            $rows = db_connect()->table('admin_notifications')
                ->orderBy('id', 'DESC')
                ->limit($limit, $offset)
                ->get()
                ->getResultArray();
            $seenAt = self::seenAt($userId);
        } catch (Throwable) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['text']   = self::text($row);
            $row['unread'] = $seenAt === null || (string) $row['created_at'] > $seenAt;
        }

        return $rows;
    }

    public static function total(): int
    {
        try {
            return db_connect()->table('admin_notifications')->countAllResults();
        } catch (Throwable) {
            return 0;
        }
    }

    public static function unreadCount(int $userId): int
    {
        try {
            $builder = db_connect()->table('admin_notifications');
            $seenAt  = self::seenAt($userId);
            if ($seenAt !== null) {
                $builder->where('created_at >', $seenAt);
            }

            return $builder->countAllResults();
        } catch (Throwable) {
            return 0;
        }
    }

    public static function markAllRead(int $userId): void
    {
        try {
            $db = db_connect();
            $db->table('users')->where('id', $userId)->update(['notifications_seen_at' => date('Y-m-d H:i:s')]);
            $db->table('admin_notifications')->where('created_at <', date('Y-m-d H:i:s', strtotime('-' . self::KEEP_DAYS . ' days')))->delete();
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

        return strtr(lang('App.notif_' . $type), $replace);
    }

    private static function seenAt(int $userId): ?string
    {
        $row = db_connect()->table('users')->select('notifications_seen_at')->where('id', $userId)->get()->getRowArray();
        $seen = (string) ($row['notifications_seen_at'] ?? '');

        return $seen !== '' ? $seen : null;
    }
}
