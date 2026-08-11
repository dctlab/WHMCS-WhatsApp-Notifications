<?php

/**
 * Code: TicketOpen
 *
 * Instant notification: fires directly off WHMCS's TicketOpen hook, letting
 * the CLIENT know their ticket was received. For the staff-facing version
 * of this same event, see AdminTicketOpenedNotification.
 *
 * @see https://developers.whmcs.com/hooks-reference/ticket/#ticketopen
 */

namespace Dct\HookNotification\Notifications\Custom;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class TicketOpenNotification extends AbstractNotification implements ResendableNotificationInterface
{
    use TicketNotificationPayloadTrait;

    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    private const SENT_TABLE = 'mod_ishost_ticket_open_sent';

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
        ];

        parent::__construct(
            'TicketOpen',
            NotificationReportCategory::TICKET,
            Hooks::TICKET_OPEN,
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

        $this->ensureSentTableExists(self::SENT_TABLE);

        $alreadySent = Capsule::table(self::SENT_TABLE)
            ->where('ticket_id', $ticketId)
            ->exists();

        if ($alreadySent) {
            return null;
        }

        $payload = $this->buildPayloadForTicket((int) $ticketId);

        if ($payload === null) {
            return null;
        }

        Capsule::table(self::SENT_TABLE)->insert([
            'ticket_id' => $ticketId,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        return $payload;
    }

    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        return $this->buildPayloadForTicket($categoryId);
    }
}
