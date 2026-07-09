<?php

/**
 * Code: ISHostInvoiceCreated
 *
 * Instant notification: fires directly off WHMCS's InvoiceCreated hook (for
 * client-ordered/auto-generated invoices) and InvoiceCreationAdminArea (for
 * invoices an admin creates directly - see additionalHooks() below).
 *
 * Sends as soon as the invoice is created, even if it's still in Draft
 * status. This is intentional, not an oversight: on WHMCS 9.0.6 (and
 * possibly other versions), a Draft invoice that's later published does not
 * reliably re-fire any hook this module can listen to, so "send at
 * creation" is the only trigger point that's actually reliable. If your
 * workflow is to create a Draft invoice and edit it over time before
 * publishing, be aware the client is notified immediately at creation, not
 * at publish time.
 *
 * WHMCS only gives these hooks the invoice id (`$vars['invoiceid']`), so
 * transformHookParams() below enriches it into the full payload this
 * notification's parameters need, via the same DB lookup previously used by
 * the (now removed) daily cron scan.
 *
 * @see https://developers.whmcs.com/hooks-reference/invoices-and-quotes/#invoicecreated
 * @see https://developers.whmcs.com/hooks-reference/admin-area/#invoicecreationadminarea
 */

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class ISHostInvoiceCreatedNotification extends AbstractNotification implements ResendableNotificationInterface
{
    /**
     * Domain shown in the "WHMCS domain" parameter.
     * Update this if the installation domain ever changes.
     */
    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    /**
     * Guards against sending twice for the same invoice: an admin-created
     * invoice that's published immediately can fire both
     * InvoiceCreationAdminArea and InvoiceCreated for the same invoice.
     */
    private const SENT_TABLE = 'mod_ishost_invoice_created_sent';

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
                // Assumes a getClientLastNameByClientId() helper exists in the
                // module, following the same convention as the first/full name
                // helpers already used elsewhere. Add it if it isn't defined yet.
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
                // Static, editable closing signature used at the end of the message.
                fn (): string => "Thank you for choosing Indian Server Hosting!\nIndian Server Hosting Team"
            ),
            new NotificationParameter(
                'whmcs_domain',
                lkn_hn_lang('WHMCS domain'),
                fn (): string => self::WHMCS_DOMAIN
            ),
            new NotificationParameter(
                'invoice_number',
                lkn_hn_lang('Invoice number'),
                // Formatted, customer-facing invoice number, e.g. ISH2025-10~E/759
                fn (): string => (string) $this->whmcsHookParams['invoicenum']
            ),
            new NotificationParameter(
                'invoice_id',
                lkn_hn_lang('Invoice ID'),
                // Internal database ID, e.g. 202509779
                fn (): string => (string) $this->whmcsHookParams['invoice_id']
            ),
            new NotificationParameter(
                'invoice_creation_date',
                lkn_hn_lang('Invoice creation date'),
                fn (): string => $this->whmcsHookParams['date']
            ),
            new NotificationParameter(
                'invoice_amount',
                lkn_hn_lang('Invoice amount'),
                // Subtotal, i.e. the invoice amount before credit is applied.
                fn (): string => $this->whmcsHookParams['subtotal']
            ),
            new NotificationParameter(
                'credit_used',
                lkn_hn_lang('Credit used'),
                fn (): string => $this->whmcsHookParams['credit']
            ),
            new NotificationParameter(
                'invoice_total_after_credit',
                lkn_hn_lang('Invoice total (after credit)'),
                fn (): string => $this->whmcsHookParams['total']
            ),
            new NotificationParameter(
                'invoice_due_date',
                lkn_hn_lang('Invoice due date'),
                fn (): string => getInvoiceDueDateByInvoiceId($this->whmcsHookParams['invoice_id'])
            ),
            new NotificationParameter(
                'invoice_items',
                lkn_hn_lang('Invoice items'),
                fn (): string => getItemsRelatedToInvoice($this->whmcsHookParams['invoice_id'])
            ),
            new NotificationParameter(
                'invoice_item',
                lkn_hn_lang('Invoice item'),
                // First item on the invoice only.
                fn (): string => $this->getFirstInvoiceItem()
            ),
        ];

        parent::__construct(
            'ISHostInvoiceCreated',
            NotificationReportCategory::INVOICE,
            Hooks::INVOICE_CREATED,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }

    /**
     * WHMCS does not route admin-created invoices through the InvoiceCreated
     * hook (this is a documented WHMCS quirk, not a bug here) - it fires the
     * separate InvoiceCreationAdminArea hook instead. Listening on both
     * covers invoices created via client order/auto-generation (InvoiceCreated)
     * and invoices an admin creates directly (InvoiceCreationAdminArea).
     *
     * @return Hooks[]
     */
    public function additionalHooks(): array
    {
        return [Hooks::INVOICE_CREATION_ADMIN_AREA];
    }

    /**
     * Enriches WHMCS's InvoiceCreated hook payload (just `invoiceid`) into
     * the full set of fields this notification's parameters need, and
     * guards against sending more than once for the same invoice (an
     * admin-created invoice published immediately can fire both
     * InvoiceCreationAdminArea and InvoiceCreated for it).
     *
     * @param array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null null skips sending.
     */
    public function transformHookParams(array $whmcsHookParams): ?array
    {
        $invoiceId = $whmcsHookParams['invoiceid'] ?? $whmcsHookParams['invoice_id'] ?? null;

        if (!$invoiceId) {
            return null;
        }

        $this->ensureSentTableExists();

        $alreadySent = Capsule::table(self::SENT_TABLE)
            ->where('invoice_id', $invoiceId)
            ->exists();

        if ($alreadySent) {
            return null;
        }

        $payload = $this->buildPayloadForInvoice((int) $invoiceId);

        if ($payload === null) {
            return null;
        }

        Capsule::table(self::SENT_TABLE)->insert([
            'invoice_id' => $invoiceId,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        return $payload;
    }

    /**
     * Builds the full parameter payload for a single invoice, fresh from the
     * database. Used both by transformHookParams() (instant send) and
     * buildResendPayload() (manual resend from the Reports page).
     */
    private function buildPayloadForInvoice(int $invoiceId): ?array
    {
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();

        if ($invoice === null || (float) $invoice->total === 0.0) {
            return null;
        }

        return [
            'client_id'           => $invoice->userid,
            'report_category_id' => $invoice->id,
            'invoice_id'          => $invoice->id,
            // WHMCS only assigns the formatted invoice number (e.g. "ISH2026-07~E/123")
            // once an invoice is published; while still Draft it's blank in the
            // database. Fall back to a plain "#<id>" reference so the message can
            // still send instead of being blocked by the empty-parameter guard.
            'invoicenum'          => $invoice->invoicenum ?: ('#' . $invoice->id),
            'date'                => $invoice->date,
            'duedate'             => $invoice->duedate,
            'subtotal'            => $invoice->subtotal,
            'credit'              => $invoice->credit,
            'total'               => $invoice->total,
        ];
    }

    private function getFirstInvoiceItem(): string
    {
        $invoiceId = $this->whmcsHookParams['invoice_id'];
        $items     = getInvoiceItemsDescriptionsByInvoiceId($invoiceId);

        return $items[0] ?? '';
    }

    private function ensureSentTableExists(): void
    {
        if (Capsule::schema()->hasTable(self::SENT_TABLE)) {
            return;
        }

        Capsule::schema()->create(self::SENT_TABLE, function ($table) {
            $table->unsignedInteger('invoice_id')->primary();
            $table->dateTime('sent_at');
        });
    }

    /**
     * Rebuilds the payload for a single invoice, fresh from the database,
     * so this notification can be resent from the Notification Reports page.
     */
    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        return $this->buildPayloadForInvoice($categoryId);
    }
}
