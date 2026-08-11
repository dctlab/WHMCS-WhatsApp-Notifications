<?php

/**
 * Code: PaymentConfirmation
 *
 * Instant notification: fires directly off WHMCS's InvoicePaid hook, which
 * WHMCS triggers exactly once, right when an invoice's balance reaches zero
 * - whether that happened via an automated gateway payment or an admin
 * manually recording a payment. There is no separate "admin added a
 * payment" case to handle: WHMCS routes both through the same InvoicePaid
 * event once the invoice is actually fully paid.
 *
 * WHMCS only gives this hook the invoice id (`$vars['invoiceid']`), so
 * transformHookParams() below enriches it into the full payload this
 * notification's parameters need - the same lookup previously used by the
 * (now removed) daily cron scan.
 *
 * A small tracking table (mod_ishost_payment_confirmation_sent) still guards
 * against sending more than once for the same invoice, in case WHMCS ever
 * fires InvoicePaid more than once for it.
 *
 * @see https://developers.whmcs.com/hooks-reference/invoices-and-quotes/#invoicepaid
 */

namespace Dct\HookNotification\Notifications\Custom;

use DateTime;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class PaymentConfirmationNotification extends AbstractNotification implements ResendableNotificationInterface
{
    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    private const SENT_TABLE = 'mod_ishost_payment_confirmation_sent';

    public function __construct()
    {
        $parameters = [
            new NotificationParameter(
                'invoice_id',
                lkn_hn_lang('Invoice ID'),
                // Internal database ID, e.g. 202509779
                fn (): int => $this->whmcsHookParams['invoice_id']
            ),
            new NotificationParameter(
                'invoice_number',
                lkn_hn_lang('Invoice number'),
                // Formatted, customer-facing invoice number, e.g. ISH2025-10~E/759
                fn (): string => (string) $this->whmcsHookParams['invoicenum']
            ),
            new NotificationParameter(
                'invoice_items',
                lkn_hn_lang('Invoice items'),
                fn (): string => getItemsRelatedToInvoice($this->whmcsHookParams['invoice_id'])
            ),
            new NotificationParameter(
                'invoice_total',
                lkn_hn_lang('Invoice total'),
                fn (): string => getInvoiceTotal($this->whmcsHookParams['invoice_id'])
            ),
            new NotificationParameter(
                'invoice_balance',
                lkn_hn_lang('Invoice balance'),
                fn (): string => getInvoiceBalance($this->whmcsHookParams['invoice_id'])
            ),
            new NotificationParameter(
                'invoice_pdf_url',
                lkn_hn_lang('Invoice PDF URL'),
                fn (): string => getInvoicePdfUrlByInvocieId($this->whmcsHookParams['invoice_id'])
            ),
            new NotificationParameter(
                'invoice_pdf_filename',
                lkn_hn_lang('Invoice PDF filename'),
                fn (): string => "Invoice-{$this->whmcsHookParams['invoice_id']}.pdf"
            ),
            new NotificationParameter(
                'client_id',
                lkn_hn_lang('Client ID'),
                fn (): int => $this->client->id
            ),
            new NotificationParameter(
                'client_email',
                lkn_hn_lang('Client email'),
                fn (): string => getClientEmailByClientId($this->client->id)
            ),
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
                'payment_amount',
                lkn_hn_lang('Payment amount'),
                fn (): string => $this->whmcsHookParams['payment_amount']
            ),
            new NotificationParameter(
                'payment_method',
                lkn_hn_lang('Payment method'),
                fn (): string => $this->whmcsHookParams['payment_method']
            ),
            new NotificationParameter(
                'transaction_id',
                lkn_hn_lang('Transaction ID'),
                fn (): string => $this->whmcsHookParams['transaction_id']
            ),
            new NotificationParameter(
                'payment_date',
                lkn_hn_lang('Payment date'),
                fn (): string => $this->whmcsHookParams['payment_date']
            ),
            new NotificationParameter(
                'payment_time',
                lkn_hn_lang('Payment time'),
                fn (): string => $this->whmcsHookParams['payment_time']
            ),
            new NotificationParameter(
                'whmcs_domain',
                lkn_hn_lang('WHMCS domain'),
                fn (): string => self::WHMCS_DOMAIN
            ),
            new NotificationParameter(
                'message_signature',
                lkn_hn_lang('Message signature'),
                fn (): string => "Thank you for choosing Indian Server Hosting!\nIndian Server Hosting Team"
            ),
        ];

        parent::__construct(
            'PaymentConfirmation',
            NotificationReportCategory::INVOICE,
            Hooks::INVOICE_PAID,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }

    /**
     * Enriches WHMCS's InvoicePaid hook payload (just `invoiceid`) into the
     * full set of fields this notification's parameters need, and guards
     * against sending more than once for the same invoice.
     *
     * @param array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null null skips sending (already sent, or invoice not found).
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
     * Builds the full parameter payload for a single invoice's payment,
     * fresh from the database. Used both by transformHookParams() (instant
     * send) and buildResendPayload() (manual resend from the Reports page).
     */
    private function buildPayloadForInvoice(int $invoiceId): ?array
    {
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();

        if ($invoice === null) {
            return null;
        }

        $transaction = Capsule::table('tblaccounts')
            ->where('invoiceid', $invoice->id)
            ->orderBy('date', 'desc')
            ->first();

        $paymentDateTime = $this->resolvePaymentDateTime($transaction, $invoice);
        $paymentMethod   = $this->resolvePaymentMethodName($transaction, $invoice);

        return [
            'client_id'           => $invoice->userid,
            'report_category_id' => $invoice->id,
            'invoice_id'          => $invoice->id,
            'invoicenum'          => $invoice->invoicenum,
            'payment_amount'      => $transaction->amountin ?? $invoice->total,
            'payment_method'      => $paymentMethod,
            'transaction_id'      => $transaction->transid ?? '',
            'payment_date'        => $paymentDateTime->format('Y-m-d'),
            'payment_time'        => $paymentDateTime->format('H:i:s'),
        ];
    }

    private function resolvePaymentDateTime($transaction, $invoice): DateTime
    {
        $rawDate = $transaction->date ?? $invoice->datepaid;

        if (empty($rawDate) || $rawDate === '0000-00-00 00:00:00') {
            return new DateTime();
        }

        return new DateTime($rawDate);
    }

    private function resolvePaymentMethodName($transaction, $invoice): string
    {
        $gatewayModule = $transaction->gateway ?? $invoice->paymentmethod;

        if (empty($gatewayModule)) {
            return '';
        }

        return Capsule::table('tblpaymentgateways')
            ->where('gateway', $gatewayModule)
            ->where('setting', 'Name')
            ->value('value') ?? $gatewayModule;
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
     * Rebuilds the payload for a single invoice's payment, fresh from the
     * database, so this notification can be resent from the Notification
     * Reports page even after the "already sent" guard would otherwise
     * skip it.
     */
    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        return $this->buildPayloadForInvoice($categoryId);
    }
}
