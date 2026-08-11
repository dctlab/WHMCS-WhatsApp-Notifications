<?php

/**
 * Code: AdminTicketUserReplied
 *
 * Instant notification: fires directly off WHMCS's TicketUserReply hook,
 * alerting STAFF/ADMIN (not the client) that a client replied to a ticket.
 * Always sent to Settings::ADMIN_ALERT_WHATSAPP_NUMBER - see
 * AbstractPlatform::ADMIN_FACING_NOTIFICATION_CODES.
 *
 * No duplicate-send guard: a ticket can receive many client replies over
 * its lifetime, and each one should be able to notify.
 *
 * @see https://developers.whmcs.com/hooks-reference/ticket/#ticketuserreply
 */

namespace Dct\HookNotification\Notifications\Custom;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

final class AdminTicketUserRepliedNotification extends AbstractNotification implements ResendableNotificationInterface
{
    use TicketNotificationPayloadTrait;

    public function __construct()
    {
        $parameters = [
            new NotificationParameter(
                'client_first_name',
                lkn_hn_lang('Client first name'),
                fn (): string => getClientFirstNameByClientId($this->client->id)
            ),
            new NotificationParameter(
                'client_last_name',
                lkn_hn_lang('Client last name'),
                fn (): string => getClientLastNameByClientId($this->client->id)
            ),
            new NotificationParameter(
                'client_full_name',
                lkn_hn_lang('Client full name'),
                fn (): string => getClientFullNameByClientId($this->client->id)
            ),
            new NotificationParameter(
                'message_signature',
                lkn_hn_lang('Message signature'),
                fn (): string => "Indian Server Hosting Team"
            ),
            new NotificationParameter(
                'ticket_reply_time_whmcs',
                lkn_hn_lang('Ticket Reply Time (WHMCS format)'),
                fn (): string => (string) $this->whmcsHookParams['event_timestamp_whmcs']
            ),
            new NotificationParameter(
                'reply_time_hour',
                lkn_hn_lang('Reply Time (Hour part)'),
                fn (): string => (string) $this->whmcsHookParams['event_hour']
            ),
            new NotificationParameter(
                'reply_time_minute',
                lkn_hn_lang('Reply Time (Minute part)'),
                fn (): string => (string) $this->whmcsHookParams['event_minute']
            ),
            new NotificationParameter(
                'reply_time_total_minutes',
                lkn_hn_lang('Reply Time (Total minutes)'),
                fn (): string => (string) $this->whmcsHookParams['event_total_minutes']
            ),
            new NotificationParameter(
                'message_timestamp',
                lkn_hn_lang('Message Timestamp'),
                fn (): string => (string) $this->whmcsHookParams['event_timestamp']
            ),
            new NotificationParameter(
                'ticket_id',
                lkn_hn_lang('Ticket ID'),
                fn (): string => (string) $this->whmcsHookParams['ticket_id']
            ),
            new NotificationParameter(
                'ticket_subject',
                lkn_hn_lang('Ticket Subject'),
                fn (): string => (string) $this->whmcsHookParams['ticket_subject']
            ),
            new NotificationParameter(
                'ticket_link',
                lkn_hn_lang('Link to Ticket (for Client)'),
                fn (): string => (string) $this->whmcsHookParams['ticket_link']
            ),
            new NotificationParameter(
                'ticket_department',
                lkn_hn_lang('Ticket Department'),
                fn (): string => (string) $this->whmcsHookParams['ticket_department']
            ),
            new NotificationParameter(
                'ticket_priority',
                lkn_hn_lang('Ticket Priority'),
                fn (): string => (string) $this->whmcsHookParams['ticket_priority']
            ),
            new NotificationParameter(
                'staff_responded',
                lkn_hn_lang('Staff Responded The Ticket'),
                fn (): string => (string) $this->whmcsHookParams['replied_by']
            ),
            new NotificationParameter(
                'reply_message',
                lkn_hn_lang('Reply Message Content'),
                fn (): string => (string) $this->whmcsHookParams['reply_message']
            ),
            new NotificationParameter(
                'ticket_system_id',
                lkn_hn_lang('Ticket System ID'),
                fn (): string => (string) $this->whmcsHookParams['ticket_system_id']
            ),
        ];

        parent::__construct(
            'AdminTicketUserReplied',
            NotificationReportCategory::TICKET,
            Hooks::TICKET_USER_REPLY,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }

    /**
     * @param array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null null skips sending.
     */
    public function transformHookParams(array $whmcsHookParams): ?array
    {
        $ticketId = $whmcsHookParams['ticketid'] ?? $whmcsHookParams['ticket_id'] ?? null;

        if (!$ticketId) {
            return null;
        }

        $replyId = $whmcsHookParams['replyid'] ?? null;
        $reply   = $this->resolveTicketReply((int) $ticketId, $replyId ? (int) $replyId : null);

        return $this->buildPayloadForTicket(
            (int) $ticketId,
            null,
            $reply['message'] ?? null,
            $reply['admin'] ?? null,
        );
    }

    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        $reply = $this->resolveTicketReply($categoryId);

        return $this->buildPayloadForTicket(
            $categoryId,
            null,
            $reply['message'] ?? null,
            $reply['admin'] ?? null,
        );
    }
}
