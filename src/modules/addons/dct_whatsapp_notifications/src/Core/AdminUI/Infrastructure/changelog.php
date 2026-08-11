<?php

return [
    [
        'version' => '5.12.0',
        'date' => '2026-08-11',
        'changes' => [
            'Phase 10 - Final cross-module QA, security, performance, and consistency audit across all 9 prior redesign phases. Full comprehensive scans (not per-file, but across the entire delivered source): zero Bootstrap 5 patterns found anywhere, zero unscoped CSS selectors, all navigation routes verified directly against endpoints.php, credential masking and log security re-verified end to end, the approved database index confirmed as the only schema change introduced across all 9 phases.',
            'Three real security issues found and fixed during the (explicitly scoped as limited) provider security scan of the previously-deferred Botms/Chatwoot/Evolution API setup views: (1) Evolution API error view had the same raw-API-error-dump pattern already fixed for Meta in Phase 8, missed at the time since this provider view was deferred - fixed the same way. (2) Chatwoot live chat widget initialization interpolated client name/email/phone directly into unquoted-context JS string literals with no JS-specific escaping - a client name containing a single quote could break out of the string and inject arbitrary JavaScript; fixed with Smarty own escape:javascript modifier (confirmed the adjacent custom_attrs_script value was already safely json_encode()d and correctly left untouched, since escaping it again would have corrupted it). (3) Botms webhook registration status displayed the raw third-party API response message with no HTML escaping - added.',
            'Two findings reported, not fixed, since they require backend changes outside pure security fixes: a pre-existing, low-severity redundancy where the same DataTables CSS link is added by two separate AdminAreaHeadOutput hooks on the bulk/new page (one page-scoped, one unconditional across all WHMCS admin - the unconditional one was already noted in Phase 1 and left alone as outside this module own asset-scoping concern); and one pre-existing console.log in password_recovery.js, a file entirely unrelated to and untouched by any of the 9 redesign phases.',
            'No new features added, per this phase explicit scope - QA and stabilization only. See the accompanying final report for the complete, itemized findings list with severity classifications.',
        ],
    ],
    [
        'version' => '5.11.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 9 - Bulk Messaging redesign, across all three views (list, progress-report component, and the main create/edit form). This turned out to be a genuinely sophisticated existing system - filter-based recipient matching (client status/language/country/service/service status), an explicit "view matched clients" step with individual per-client opt-out, real backend-computed progress percentages, cron-driven queued sending (never synchronous from the browser), status control (in-progress/aborted/completed), and per-recipient resend on the results table. All of it reused as-is - restyled with DCTLAB components, every field name, JS function (DataTables integration, the not-sending-client toggle logic, status-change confirmation), form action, and route preserved exactly, verified via direct field-name comparison.',
            'Bug found and fixed: the Max Concurrency field on the edit page used {if condition} - literally the undefined word "condition" as a Smarty expression, which always evaluates false. This meant the field silently always displayed "0" instead of the actual saved concurrency value whenever editing an existing bulk campaign. Fixed to render the real value directly.',
            'Confirmed via audit, not assumed: recipient counts, progress percentages, and per-recipient statuses (error/aborted/waiting/success) are all genuinely computed by the existing backend, not decorative - displayed as-is. No fake scheduling, progress, or queue was invented; the existing scheduling (start date) and queue (cron-based) were already real.',
            'No controller or service files touched this phase - purely a presentation-layer redesign across the three view files.',
        ],
    ],
    [
        'version' => '5.10.1',
        'date' => '2026-08-11',
        'changes' => [
            'Security fix (approved follow-up to Phase 8): password-type provider settings (Business Account ID, Access Token, Phone Number ID, and the equivalent credential field on every other provider - Botms, Baileys, Evolution API, Chatwoot, and the module License field, all six confirmed to use the same "type" => "password" definition) no longer render their actual stored value into HTML. The input is always empty on page load, with help text explaining that a value is already saved and leaving it blank keeps it, or that nothing is saved yet.',
            'Save-path change, scoped narrowly to password-type fields only, identified via each provider own settings definition (not the HTML input type - never trusted the browser for this): if a password field is submitted blank, that specific setting is skipped entirely rather than being overwritten with an empty string, preserving whatever is currently stored. If a new value is entered, it replaces the old one exactly as before. Every non-secret setting type (text, url, number, select, multiple, textarea, checkbox) has its save behavior completely unchanged - verified by confirming the new logic only branches on type === password.',
            'Also fixed while in the same code: the existing settings-save log call was passing the raw credential value into the log entry whenever a password field changed - now passed through WHMCS own logModuleCall masking mechanism (the $masks parameter, already present in this module logging helper but previously always called with an empty array for this specific log), so any occurrence of the actual new secret is redacted from what gets stored, on both the success and failure log paths.',
            'No provider authentication logic, API calls, webhook logic, or any specific platform integration touched - confirmed via diff scope: only the generic settings service/repository and the shared settings template were modified.',
        ],
    ],
    [
        'version' => '5.10.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 8 - Settings redesign. Restyled the shared, data-driven settings form (used by every provider - Meta, Botms, Baileys, Evolution API, Chatwoot, General, Bulk all render through this one template) and the Meta connection-status view. Every field name, form action/method, hidden field, and the existing confirmSubmit() confirmation dialog preserved exactly - verified via direct comparison against the original.',
            'Two security fixes, both directly in scope per this phase own security section, neither changing how saving actually works: (1) the settings-save failure alert previously embedded the raw PHP exception message on screen via a <pre> tag - removed; the exception is now properly logged server-side instead (previously it was not logged anywhere at all, so this is a net improvement to debuggability, not a loss - the message already said "go to the module logs," which is now actually true). (2) the Meta connection-test error view previously dumped the full raw Meta API error response on screen - replaced with a generic message pointing to the module logs; the controller still passes the same data unchanged, this is a view-level choice not to render it.',
            'Deliberately NOT fixed, reported instead: every password-type credential field (Business Account ID, Access Token, Phone Number ID, etc.) has its actual raw value embedded in the page HTML via value="{$setting[\'current\']}" - type="password" only masks it visually, view-source/devtools would still reveal it. The correct fix (mask on display, "leave blank to keep current" on save) requires changing updateSettings() own save-logic for empty submissions, since currently an empty field is saved as blank, silently wiping the credential. Given the explicit stop-condition for backend changes and the real risk of a save-logic bug wiping production WhatsApp credentials, this was not silently implemented - reported as a proposed follow-up requiring explicit approval instead.',
            'Scope decision, not an oversight: Botms/Chatwoot/Evolution API each have their own substantial, working, provider-specific setup views (webhook registration, QR-code connection flow, custom-attribute setup) beyond the shared settings form - these were left unchanged this round given the scope already covered, consistent with the Phase 1 principle that not-yet-redesigned pages may still look old.',
        ],
    ],
    [
        'version' => '5.9.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 7 - 2FA Logs (Client and Admin, both share one controller/template) redesign. Restyled with the DCTLAB design system: filter toolbar, status-style event badges, table, new empty state. Every existing filter (User ID, Event, Date From/To - confirmed these are the only ones that exist; no Status/Phone/IP filter was added since none exist), the exact three real event values (code_sent/verify_success/verify_failed - no invented "expired" or "pending" states), and the existing windowed pagination all preserved exactly - verified via direct field-name comparison against the original template. Route verified directly against endpoints.php before any work began, not inferred from navbar.php.',
            'Security fix, in scope per this phase own brief: added |escape to ip_address, details, event (fallback case), and user_name output - none of these were escaped in the original template. Smarty does not auto-escape by default, and details/ip_address are log-controlled fields as called out explicitly in this phase security section - this is an output-escaping fix only, the stored data itself is completely unchanged.',
            'Not built, reported instead: a security-summary KPI row (Total/Successful/Failed/Today) was requested but would require new aggregate COUNT queries, since only one page of 30 rows is ever loaded at a time - the brief explicitly says to stop and report rather than add this without approval, so it was not built.',
        ],
    ],
    [
        'version' => '5.8.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 6 - WhatsApp Conversations (live chat) page redesign. Restyled with the DCTLAB design system: conversation list, message bubbles, composer. Every existing JS mechanism preserved exactly and verified via direct occurrence-count comparison against the original - the 5-second polling loop, the live/offline indicator, the fetch()-based JSON send flow, and both layers of message escaping (server-side |escape on initial render, client-side escapeHtml() via textContent on polled messages) are all byte-identical in behavior to before. No PHP files touched - pure template-level change.',
            'New, added safely: a mobile back-button behavior (list visible by default, selecting a conversation reveals the thread, Back returns) - pure CSS/display-state toggle over data already on the page, not a new capability. Deliberately only activates when a conversation was explicitly selected via the URL, not when the controller auto-selected the first conversation as its existing fallback default on a fresh visit - keeping the list as the true default view on mobile per the brief.',
            'Honestly confirmed absent, not invented: no search (neither client nor server-side existed before), no unread counts, no read receipts, no per-message provider/platform label (the messages table has no platform column), and no client info sidebar beyond name/phone (building one would need new joins against tblclients that do not currently happen here). None of these were added.',
            'Correction to my own earlier mistake (Phase 5): the Analytics page "View every conversation" link was changed back to notification-conversations. In Phase 5 I incorrectly assumed this endpoint was broken and pointed it at notification-chat instead, based on checking navbar.php and entrypoint.php but not the actual route registration file (endpoints.php) - which confirms notification-conversations is a real, separate, valid endpoint (a different page entirely, the billing-analysis conversation list, not the live chat UI this phase covers). Verified directly against endpoints.php before correcting.',
        ],
    ],
    [
        'version' => '5.7.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 5 - Analytics page redesign. Date range presets (Today/Yesterday/Last 7/30 Days/This Month/custom) added using the same pattern as the Dashboard, on this page own existing f_date_from/f_date_to param names. Delivery Rate/Read Rate/Failure Rate KPI cards added, computed in PHP (not risky inline template math). Delivery Performance shown as lightweight CSS bars, Message Activity as the same no-dependency chart used on the Dashboard - no chart library added. New empty state for when no data exists in the selected range.',
            'Backend reuse, not duplication: renamed NotificationReportService::getDashboardData() to getPerformanceOverview(), since Analytics now reuses the exact same method the Dashboard already used (Phase 2) - same queries, same behavior, just a name that no longer implies it is Dashboard-only, plus message_billable added to its return array (already being computed internally, just not previously exposed). The one existing call site (Dashboard controller) updated accordingly - verified via full-codebase search that no reference to the old name remains anywhere except historical changelog text.',
            'This replaces the previous "Most sent notifications (last hour)" panel, which was honestly labeled but inconsistent with the rest of the page (always last-hour regardless of the selected date range) - Notification Performance now respects the same range as every other section, reusing the existing getNotificationPerformance() query from Phase 2 rather than writing anything new.',
            'Bug found and fixed: the "View every conversation" link pointed to a page param (notification-conversations) that does not exist in the actual route registration (endpoints.php) - would have 404d if clicked. Corrected to the real endpoint (notification-chat), confirmed by direct inspection of the routing table before changing it.',
        ],
    ],
    [
        'version' => '5.6.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 4 - Reports page redesign. Restyled with the DCTLAB design system: filter toolbar, status/delivery/billable badges (mapped from the existing Bootstrap 3 label classes returned by the domain model, including label-primary which has no direct DCTLAB equivalent - mapped to info as the closest semantic match), table, and a new empty state (No matching notifications found + Clear Filters) for when filters return nothing. Every existing filter, the popover-based message-details mechanism, bulk actions (resend/delete), individual per-row actions, and full server-side pagination preserved exactly - verified via direct field-name comparison against the original template. No PHP files touched this phase.',
            'Audit finding, not silently fixed: the repository layer already supports filtering reports by notification code (applyFilters() has full support for it), but the controller never wires a request parameter to it - so this capability exists in the data layer but is not reachable from the UI today. Not added in this phase, since doing so is a small backend change and this phase is scoped to presentation only; flagged for a separate, explicit decision.',
            'Also noted, not changed: per_page defaults to 30 when no valid value is supplied, but 30 is not itself one of the four selectable per_page options (10/25/50/100) - a pre-existing inconsistency, left exactly as it was found rather than silently adjusted mid-UI-phase.',
        ],
    ],
    [
        'version' => '5.5.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 3B - Notification Template Editor redesign. Restyled all three files that make up this page (outer form, the Meta editor, and the shared Standard editor used by every non-Meta provider) with the DCTLAB design system. Every existing form field name, hidden input, JS confirm/toggle function, and save/delete backend path preserved byte-for-byte identical - verified directly via field-name comparison, not just visual review. Purely a presentation-layer change; no controller, service, or renderer PHP touched.',
            'New, added safely: a client-side-only Message Preview for the Meta editor, built entirely from data already on the page (the template text and each dropdowns current selection) - no API call, no new backend data, explicitly labeled as sample values for illustration.',
            'Honest limitation in the new preview: dynamic URL buttons (ones containing a {{N}} variable) render in the underlying HTML as bare dropdowns without a button wrapper, so they do not appear in the button row of the preview - only static/quick-reply buttons do. Everything else (header, body, footer) reflects live selections correctly.',
            'Confirmed via audit, not assumed: "Category" and a free-text "Template Name" field do not exist in the current implementation (Meta template selection is a dropdown of already-approved templates, and category is never even fetched from the API) - neither was added, per the explicit instruction not to fake unsupported fields. The in-editor Delete button was already commented out/disabled in the original code (delete only works from the Notifications list) - left exactly as-is rather than re-enabled, since that would be adding functionality, not preserving it.',
        ],
    ],
    [
        'version' => '5.4.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 3A - Notifications list redesign. Restyled with the DCTLAB design system, plus client-side search and Status/Provider filters - implemented as pure JS filtering of the already-loaded page data (the notification list is file-based discovery, not a paginated database query, so this adds zero new queries or requests). Every existing action (Setup template, Edit, Delete) points at the exact same routes/form submissions as before - purely a presentation-layer change, no controller or backend logic modified.',
            'Honest gaps, not invented: no top-level "+ Create Notification" button (no such route exists - notification types are added via code, not the UI; the real per-notification "Setup template" action is unchanged), no Enable/Disable toggle (status is derived from template count, not a stored flag - no toggle endpoint exists to wire one to), no Test action (nothing on this page provides one - the only test-send feature in the codebase lives on a different page entirely, WHMCS own Admin Client Summary).',
            'Provider display kept accurate to the real data model: a single notification can have templates across multiple providers at once, so the table keeps the existing nested notification-to-templates structure rather than forcing a false one-provider-per-row shape.',
        ],
    ],
    [
        'version' => '5.3.1',
        'date' => '2026-08-11',
        'changes' => [
            'New: database migration adding INDEX idx_created_at_status (created_at, status) to mod_dct_hook_notification_reports - the index approved after the Phase 2 dashboard performance review, supporting the Notification Performance, Recent Activity, and Daily Message Activity queries added in that phase. Runs once via the normal version-gated upgrade process, not on every request. No existing index, column, primary key, or data touched - purely additive.',
        ],
    ],
    [
        'version' => '5.3.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 2 - Dashboard redesign, built entirely on real data. KPI cards (Messages Sent, Delivered, Read, Failed, Billable Conversations, Estimated Charges), a date range selector (Today/Yesterday/Last 7 Days/Last 30 Days/This Month/custom), a lightweight no-external-library activity chart, per-notification-type delivery performance, WhatsApp usage by category, a recent activity feed, provider configuration status (reads saved settings only - no live API calls on every page load), and quick actions linking only to pages that actually exist.',
            'Backend additions, all read-only presentation queries reusing existing patterns - no sending/processing/billing logic touched: getNotificationPerformance() and getRecentReports() (repository), getDailyMessageActivity() (repository), and getDashboardData() (service) which assembles the above alongside the existing getAnalytics() method rather than duplicating its delivery/conversation/billing queries.',
            'Honest gaps, not invented: no live provider connection testing (would mean an API call on every dashboard render) - shows "Configured" or "Not Configured" based on saved settings instead, never claiming "Connected" without a real check. No "Send Message" quick action, since no standalone page for that exists yet.',
        ],
    ],
    [
        'version' => '5.2.0',
        'date' => '2026-08-11',
        'changes' => [
            'New: DCTLAB UI Phase 1 - shared design system foundation, purely additive, no existing page redesigned yet. New namespaced CSS (.dctlab-whatsapp scope, never leaks into WHMCS admin elsewhere) with design tokens and reusable component classes (page header, card, stat card, button, status badge, table, toolbar, form, alert, empty state, provider card) ready for later phases to use. New minimal foundation JS (loading-button state, copy-to-clipboard, dismissible alerts only - no page logic). Assets load only on this module own pages, not globally across WHMCS admin.',
            'New: navigation regrouped into Messaging / Analytics / Security / Settings, extending the existing dropdown data structure rather than replacing it - every existing endpoint/URL is unchanged, only labels and grouping changed. Fixed a real gap along the way: the parent dropdown itself never showed as active when a child page was current (e.g. viewing Notifications never highlighted the new Messaging menu) - now checked and highlighted correctly.',
            'Audit finding worth recording: confirmed via the actual template code that this module renders using Bootstrap 3.4 and Font Awesome 5 Pro (far/fal icon styles), sharing WHMCS own already-loaded assets rather than loading its own - the new CSS/JS was built accordingly rather than assuming Bootstrap 5.',
            'Deliberately omitted rather than invented: "Send Message" and a separate "Overview" page from the original navigation brief do not exist as standalone pages yet, so no links to them were added. "WhatsApp 2FA > Client/Admin Logs" as a 3-level nested submenu was flattened to a labeled section instead, since Bootstrap 3 dropdowns have no built-in submenu support and adding one was out of scope for a foundation-only phase.',
            'No backend changes: no database, notification, webhook, cron, or API behavior touched in this update.',
        ],
    ],
    [
        'version' => '5.1.3',
        'date' => '2026-08-11',
        'changes' => [
            'Fix (attempt 2): emoji still showed as "????" after the 5.1.2 table-charset migration, confirmed via new messages received after that update still corrupting. That ruled out the table schema as the cause - the destination table being utf8mb4 does not help if the database connection itself is not negotiating utf8mb4 for the session, since MySQL substitutes "?" for unsupported bytes in transit before the data ever reaches the table. Since this module shares WHMCS own database connection rather than opening its own, the connection charset is now explicitly forced to utf8mb4 once per request, early in the boot sequence alongside the timezone fix.',
        ],
    ],
    [
        'version' => '5.1.2',
        'date' => '2026-08-11',
        'changes' => [
            'Fix: emoji in inbound WhatsApp messages showed as "????" instead of the actual character (e.g. a wave emoji) in the WhatsApp Conversations chat view. Root cause: most modern emoji need 4-byte UTF-8 storage, which requires a utf8mb4 column - every CREATE TABLE statement in this module has specified utf8mb4 for a long time, but that only applies when a table is first created. A table created a long time ago under an older charset would never get upgraded just because the code now says utf8mb4 for new installs. Added a migration that actively checks each table live and converts it if needed, rather than assuming the current CREATE TABLE definition reflects reality.',
            'IMPORTANT: this only prevents future corruption - messages already stored as "????" cannot be recovered, since MySQL destroys the original character bytes irreversibly when substituting "?" at insert time under an incompatible charset. New messages received after this update should display correctly.',
        ],
    ],
    [
        'version' => '5.1.1',
        'date' => '2026-08-11',
        'changes' => [
            'Rebranding follow-up: logo.png replaced with the actual DCTLAB logo provided, closing the one gap flagged in the previous rebranding update (text and links were already DCTLAB, but the image file itself was still the old logo). Displays in the navbar at its existing height:20px sizing.',
        ],
    ],
    [
        'version' => '5.1.0',
        'date' => '2026-08-11',
        'changes' => [
            'DCTLAB rebranding, applied properly this time as a tracked change instead of a separate manual edit: composer.json and whmcs.json metadata (name, description, authors, support links) updated to match what was provided; navbar logo alt/title text and link, footer copyright, addon module list author credit, and the (no-op) license description text all changed from Link Nacional to DCTLAB; the base64-embedded old logo image in the module author field removed rather than left showing mismatched branding (the navbar logo.png image file itself still needs a real DCTLAB logo image provided separately - text/links around it are updated, but I cannot generate a company logo); GitHub links (bug reports, wiki, docs, releases) that pointed to the real upstream open-source project this addon was built from, and the Link Nacional commercial "(PRO)" upgrade link, both redirected to DCTLAB support per direction given; EULA.txt updated via straightforward find-replace (LINKNACIONAL -> DCTLAB, the old domain -> the new one) at the explicit request that this specific legal document only needed a simple substitution.',
        ],
    ],
    [
        'version' => '5.0.3',
        'date' => '2026-08-10',
        'changes' => [
            'Fix: WhatsApp Conversations chat list showed the same contact multiple times, once per calendar day they had messaged - a side effect of the derived-conversation-key fix from the billing analytics work, which correctly creates a new billing-window row per day but was never meant to affect this contact list view. getChatConversationsList() now dedupes by phone number, keeping the most recent row per contact, while the underlying table keeps its per-day rows intact for billing purposes.',
            'Also: fixed composer.json (removed a stale platform override that was forcing composer to pretend PHP was 8.1 when the real version comfortably satisfied requirements, and removed setasign/fpdi and setasign/fpdf - confirmed unused anywhere in the codebase, which were also the source of a security-advisory install block). Version string itself was not actually bumped in the previous two delivered zips despite the filenames changing - fixed now so upgrade detection works correctly going forward.',
        ],
    ],
    [
        'version' => '5.0.0',
        'date' => '2026-08-09',
        'changes' => [
            'Major rename, three parts: (1) the separate 2FA module folder renamed from modules/security/lknwa2fa to modules/security/dct2fa, including all its function names and constants. (2) The internal PHP namespace renamed from Lkn\\HookNotification to Dct\\HookNotification throughout the entire codebase - composer.json PSR-4 mapping updated to match, but note the vendor/ folder is not bundled in this zip, so you must run composer install (or composer dump-autoload) on the server after installing this update, or the module will fail with class-not-found errors. (3) Every database table renamed from mod_lkn_hook_notification_* to mod_dct_hook_notification_* - handled automatically via RENAME TABLE (not a copy), so all existing reports, conversations, and history are fully preserved. This runs both through the normal upgrade process and defensively at the start of the webhook schema self-heal check, specifically to close a race where a webhook event arriving before reactivation could otherwise create empty tables under the new names before the real data gets migrated.',
            'Note: the separate 2FA module tables (mod_lkn_wa2fa_*) were intentionally left unrenamed - only the tables matching lkn_hook_notification were part of this request. Let me know if you want those renamed too.',
        ],
    ],
    [
        'version' => '4.7.1',
        'date' => '2026-07-26',
        'changes' => [
            'Fix: "Send Test WhatsApp Message" did not appear on the Client Summary page at all. Checked WHMCS own documented example for this hook rather than continuing to guess - AdminAreaClientSummaryActionLinks expects an array of link strings back, not a single HTML string, which is what the previous version returned. Fixed to match the documented format.',
        ],
    ],
    [
        'version' => '4.7.0',
        'date' => '2026-07-26',
        'changes' => [
            'New: "Send Test WhatsApp Message" on the Admin Area Client Summary page (Other Actions panel) - opens a small modal to send a one-off test message to that client, using any enabled platform. For Meta, pick from a live dropdown of your approved templates and fill in as many parameters as it needs; for Botms.in/Baileys, just type a free-text test message. The phone number field is pre-filled from the client profile but editable, so you can also test a different number.',
            'Note: this is a standalone testing utility, deliberately separate from the real notification system - it does not create a Notification Report entry and does not respect the client WhatsApp opt-out preference, since an admin explicitly testing a number is a different action from an automated notification. Sends and any errors are still recorded in the module log for visibility.',
        ],
    ],
    [
        'version' => '4.6.7',
        'date' => '2026-07-26',
        'changes' => [
            'Fix (confirmed root cause via the diagnostic log): Meta template body-parameter mappings got scrambled whenever a template placeholder text had its {{N}} numbers appear out of order (e.g. "...{{7}}/path?id={{6}}..." - {{7}} appears before {{6}} in the text, which Meta allows). The editor rendered each dropdown using a generic array field name, which PHP numbers by the order fields appear on the page rather than the actual {{N}} each one represents - so out-of-order placeholders got their saved values silently swapped. Each dropdown now carries its real {{N}} explicitly in its field name, so saving is correct regardless of the order placeholders appear in the template text.',
            'IMPORTANT: this only fixes saving going forward - any notification template already saved while affected by this bug (like InvoiceOverdueReminder, confirmed affected) still has the scrambled mapping stored. Open each affected template, re-select the correct parameter for every dropdown, and Save again to correct it.',
        ],
    ],
    [
        'version' => '4.6.6',
        'date' => '2026-07-26',
        'changes' => [
            'Diagnostic (not a confirmed fix yet): investigating a reported bug where Meta template body-parameter mappings with multiple positions get scrambled after Save + reopen. Traced through the save logic (NotificationService::handleWhatsAppPlatformPayloadForm), storage (json_encode into platform_payload), and read-back (NotificationTemplate::getParamCodeForPos) - all three look structurally correct in isolation, so this adds temporary logging at the exact save point ("DIAGNOSTIC: body-parameters save" in the module log) to see the real submitted data on the next Save attempt, rather than guessing at a fix that might not address the actual cause. Please reproduce the issue once more after installing this, then share that specific log entry.',
        ],
    ],
    [
        'version' => '4.6.5',
        'date' => '2026-07-26',
        'changes' => [
            'Fix: UserLoginNotification.php on the server was confirmed to still be an old, unfixed copy - missing both the tblusers_clients ID-mapping fix and the null-safety guards from earlier, plus its own unrelated pre-existing typo (fisrt_name). This file is now bundled directly inside this zip (src/Notifications/UserLoginNotification.php), instead of being delivered as a separate standalone file - delivering it separately was clearly the point of failure, since a full addon update was landing while this one file kept getting missed. It will now update automatically every time this zip is installed, the same as everything else in the module.',
        ],
    ],
    [
        'version' => '4.6.4',
        'date' => '2026-07-24',
        'changes' => [
            'Hardened: the timezone fix from the last update was called unconditionally right at the top of the entrypoint - if missing (e.g. an incomplete file upload of helpers.php) it would crash before NotificationHookListener ever registers, silently disabling every instant notification for that request. Same failure mode as the earlier BulkDispatcher issue. Now guarded with function_exists() and its own isolated try/catch, so this can never again take down anything else in the module, deployment issues included.',
            'IMPORTANT: verify helpers.php on your server actually contains the function lkn_hn_apply_whmcs_timezone (search the live file for that name) - if it does not, the previous update did not fully deploy and needs to be re-uploaded (fully delete the existing dct_whatsapp_notifications folder first, then extract fresh, rather than uploading over it).',
        ],
    ],
    [
        'version' => '4.6.3',
        'date' => '2026-07-24',
        'changes' => [
            'Fix: notification timestamps were consistently off by several hours from WHMCS\'s own displayed times for the same event (matching the UTC-to-local offset, e.g. 5:30 for IST) - the module was using PHP\'s raw server default timezone instead of matching WHMCS\'s own configured Timezone setting (Setup → General Settings → Localisation). Now aligned automatically at the start of every request, so every timestamp this module generates or displays should match what WHMCS itself shows going forward. Past log entries already recorded with the old offset aren\'t retroactively corrected.',
        ],
    ],
    [
        'version' => '4.6.2',
        'date' => '2026-07-23',
        'changes' => [
            'Changed: the "Enable WhatsApp Alerts?" master toggle on the client-area preferences page now uses the Bootstrap Switch plugin (small size, YES/NO labels) instead of a plain checkbox, matching the pill-style toggle look used elsewhere in WHMCS. Uses the current theme\'s own jQuery/bootstrap-switch if already loaded, falling back to loading them from a CDN only if needed.',
        ],
    ],
    [
        'version' => '4.6.1',
        'date' => '2026-07-23',
        'changes' => [
            'Fix: last update wrongly claimed a "Client Area Display" toggle existed under Setup → Addon Modules for showing the WhatsApp Notifications client-area page in the nav - no such setting exists. The correct mechanism is a ClientAreaPrimaryNavbar hook, which is now registered automatically - no admin setting to find or enable. If it still doesn\'t appear in your specific theme\'s nav, the page is always reachable directly at index.php?m=dct_whatsapp_notifications once a client is logged in.',
        ],
    ],
    [
        'version' => '4.6.0',
        'date' => '2026-07-23',
        'changes' => [
            'New: clients can now manage their own WhatsApp notification preferences from the client area (index.php?m=dct_whatsapp_notifications) - a master "Enable WhatsApp Alerts" toggle, plus individual checkboxes per notification type (Payment Confirmation, Ticket Open, Invoice reminders, etc). Enforced centrally, so it applies uniformly across every platform (Meta/Botms.in/Baileys) without needing separate logic per platform.',
            'Note: staff-facing notifications (AdminTicketOpened, AdminTicketUserReplied) and the 2FA login code are intentionally not client-toggleable - the former go to a fixed staff number regardless of any client preference, and disabling 2FA codes would be a security downgrade rather than a notification preference.',
            'Setup step: enable "Client Area Display" for this addon under Setup → Addon Modules so it shows up as a client area menu link automatically (otherwise clients can still reach it directly at index.php?m=dct_whatsapp_notifications once logged in).',
        ],
    ],
    [
        'version' => '4.5.42',
        'date' => '2026-07-23',
        'changes' => [
            'Fix: 2FA Meta send failed with "Button at index 0 must be of type Url" - Meta has two distinct Authentication button types (Copy Code vs one-tap-autofill URL), and the setting only ever supported Copy Code. "Template includes a Copy Code button" is now a proper selector: No button / Copy Code / One-Tap Autofill (URL). IMPORTANT: re-open Settings → WhatsApp Meta and re-select the correct button type for your template (Meta\'s last error told us yours needs "URL"), then save - the old checkbox value doesn\'t map to either new option automatically.',
        ],
    ],
    [
        'version' => '4.5.41',
        'date' => '2026-07-23',
        'changes' => [
            'Fix: 2FA Meta template sends failed with "template name does not exist" - the auto-detected template dropdown was only storing the template name, not its approved language (e.g. "en_US"), so sends used this addon\'s unrelated default notification language instead. The dropdown now encodes both together. IMPORTANT: re-open Settings → WhatsApp Meta and re-select/re-save your 2FA template after installing this update - your currently saved setting is in the old format and needs to be re-saved to pick up the fix (also requires the matching modules/security/lknwa2fa update).',
        ],
    ],
    [
        'version' => '4.5.40',
        'date' => '2026-07-23',
        'changes' => [
            'Fix: "Approximate Total Charges" always showed 0 and every conversation showed category "Unknown" - Meta doesn\'t always include a `conversation` object on status webhook events (confirmed via live payload: neither "sent" nor "delivered" events carried one on this account, only `pricing` did), so the conversation-tracking table never got updated even though billable/category data was available every time. Now falls back to a derived conversation key (recipient + calendar day) whenever Meta omits the conversation object, so this data isn\'t silently dropped.',
            'Note: this only affects new webhook events going forward - conversations already stuck showing "Unknown" from before this update won\'t retroactively fix themselves, since they were created with a different (now unused) key. They\'ll age out naturally as new activity comes in correctly categorized.',
        ],
    ],
    [
        'version' => '4.5.39',
        'date' => '2026-07-11',
        'changes' => [
            'Fix: "Client has no valid phone number" was rejecting legitimate numbers entered without a country code prefix (e.g. local-format Indian numbers). Now auto-prepends the client\'s country dial code before validating, across every notification in the module.',
        ],
    ],
    [
        'version' => '4.5.38',
        'date' => '2026-07-10',
        'changes' => [
            'New: Botms.in sends now auto-retry up to 3 times on error (e.g. "Connection Closed"), 2s apart, instead of failing on the first attempt.',
        ],
    ],
    [
        'version' => '4.5.37',
        'date' => '2026-07-10',
        'changes' => [
            'New: 2FA Authentication Template Name is now an auto-populated dropdown from Meta, instead of manual entry.',
            'Removed: 2FA Template Body Parameters - back to single-code-variable sending.',
            'New: 2FA Delivery Platform setting (Settings → Module) - pick Meta/Botms.in/Baileys, or Auto.',
        ],
    ],
    [
        'version' => '4.5.36',
        'date' => '2026-07-10',
        'changes' => [
            'New: 2FA Template Body Parameters setting - supports multi-variable Meta Authentication templates, not just a single code placeholder.',
        ],
    ],
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
