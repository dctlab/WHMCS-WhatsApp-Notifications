<?php

namespace Lkn\HookNotification\Notifications\Custom;

use DateTime;
use WHMCS\Database\Capsule;

/**
 * Shared ticket data resolution, reused by TicketOpenNotification,
 * TicketRepliedNotification, AdminTicketOpenedNotification, and
 * AdminTicketUserRepliedNotification.
 *
 * This is a TRAIT, not a class: the module's Custom/*.php auto-discovery
 * instantiates every newly-declared CLASS in this folder as a notification,
 * so shared logic here must be a trait (or interface) to be safely skipped,
 * the same way ResendableNotificationInterface already is.
 *
 * @since 4.5.20
 */
trait TicketNotificationPayloadTrait
{
    /**
     * Builds the full parameter payload for a single ticket, fresh from the
     * database.
     *
     * @param string|null $replyMessage   The reply text, when this is about a specific reply
     *                                    (TicketAdminReply/TicketUserReply). Null for TicketOpen.
     * @param string|null $repliedByAdmin The staff member's name, when relevant. Null otherwise.
     *
     * @return array<mixed>|null null if the ticket no longer exists.
     */
    private function buildPayloadForTicket(
        int $ticketId,
        ?DateTime $eventAt = null,
        ?string $replyMessage = null,
        ?string $repliedByAdmin = null,
    ): ?array {
        $ticket = Capsule::table('tbltickets')->where('id', $ticketId)->first();

        if ($ticket === null) {
            return null;
        }

        $eventAt = $eventAt ?? new DateTime();

        $systemUrl = rtrim((string) Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');
        $ticketLink = $systemUrl !== ''
            ? $systemUrl . '/supporttickets.php?action=view&id=' . $ticket->id
            : '';

        $department = Capsule::table('tblticketdepartments')->where('id', $ticket->did)->value('name');

        $hour   = (int) $eventAt->format('G');
        $minute = (int) $eventAt->format('i');

        return [
            'client_id'            => $ticket->userid ?: 0,
            'report_category_id'  => $ticket->id,
            'ticket_id'            => $ticket->tid ?: (string) $ticket->id,
            'ticket_system_id'     => $ticket->id,
            'ticket_subject'       => $ticket->title ?: 'N/A',
            'ticket_link'          => $ticketLink ?: 'N/A',
            'ticket_department'    => $department ?: 'N/A',
            'ticket_priority'      => $ticket->priority ?: 'N/A',
            // WhatsApp rejects empty template parameters, so these fall back
            // to a placeholder rather than blank when there's no reply yet
            // (e.g. on the "ticket just opened" notifications).
            'reply_message'        => $replyMessage ?: 'N/A',
            'replied_by'           => $repliedByAdmin ?: 'N/A',
            'event_hour'           => (string) $hour,
            'event_minute'         => (string) $minute,
            'event_total_minutes'  => (string) ($hour * 60 + $minute),
            'event_timestamp'      => $eventAt->format('Y-m-d H:i:s'),
            'event_timestamp_whmcs' => $this->formatDateTimeUsingWhmcsFormat($eventAt),
        ];
    }

    /**
     * Resolves the latest reply on a ticket (or a specific one, when
     * `$replyId` is known), for the "Reply Message Content" /
     * "Staff Responded The Ticket" parameters.
     *
     * @return array{message: string, admin: ?string}|null
     */
    private function resolveTicketReply(int $ticketId, ?int $replyId = null): ?array
    {
        // tblticketreplies links back to tbltickets.id via the `tid` column,
        // not `ticketid` (that name only exists in WHMCS's hook params, not
        // in this table's actual schema).
        $query = Capsule::table('tblticketreplies')->where('tid', $ticketId);

        if ($replyId) {
            $query->where('id', $replyId);
        } else {
            $query->orderBy('date', 'desc');
        }

        $reply = $query->first();

        if ($reply === null) {
            return null;
        }

        return [
            'message' => strip_tags((string) $reply->message),
            // tblticketreplies.admin already stores the staff member's name/username
            // directly (blank when the reply was from the client, not staff).
            'admin' => !empty($reply->admin) ? $reply->admin : null,
        ];
    }

    /**
     * Formats a DateTime using the date format configured in WHMCS's own
     * General Settings (Setup > General Settings > Localisation > Date Format),
     * plus the time (H:i).
     */
    private function formatDateTimeUsingWhmcsFormat(DateTime $dt): string
    {
        $whmcsDateFormat = Capsule::table('tblconfiguration')
            ->where('setting', 'DateFormat')
            ->value('value');

        return $dt->format(($whmcsDateFormat ?: 'Y-m-d') . ' H:i');
    }

    /**
     * Only used by the two "ticket opened" notifications: a ticket should
     * only ever fire an "opened" notification once. NOT used by the reply
     * notifications - a ticket can have many replies, and each one should
     * be able to notify, so there's no "already sent" concept for those.
     */
    private function ensureSentTableExists(string $table): void
    {
        if (Capsule::schema()->hasTable($table)) {
            return;
        }

        Capsule::schema()->create($table, function ($tableBlueprint) {
            $tableBlueprint->unsignedInteger('ticket_id')->primary();
            $tableBlueprint->dateTime('sent_at');
        });
    }
}
