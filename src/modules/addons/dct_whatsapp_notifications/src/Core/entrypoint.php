<?php

/**
 * This is the entrypoing for automatic notification triggering based on WHMCS
 * add_hook.
 *
 * This file has the same function as hooks.php. But:
 *
 * since hooks.php cannot be encoded, the module uses this file to put its
 * code because this one can be encoded.
 */

use Dct\HookNotification\Core\AdminUI\Http\Controllers\HomepageController;
use Dct\HookNotification\Core\BulkMessaging\Infrastructure\BulkDispatcher;
use Dct\HookNotification\Core\Notification\Infrastructure\ManualNotificationHookListener;
use Dct\HookNotification\Core\Notification\Infrastructure\NotificationHookListener;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Wrap inside a function to avoid naming conflicts.
 *
 * @return void
 */
function lkn_hn_entrypoint()
{
    try {
        require_once __DIR__ . '/Shared/Infrastructure/helpers.php';

        // PHP's own default timezone (whatever the server/php.ini sets, often
        // UTC) is what date()/new DateTime() use unless explicitly told
        // otherwise - but WHMCS's own displayed timestamps (emails, admin
        // logs) use the timezone configured in Setup > General Settings >
        // Localisation. Without aligning the two, every timestamp this
        // module generates or displays can be hours off from what WHMCS
        // itself shows for the exact same real-world event. Done first,
        // before anything else runs, so every subsequent date()/DateTime
        // call in this request benefits from it.
        //
        // Isolated on purpose, same reasoning as BulkDispatcher below: this
        // must never be able to take down the rest of the entrypoint (most
        // importantly NotificationHookListener) - including if it's simply
        // not present yet due to an incomplete file deployment.
        try {
            if (function_exists('lkn_hn_apply_whmcs_timezone')) {
                lkn_hn_apply_whmcs_timezone();
            }
        } catch (Throwable $th) {
            lkn_hn_log('Timezone alignment error', [], ['exception' => $th->__toString()]);
        }

        // Forces the shared WHMCS DB connection to utf8mb4 for this request,
        // so emoji and other 4-byte UTF-8 characters don't get silently
        // corrupted to "?" in transit before ever reaching a table -
        // regardless of what charset the connection negotiated by default.
        // See lkn_hn_apply_utf8mb4_connection() for the full reasoning.
        // Isolated for the same reason as the timezone fix above.
        try {
            if (function_exists('lkn_hn_apply_utf8mb4_connection')) {
                lkn_hn_apply_utf8mb4_connection();
            }
        } catch (Throwable $th) {
            lkn_hn_log('utf8mb4 connection charset error', [], ['exception' => $th->__toString()]);
        }

        require_once __DIR__ . '/Platforms/Chatwoot/Infrastructure/live_chat_hooks.php';

        // Isolated on purpose: if BulkMessaging fails to load for any reason
        // (e.g. a missing/stale file from an incomplete deployment), it must
        // not prevent NotificationHookListener below from registering - that
        // would silently disable every instant notification (PaymentConfirmation,
        // TicketOpen, InvoicePaymentReminder, etc.) for the whole request.
        try {
            BulkDispatcher::getInstance()->run();
        } catch (Throwable $th) {
            lkn_hn_log(
                'Bulk messaging error',
                [],
                [
                    'msg' => $th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                    'trace' => $th->getTraceAsString(),
                    'to_string' => $th->__toString(),
                ]
            );
        }

        (new NotificationHookListener())->listen();

        /**
         * Currently, only the hook AdminInvoicesControlsOutput has manual notification.
         */
        (new ManualNotificationHookListener())->listenFor(
            Hooks::ADMIN_INVOICES_CONTROLS_OUTPUT
        );

        add_hook(
            'AdminAreaHeadOutput',
            999,
            function (): ?string {
                if ($_GET['module'] !== 'dct_whatsapp_notifications' || $_GET['page'] !== 'bulk/new') {
                    return null;
                }

                return '<link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css"/>';
            }
        );

        add_hook('AdminHomepage', 999, function (): ?string {
            try {
                return (new HomepageController(new View()))->newVersion();
            } catch (Throwable $th) {
                lkn_hn_log(
                    'Version check error',
                    [],
                    [
                        'error' => $th->__toString(),
                    ]
                );

                return null;
            }
        });

        add_hook(
            'DailyCronJob',
            999,
            function (): void {
                try {
                    (new HomepageController(new View()))->handleNewVersionCheck();
                } catch (Throwable $th) {
                    lkn_hn_log(
                        'Version check error',
                        [],
                        [
                            'error' => $th->__toString(),
                        ]
                    );
                }
            }
        );

        // Adds "WhatsApp Notifications" to the client area's primary nav
        // menu, under the account/settings dropdown - links to this addon's
        // clientarea() page where a client manages their own notification
        // preferences. Isolated in its own try/catch: a failure here (e.g. a
        // WHMCS version where the parent menu name differs) must not affect
        // anything else this module does.
        try {
            add_hook('ClientAreaPrimaryNavbar', 1, function ($primaryNavbar) {
                $parent = $primaryNavbar->getChild('Account') ?? $primaryNavbar;

                $parent->addChild('lknWhatsAppNotifications', [
                    'label' => lkn_hn_lang('WhatsApp Notifications'),
                    'uri' => 'index.php?m=dct_whatsapp_notifications',
                    'icon' => 'fa-brands fa-whatsapp',
                    'order' => 700,
                ]);
            });
        } catch (Throwable $th) {
            lkn_hn_log(
                'ClientAreaPrimaryNavbar hook error',
                [],
                ['exception' => $th->__toString()]
            );
        }

        // Adds a "Send Test WhatsApp Message" action link (and its modal) to
        // the Admin Area Client Summary page - lets an admin send a one-off
        // test message to a client without going through a real notification
        // template. Isolated in its own try/catch, same reasoning as above:
        // this is a convenience utility, not core to the module, and must
        // never be able to break anything else if it fails to load/render.
        try {
            $testSendController = new \Dct\HookNotification\Core\TestSend\Http\Controllers\TestSendController();

            add_hook('AdminAreaClientSummaryActionLinks', 1, function ($vars) use ($testSendController) {
                return $testSendController->renderActionLink($vars);
            });

            add_hook('AdminAreaClientSummaryPage', 1, function ($vars) use ($testSendController) {
                return $testSendController->renderModal($vars);
            });
        } catch (Throwable $th) {
            lkn_hn_log(
                'TestSend hooks error',
                [],
                ['exception' => $th->__toString()]
            );
        }
    } catch (Throwable $th) {
        lkn_hn_log(
            'general error',
            [],
            [
                'msg' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
                'to_string' => $th->__toString(),
            ]
        );
    }
}

lkn_hn_entrypoint();

add_hook('AdminAreaHeadOutput', 1, function ($vars) {
    $output = <<<HTML
<link
    rel="stylesheet"
    href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css"
/>
HTML;

    // Module-specific assets: only loaded on this module's own admin pages
    // (?module=dct_whatsapp_notifications), not globally across WHMCS admin,
    // per the Phase 1 brief's asset-loading guidance. The existing DataTables
    // <link> above already loads unconditionally - left as-is, since
    // changing that is outside this module's own asset scoping and not part
    // of this change.
    if (($_GET['module'] ?? '') === 'dct_whatsapp_notifications') {
        try {
            $systemUrl = rtrim(
                (string) \WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'),
                '/'
            );

            $output .= <<<HTML
<link
    rel="stylesheet"
    href="{$systemUrl}/modules/addons/dct_whatsapp_notifications/assets/css/dctlab-whatsapp.css"
/>
<script src="{$systemUrl}/modules/addons/dct_whatsapp_notifications/assets/js/dctlab-whatsapp.js"></script>
HTML;
        } catch (Throwable $th) {
            lkn_hn_log('DCTLAB foundation assets failed to load', [], ['exception' => $th->__toString()]);
        }
    }

    return $output;
});
