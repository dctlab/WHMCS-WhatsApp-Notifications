<?php

/**
 * Code: TicketReplied
 *
 * Instant notification: fires directly off WHMCS's TicketAdminReply hook,
 * letting the CLIENT know staff replied to their ticket.
 *
 * Unlike the "ticket opened" notifications, there's no duplicate-send guard
 * here: a ticket can receive many replies over its lifetime, and each one
 * should be able to notify - "already sent" doesn't apply per-ticket here.
 *
 * @see https://developers.whmcs.com/hooks-reference/ticket/#ticketadminreply
 */

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;

final class TicketRepliedNotification extends AbstractNotification implements ResendableNotificationInterface
{
    use TicketNotificationPayloadTrait;

    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

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
                fn (): string => "Thank you for choosing Indian Server Hosting!\nIndian Server Hosting Team"
            ),
            new NotificationParameter(
                'whmcs_domain',
                lkn_hn_lang('WHMCS domain'),
                fn (): string => self::WHMCS_DOMAIN
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
                'staff_responded',
                lkn_hn_lang('Staff Responded The Ticket'),
                fn (): string => (string) $this->whmcsHookParams['replied_by']
            ),
            new NotificationParameter(
                'reply_message',
                lkn_hn_lang('Reply Message Content'),
                fn (): string => (string) $this->whmcsHookParams['reply_message']
            ),
        ];

        parent::__construct(
            'TicketReplied',
            NotificationReportCategory::TICKET,
            Hooks::TICKET_ADMIN_REPLY,
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
