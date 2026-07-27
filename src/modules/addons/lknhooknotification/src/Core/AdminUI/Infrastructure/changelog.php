<?php

return [
    [
        'version' => '4.5.35',
        'date' => '2026-07-10',
        'changes' => [
            'Fix: editing the TwoFactorAuthentication notification didn\'t work - it had a null hook value, which the Notifications list template doesn\'t handle. Now uses a real (but non-firing) hook label instead.',
        ],
    ],
    [
        'version' => '4.5.34',
        'date' => '2026-07-10',
        'changes' => [
            'New: 2FA Logs menu (User Logs / Admin Logs) showing the WhatsApp 2FA audit trail.',
            'New: TwoFactorAuthentication notification - the client-facing 2FA message is now a customizable template.',
            'New (2FA module): Code Valid minutes and Code Length are now configurable in the module\'s own settings screen.',
        ],
    ],
    [
        'version' => '4.5.33',
        'date' => '2026-07-10',
        'changes' => [
            'New: Meta support for the WhatsApp Verification 2FA module - new "2FA Authentication Template Name" setting under WhatsApp Meta.',
        ],
    ],
    [
        'version' => '4.5.32',
        'date' => '2026-07-10',
        'changes' => [
            'Hardened: a BulkDispatcher failure no longer silently prevents all instant notifications from registering - isolated into its own error boundary.',
        ],
    ],
    [
        'version' => '4.5.31',
        'date' => '2026-07-10',
        'changes' => [
            'Changed: widened the module\'s page layout cap from 1280px to 1880px across every page, reducing horizontal scrolling on wide tables like Notification Reports.',
        ],
    ],
    [
        'version' => '4.5.30',
        'date' => '2026-07-10',
        'changes' => [
            'Fix: Resend/Delete buttons were wrapping to a second line instead of staying side by side.',
            'Changed: tightened table padding and column widths to reduce horizontal scrolling.',
        ],
    ],
    [
        'version' => '4.5.29',
        'date' => '2026-07-10',
        'changes' => [
            'Changed: Resend/Delete are now icon-only grouped buttons with tooltips on Notification Reports.',
            'Changed: Per Page selector applies immediately on change.',
        ],
    ],
    [
        'version' => '4.5.28',
        'date' => '2026-07-10',
        'changes' => [
            'New: Bulk Action (Resend/Delete) on Notification Reports with row checkboxes and select-all.',
            'New: Per Page selector (10/25/50/100) on Notification Reports.',
        ],
    ],
    [
        'version' => '4.5.27',
        'date' => '2026-07-10',
        'changes' => [
            'Fix: InvoicePaymentReminder/InvoiceOverdueReminder crashed on every attempt (Unknown column "qty") - tblinvoiceitems has no such column. Now uses amount instead. This is why nothing was appearing in Notification Reports before.',
            'Note: invoices that hit the crash earlier today are already claimed as "sent today" by the duplicate-send guard - test on a fresh invoice, or check again tomorrow.',
        ],
    ],
    [
        'version' => '4.5.26',
        'date' => '2026-07-10',
        'changes' => [
            'Fix: TicketReplied/AdminTicketUserReplied crashed with a SQL error looking up the reply text - wrong column name (ticketid instead of tid).',
            'Hardened: InvoicePaymentReminder/InvoiceOverdueReminder dedup is now an atomic, database-enforced claim instead of check-then-insert, closing a possible duplicate-send race.',
        ],
    ],
    [
        'version' => '4.5.25',
        'date' => '2026-07-10',
        'changes' => [
            'Fix: InvoicePaymentReminder/InvoiceOverdueReminder now also fire when an admin manually clicks "Send Email" on an invoice, not just on WHMCS\'s automated reminder cron.',
        ],
    ],
    [
        'version' => '4.5.24',
        'date' => '2026-07-10',
        'changes' => [
            'New: Botms.in "Connection Closed"/"Send failed" errors now include a plain-language hint in Notification Reports pointing to the fix (reconnect the WhatsApp session in your Botms.in dashboard).',
        ],
    ],
    [
        'version' => '4.5.23',
        'date' => '2026-07-09',
        'changes' => [
            'New: InvoicePaymentReminder and InvoiceOverdueReminder notifications, both driven by WHMCS\'s own reminder automation, auto-split by due date.',
        ],
    ],
    [
        'version' => '4.5.22',
        'date' => '2026-07-09',
        'changes' => [
            'New: ModuleSuspended and ModuleUnsuspended notifications - Client First/Last/Full Name, Message Signature, WHMCS Domain, Product Name, Product Domain.',
        ],
    ],
    [
        'version' => '4.5.21',
        'date' => '2026-07-09',
        'changes' => [
            'Changed: replaced {{ticket_reply_time_bahasa}} with {{ticket_reply_time_whmcs}}, formatted using WHMCS\'s own configured Date Format instead of Indonesian.',
        ],
    ],
    [
        'version' => '4.5.20',
        'date' => '2026-07-09',
        'changes' => [
            'New: 6 instant notifications - ProductTermination, NewClientRegistration, TicketOpen, TicketReplied, AdminTicketOpened, AdminTicketUserReplied.',
            'New: Admin/staff WhatsApp alert number setting (Settings → Module), used by the two staff-facing ticket notifications.',
        ],
    ],
    [
        'version' => '4.5.19',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: saving a notification template with no platform selected crashed with an uncaught error instead of a normal validation message.',
        ],
    ],
    [
        'version' => '4.5.18',
        'date' => '2026-07-09',
        'changes' => [
            'New: "NewProductActivation" notification - fires instantly on service activation (AfterModuleCreate). Parameters: Client First/Last/Full Name, Message Signature, WHMCS Domain, Product Name, Product Domain, Service ID.',
        ],
    ],
    [
        'version' => '4.5.17',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: the "Sent Message" popover on Notification Reports could overflow/overlap the page for long messages (notably Botms.in/Baileys). Now wraps, capped at 500 characters, positioned above the row.',
        ],
    ],
    [
        'version' => '4.5.16',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: Botms.in webhook handler now correctly parses the real payload shape (Baileys-style messages.upsert events), skipping system/session events and extracting real incoming message previews.',
        ],
    ],
    [
        'version' => '4.5.15',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: sending via Botms.in failed with a TypeError when the WhatsApp Custom Field setting was left unselected.',
        ],
    ],
    [
        'version' => '4.5.14',
        'date' => '2026-07-09',
        'changes' => [
            'New: added Botms.in as a WhatsApp platform (Settings → Platforms → Botms.in) - Instance ID + Access Token, selectable for any notification template.',
            'New: webhook auto-registers with botms.in on save, feeding incoming messages into the WhatsApp Conversations chat view.',
        ],
    ],
    [
        'version' => '4.5.13',
        'date' => '2026-07-09',
        'changes' => [
            'New: "Sent Message" preview column on Notification Reports, showing what was actually sent to the client.',
            'New: Delete button on each report row (log entry only, does not unsend the message).',
            'Changed: Resend is now available on every report row, not just failed/errored ones.',
        ],
    ],
    [
        'version' => '4.5.12',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: ISHostInvoiceCreated now falls back to "#<invoice id>" for the invoice number when WHMCS hasn\'t assigned the formatted number yet (still Draft), instead of being blocked by the empty-parameter guard.',
        ],
    ],
    [
        'version' => '4.5.11',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: ISHostInvoiceCreated now sends at invoice creation time regardless of Draft status - on WHMCS 9.0.6, publishing a Draft invoice later doesn\'t reliably re-fire a hook this module can catch, so the previous Draft-skip left admin-created invoices unsent.',
        ],
    ],
    [
        'version' => '4.5.10',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: ISHostInvoiceCreated now also fires for invoices created directly by an admin (via the separate InvoiceCreationAdminArea hook), not just client-ordered/auto-generated invoices.',
            'New: skips invoices still saved as Draft, and guards against a duplicate send when an invoice is published immediately on creation.',
        ],
    ],
    [
        'version' => '4.5.9',
        'date' => '2026-07-09',
        'changes' => [
            'Fix: PaymentConfirmation now fires instantly on WHMCS\'s InvoicePaid hook instead of a once-daily cron scan.',
            'New: ISHostInvoiceCreated now fires instantly on WHMCS\'s InvoiceCreated hook instead of a once-daily cron scan.',
        ],
    ],
    [
        'version' => '4.5.8',
        'date' => '2026-07-09',
        'changes' => [
            'New: Billable and WA Category columns + filter on the Notification Reports page.',
            'New: Billable/Free/Paid delivered message stat cards on Message Analytics.',
            'New: Approximate Total Charges estimate on Message Analytics, based on rates you configure in Settings.',
            'New: added Meta\'s "Authentication (International)" category everywhere categories are shown/filtered.',
        ],
    ],
    [
        'version' => '4.5.7',
        'date' => '2026-07-08',
        'changes' => [
            'New: "WhatsApp Conversations" is now a live chat interface with a contact list, full message thread, and auto-refresh.',
            'New: reply directly from the chat view with a free-form text message (within Meta\'s 24h customer service window).',
            'New: full message history (both directions) is now stored, not just aggregate counts.',
        ],
    ],
    [
        'version' => '4.5.6',
        'date' => '2026-07-08',
        'changes' => [
            'Fix: sending now fails with a clear, specific error when a template merge field is blank, instead of Meta\'s opaque #131008 error.',
            'Fix: template-building errors were never checked before calling the API, silently sending malformed requests.',
            'New: incoming customer replies now show their actual message text on the WhatsApp Conversations page.',
        ],
    ],
    [
        'version' => '4.5.5',
        'date' => '2026-07-07',
        'changes' => [
            'New: inbound customer replies now update the WhatsApp Conversations page (message count and last-seen time), even before Meta reports the conversation id.',
        ],
    ],
    [
        'version' => '4.5.4',
        'date' => '2026-07-07',
        'changes' => [
            'Fix: webhook callback URL could render without a domain on some installs; now built from tblconfiguration.SystemURL.',
        ],
    ],
    [
        'version' => '4.5.3',
        'date' => '2026-07-07',
        'changes' => [
            'Fix: delivery status / conversation webhook events could silently fail to save if the DB migration had not been triggered yet by WHMCS. The schema now self-heals automatically on every request.',
        ],
    ],
    [
        'version' => '4.5.2',
        'date' => '2026-07-07',
        'changes' => [
            'Fix: the WhatsApp webhook callback URL shown in settings was broken (built from the admin page path); it is now built from the WHMCS system domain.',
            'Fix: webhook verify token comparison now trims whitespace to avoid false verification failures.',
        ],
    ],
    [
        'version' => '4.5.1',
        'date' => '2026-07-07',
        'changes' => [
            'New: WhatsApp Conversations page (per-conversation client, phone, category, message count, expiration).',
            'New: Billable/Free/Unknown tracking per conversation, based on Meta\'s pricing.billable field.',
            'New: Conversations are linked back to the WHMCS client and phone number when resolvable.',
        ],
    ],
    [
        'version' => '4.5.0',
        'date' => '2026-07-07',
        'changes' => [
            'New: WhatsApp delivery status tracking (sent/delivered/read/failed) via Meta status webhook.',
            'New: WhatsApp conversation analytics dashboard (billable conversations by category).',
            'New: Resend action for failed/errored notifications on the Reports page.',
            'New: Search reports by client, invoice, domain, status, delivery status, platform and date.',
        ],
    ],
    [
        'version' => '3.9.0',
        'date' => '2025-03-28',
        'changes' => [
            'Integration with Baileys',
        ],
    ],
    [
        'version' => '3.8.1',
        'date' => '2025-03-24',
        'changes' => [
            'General fixes',
        ],
    ],
    [
        'version' => '3.8.0',
        'date' => '2025-03-03',
        'changes' => [
            'WhatsApp Evolution.',
        ],
    ],
    [
        'version' => '3.7.1',
        'date' => '2025-02-21',
        'changes' => [
            'Fix language error when sending message.',
            'Fix number inputs to accept larger numbers.',
            'Add fallback to English for WhatsApp languages.',
        ],
    ],
    [
        'version' => '3.7.0',
        'date' => '2025-01-22',
        'changes' => [
            'Add multi-language support for WhatsApp Meta notifications.',
            'Add FPDI lib to enable editing invoice PDFs.',
            'Show warning when environment is incompatible with module requirements.',
        ],
    ],
    [
        'version' => '3.6.0',
        'date' => '2024-11-21',
        'changes' => [
            'Update WhatsApp API version.',
            'Settings menu now displays current API version.',
            'Add compatibility with PHP 8.1.',
        ],
    ],
    [
        'version' => '3.5.1',
        'date' => '2024-11-05',
        'changes' => [
            'Fix error when sending notifications with WhatsApp PDF.',
        ],
    ],
    [
        'version' => '3.5.0',
        'date' => '2024-10-28',
        'changes' => [
            'Fix error when sending manual WhatsApp notifications.',
        ],
    ],
    [
        'version' => '3.4.8',
        'date' => '2024-09-18',
        'changes' => [
            'Fix JS script references.',
        ],
    ],
    [
        'version' => '3.4.7',
        'date' => '2024-09-18',
        'changes' => [
            'Fix module artifact references for WHMCS in subdirectory.',
        ],
    ],
    [
        'version' => '3.4.6',
        'date' => '2024-08-12',
        'changes' => [
            'Fix SQL build error.',
        ],
    ],
    [
        'version' => '3.4.5',
        'date' => '2024-08-09',
        'changes' => [
            'Improve logging and error handling.',
            'Remove foreign key to avoid setup errors in WHMCS.',
            'Handle clients without custom WhatsApp number field.',
            'Improve handling for non-existent clients.',
        ],
    ],
    [
        'version' => '3.4.4',
        'date' => '2024-07-01',
        'changes' => [
            'Fix database issue.',
            'Fix language template recognition.',
            'Add template configuration.',
        ],
    ],
    [
        'version' => '3.4.3',
        'date' => '2024-03-20',
        'changes' => [
            'Fix issues creating DB tables.',
            'Fix notification config page when no notifications exist.',
        ],
    ],
    [
        'version' => '3.4.2',
        'date' => '2024-03-07',
        'changes' => [
            'Fix table installation.',
        ],
    ],
    [
        'version' => '3.4.1',
        'date' => '2024-01-30',
        'changes' => [
            'Adjust logic to send private notes to non-registered clients.',
        ],
    ],
    [
        'version' => '3.4.0',
        'date' => '2024-01-29',
        'changes' => [
            ' Rename "chat" to "integration".',
            ' Add description to Chatwoot integration screen.',
            ' Add links to dynamically access Chatwoot instance info.',
            ' Rename module to WhatsApp and Chatwoot.',
            'Implement per-notification configuration.',
            'Migrate structure for saving active Chatwoot settings in DB.',
        ],
    ],
    [
        'version' => '3.3.0',
        'date' => '2023-11-10',
        'changes' => [
            'Remove DB table deletion on module deactivation.',
            'Fix customer profile links in Chatwoot.',
        ],
    ],
    [
        'version' => '3.2.1',
        'date' => '2023-08-31',
        'changes' => [
            'Fix translations.',
            'Fix Config class when `_config` table doesn’t exist.',
        ],
    ],
    [
        'version' => '3.2.0',
        'date' => '2023-08-31',
        'changes' => [
            'Add module and notification internationalization.',
            'Add support for Chatwoot Live Chat.',
            'Improve notification delivery logging.',
            'Adjust module responsiveness on mobile.',
            'Highlight buttons to create/download notifications.',
        ],
    ],
    [
        'version' => '3.1.1',
        'date' => '2023-08-04',
        'changes' => [
            'Fix license validation checks.',
            'Fix association between notification and message template.',
        ],
    ],
    [
        'version' => '3.1.0',
        'date' => '2023-08-04',
        'changes' => [
            'Add home page with module docs and useful links.',
            'Add text-type parameters to header.',
            'Implement libphonenumber to validate client phone.',
            'Update license logic to allow >3 notifications on free plan.',
            'Add modal to show notification reports in client profile.',
        ],
    ],
    [
        'version' => '3.0.1',
        'date' => '2023-06-27',
        'changes' => [
            'Fix first installation bugs.',
        ],
    ],
    [
        'version' => '3.0.0',
        'date' => '2023-06-21',
        'changes' => [
            'Reimplement and simplify notification creation.',
            'Support temporary invoice PDF generation.',
            'Improve message template config with notification.',
            'Add reports screen.',
            'Simplify repository structure.',
        ],
    ],
    [
        'version' => '2.3.3',
        'date' => '2023-05-08',
        'changes' => [
            ' Change value column to longText for older DB compatibility.',
            ' Fix links to logs page.',
        ],
    ],
    [
        'version' => '2.3.2',
        'date' => '2023-05-03',
        'changes' => [
            'Fix template-notification association page error.',
        ],
    ],
    [
        'version' => '2.3.1',
        'date' => '2023-04-25',
        'changes' => [
            ' Implement check for mod_paghiper table existence.',
        ],
    ],
    [
        'version' => '2.3.0',
        'date' => '',
        'changes' => [
            'Add AfterModuleSuspend notification.',
            'Migrate config to Chatwoot settings screen.',
            'Fix message templates select with limit=200.',
        ],
    ],
    [
        'version' => '2.2.1',
        'date' => '',
        'changes' => [
            'Update logic for creating custom hooks.',
        ],
    ],
    [
        'version' => '2.2.0',
        'date' => '',
        'changes' => [
            'Implement version check.',
            'Add logo and description in addon list.',
            'Add button for module log access.',
        ],
    ],
    [
        'version' => '2.1.0',
        'date' => '',
        'changes' => [
            'Grammar fixes.',
            'Add default client name config.',
            'Improve invoice reminder feedback UI.',
            'Fix bugs in order created hook.',
        ],
    ],
    [
        'version' => '2.0.0',
        'date' => '',
        'changes' => [
            'Add Composer and dependencies.',
            'Add support for custom hooks.',
            'Migrate and improve settings screen.',
            'Improve message template register/edit screen.',
            'Add help screen.',
        ],
    ],
    [
        'version' => '1.1.0',
        'date' => '',
        'changes' => [
            'Add Dev Container setup files.',
            'Add "OrderCreated" hook for WhatsApp.',
            'Add "OrderCreated" hook for WhatsApp in ChatWoot channel.',
        ],
    ],
    [
        'version' => '1.0.0',
        'date' => '',
        'changes' => [
            'Admin panel to view invoices.',
            'Send invoice reminders as plain text.',
            'Send invoice reminder with PagHiper boleto.',
            'On success, send similar message to Chatwoot as private.',
        ],
    ],
];
