<?php

namespace Lkn\HookNotification\Notifications\Custom;

use WHMCS\Database\Capsule;

/**
 * Shared invoice reminder payload building, reused by
 * InvoicePaymentReminderNotification and InvoiceOverdueReminderNotification.
 *
 * This is a TRAIT, not a class: the module's Custom/*.php auto-discovery
 * instantiates every newly-declared CLASS in this folder as a notification,
 * so shared logic here must be a trait (or interface) to be safely skipped -
 * the same way ResendableNotificationInterface and
 * TicketNotificationPayloadTrait already are.
 *
 * @since 4.5.23
 */
trait InvoiceReminderPayloadTrait
{
    /**
     * Resolves the invoice id this event is about, whether it came from
     * WHMCS's native InvoicePaymentReminder cron hook (`invoiceid`) or from
     * the generic EmailPreSend hook fired when an admin manually clicks
     * "Send Email" on an invoice's Summary tab (`relid` + `messagename`).
     *
     * EmailPreSend fires for EVERY email WHMCS sends (signup, password
     * reset, etc), not just invoice reminders - `relid` on those other
     * emails is NOT an invoice id (e.g. it's a client id for a signup
     * email). So this only treats it as an invoice id when `messagename`
     * clearly names an invoice reminder/overdue template; otherwise it
     * returns null rather than risk looking up (and messaging about) the
     * wrong invoice.
     *
     * @param array<mixed> $whmcsHookParams
     */
    private function resolveInvoiceIdForReminder(array $whmcsHookParams): ?int
    {
        if (!empty($whmcsHookParams['invoiceid'])) {
            return (int) $whmcsHookParams['invoiceid'];
        }

        if (!empty($whmcsHookParams['invoice_id'])) {
            return (int) $whmcsHookParams['invoice_id'];
        }

        $messageName = $whmcsHookParams['messagename'] ?? null;
        $relId       = $whmcsHookParams['relid'] ?? null;

        if (!$messageName || !$relId) {
            return null;
        }

        $looksLikeInvoiceReminderEmail = stripos($messageName, 'invoice') !== false
            && (stripos($messageName, 'remind') !== false || stripos($messageName, 'overdue') !== false);

        if (!$looksLikeInvoiceReminderEmail) {
            return null;
        }

        return (int) $relId;
    }

    /**
     * Builds the full parameter payload for a single invoice, fresh from
     * the database.
     */
    private function buildPayloadForInvoiceReminder(int $invoiceId): ?array
    {
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();

        if ($invoice === null) {
            return null;
        }

        return [
            'client_id'            => $invoice->userid,
            'report_category_id'  => $invoice->id,
            'invoice_id'           => $invoice->id,
            'invoicenum'           => $invoice->invoicenum ?: ('#' . $invoice->id),
            // WHMCS only assigns this when "Sequential Paid Invoice Numbering" is
            // enabled, and only once the invoice is actually paid - so for a
            // reminder about a still-unpaid invoice this is normally blank.
            'paid_invoicenum'      => $invoice->invoicenum ?: 'N/A',
            'date'                 => $invoice->date,
            'duedate'              => $invoice->duedate,
            'subtotal'             => $invoice->subtotal,
            'credit'               => $invoice->credit,
            'total'                => $invoice->total,
            'items_description'    => $this->describeInvoiceItems($invoice->id),
            'first_item_no_zero_qty_addons' => $this->describeFirstNonZeroAddonItem($invoice->id),
        ];
    }

    /**
     * Whether an invoice's due date has already passed (as of today).
     */
    private function isInvoiceOverdue(int $invoiceId): bool
    {
        $dueDate = Capsule::table('tblinvoices')->where('id', $invoiceId)->value('duedate');

        if (empty($dueDate) || $dueDate === '0000-00-00') {
            return false;
        }

        return strtotime($dueDate) < strtotime('today');
    }

    private function describeInvoiceItems(int $invoiceId): string
    {
        $descriptions = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->pluck('description')
            ->toArray();

        return !empty($descriptions) ? implode(' - ', $descriptions) : 'N/A';
    }

    /**
     * First invoice item, skipping addon items that aren't actually being
     * charged for (amount = 0 - e.g. an addon included at zero quantity/cost
     * this cycle).
     *
     * Note: tblinvoiceitems has no `qty` column (verified against WHMCS's
     * own schema after this crashed in production) - `amount` is the closest
     * schema-safe stand-in for "not really included" available directly on
     * this table, without an extra join to tblhostingaddons that would need
     * its own verification.
     */
    private function describeFirstNonZeroAddonItem(int $invoiceId): string
    {
        $item = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->where(function ($query) {
                $query->where('type', '!=', 'Addon')
                    ->orWhere('amount', '>', 0);
            })
            ->orderBy('id')
            ->value('description');

        return $item ?: 'N/A';
    }

    private function ensureInvoiceReminderSentTableExists(string $table): void
    {
        if (!Capsule::schema()->hasTable($table)) {
            Capsule::schema()->create($table, function ($tableBlueprint) {
                $tableBlueprint->unsignedInteger('invoice_id');
                $tableBlueprint->date('reminder_date');
                $tableBlueprint->dateTime('sent_at');
                $tableBlueprint->unique(['invoice_id', 'reminder_date'], 'uniq_invoice_reminder_day');
            });

            return;
        }

        // Self-heal: tables created before this constraint existed won't
        // have it yet. Without it, two near-simultaneous events for the same
        // invoice/day (e.g. the automated cron and a manual "Send Email"
        // click landing close together) could both pass the "not yet sent"
        // check before either finishes inserting, sending twice.
        $hasUniqueIndex = Capsule::connection()->select("
            SELECT COUNT(1) as total FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND INDEX_NAME = 'uniq_invoice_reminder_day'
        ", [$table]);

        if (($hasUniqueIndex[0]->total ?? 0) == 0) {
            Capsule::schema()->table($table, function ($tableBlueprint) {
                $tableBlueprint->unique(['invoice_id', 'reminder_date'], 'uniq_invoice_reminder_day');
            });
        }
    }

    /**
     * Atomically claims the "already sent this invoice's reminder today"
     * slot: attempts the insert directly (relying on the unique constraint
     * above) rather than checking existence first, which would leave a race
     * window between the check and the insert.
     *
     * @return bool true if this call successfully claimed the slot (i.e. no
     *              one else already sent it today); false if it was already sent.
     */
    private function claimInvoiceReminderSentToday(string $table, int $invoiceId, string $today): bool
    {
        try {
            Capsule::table($table)->insert([
                'invoice_id' => $invoiceId,
                'reminder_date' => $today,
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (\Throwable $th) {
            // Unique constraint violation (or the pre-heal table without the
            // index, in the rare case ensureInvoiceReminderSentTableExists()
            // hasn't run yet) - either way, treat as "already claimed".
            return false;
        }
    }
}
