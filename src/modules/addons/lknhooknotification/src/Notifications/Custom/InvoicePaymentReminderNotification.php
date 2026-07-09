<?php

/**
 * Code: InvoicePaymentReminder
 *
 * Instant notification: fires off WHMCS's InvoicePaymentReminder hook, for
 * reminders sent BEFORE an invoice's due date (i.e. WHMCS's regular
 * "upcoming payment" reminder, configured under Setup > Automation Settings
 * > Invoice/Late Fees).
 *
 * For reminders sent about an invoice that's already past due, see
 * InvoiceOverdueReminderNotification instead - both notifications listen on
 * the same WHMCS hook, and each decides whether it applies by comparing the
 * invoice's due date to today.
 *
 * @see https://developers.whmcs.com/hooks-reference/invoices-and-quotes/#invoicepaymentreminder
 */

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class InvoicePaymentReminderNotification extends AbstractNotification implements ResendableNotificationInterface
{
    use InvoiceReminderPayloadTrait;

    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    /**
     * Guards against sending more than once for the same invoice on the
     * same day (WHMCS may run its reminder cron more than once daily).
     */
    private const SENT_TABLE = 'mod_ishost_invoice_payment_reminder_sent';

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
                'invoice_id',
                lkn_hn_lang('Invoice ID'),
                fn (): string => (string) $this->whmcsHookParams['invoice_id']
            ),
            new NotificationParameter(
                'invoice_number',
                lkn_hn_lang('Invoice number'),
                fn (): string => (string) $this->whmcsHookParams['invoicenum']
            ),
            new NotificationParameter(
                'invoice_paid_number',
                lkn_hn_lang('Invoice Paid Number'),
                fn (): string => (string) $this->whmcsHookParams['paid_invoicenum']
            ),
            new NotificationParameter(
                'invoice_creation_date',
                lkn_hn_lang('Invoice creation date'),
                fn (): string => (string) $this->whmcsHookParams['date']
            ),
            new NotificationParameter(
                'invoice_amount',
                lkn_hn_lang('Invoice amount'),
                fn (): string => (string) $this->whmcsHookParams['subtotal']
            ),
            new NotificationParameter(
                'credit_used',
                lkn_hn_lang('Credit used'),
                fn (): string => (string) $this->whmcsHookParams['credit']
            ),
            new NotificationParameter(
                'invoice_total_after_credit',
                lkn_hn_lang('Invoice total (after credit)'),
                fn (): string => (string) $this->whmcsHookParams['total']
            ),
            new NotificationParameter(
                'invoice_due_date',
                lkn_hn_lang('Invoice due date'),
                fn (): string => (string) $this->whmcsHookParams['duedate']
            ),
            new NotificationParameter(
                'invoice_items',
                lkn_hn_lang('Invoice items'),
                fn (): string => (string) $this->whmcsHookParams['items_description']
            ),
            new NotificationParameter(
                'invoice_item_no_zero_addons',
                lkn_hn_lang('Invoice Item (exclude zero qty addons)'),
                fn (): string => (string) $this->whmcsHookParams['first_item_no_zero_qty_addons']
            ),
        ];

        parent::__construct(
            'InvoicePaymentReminder',
            NotificationReportCategory::INVOICE,
            Hooks::INVOICE_PAYMENT_REMINDER,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }

    /**
     * Also listens on EmailPreSend, so this fires when an admin manually
     * clicks "Send Email" > an invoice reminder template on the invoice's
     * Summary tab - WHMCS's native InvoicePaymentReminder hook only fires
     * for its own automated cron, not for that manual action.
     *
     * @return Hooks[]
     */
    public function additionalHooks(): array
    {
        return [Hooks::EMAIL_PRE_SEND];
    }

    /**
     * @param array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null null skips sending (not this notification's
     *                            concern - e.g. the invoice is overdue, not upcoming).
     */
    public function transformHookParams(array $whmcsHookParams): ?array
    {
        $invoiceId = $this->resolveInvoiceIdForReminder($whmcsHookParams);

        if (!$invoiceId) {
            return null;
        }

        // This notification is only for reminders about invoices NOT yet
        // overdue - InvoiceOverdueReminderNotification handles the other case.
        if ($this->isInvoiceOverdue((int) $invoiceId)) {
            return null;
        }

        $this->ensureInvoiceReminderSentTableExists(self::SENT_TABLE);

        $today = date('Y-m-d');

        $claimed = $this->claimInvoiceReminderSentToday(self::SENT_TABLE, (int) $invoiceId, $today);

        if (!$claimed) {
            return null;
        }

        return $this->buildPayloadForInvoiceReminder((int) $invoiceId);
    }

    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        return $this->buildPayloadForInvoiceReminder($categoryId);
    }
}
