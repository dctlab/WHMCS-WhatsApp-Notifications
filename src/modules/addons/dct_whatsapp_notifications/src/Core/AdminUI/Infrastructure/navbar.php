<?php

use Dct\HookNotification\Core\AdminUI\Application\Services\LicenseService;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;

/**
 * Phase 1 navigation regroup (UI foundation): all existing 'endpoint'
 * values are unchanged from before this regroup - only the grouping/labels
 * changed, so every existing URL, bookmark, and permission check tied to
 * an endpoint string keeps working exactly as it did.
 *
 * Two things from the original brief's suggested structure are
 * deliberately NOT included, since inventing them would mean linking to
 * pages that do not exist:
 *  - "Send Message" under Messaging: no standalone page exists for this
 *    yet (the closest existing feature is the Test Send modal on WHMCS's
 *    own Admin Client Summary page, a different context entirely).
 *  - "Overview" under Analytics: no separate Analytics-overview page
 *    exists distinct from the Dashboard/homepage - linking both here
 *    would just be two links to the same page.
 *
 * "WhatsApp 2FA > Client Logs / Admin Logs" is flattened to a labeled
 * section (matching the existing Settings dropdown's divider pattern)
 * rather than a true 3rd-level nested submenu - Bootstrap 3's dropdown
 * component (confirmed in use here, not Bootstrap 5) has no built-in
 * submenu support, so a real 3-level nest would need new custom JS/CSS
 * beyond what "extend the existing dropdown implementation" calls for.
 */
return [
    'left' => [
        [
            'label' => lkn_hn_lang('Dashboard'),
            'endpoint' => 'home',
            'icon' => 'far fa-home-alt',
        ],
        [
            'label' => lkn_hn_lang('Messaging'),
            'icon' => 'far fa-paper-plane',
            'items' => [
                [
                    'label' => lkn_hn_lang('Notifications'),
                    'endpoint' => 'notifications',
                    'icon' => 'far fa-bell',
                ],
                [
                    'label' => lkn_hn_lang('Bulk Messaging'),
                    'endpoint' => 'bulk/list',
                    'icon' => 'far fa-mail-bulk',
                    'show' => lkn_hn_config(Settings::BULK_ENABLE),
                ],
                [
                    'label' => lkn_hn_lang('Conversations'),
                    'endpoint' => 'notification-chat',
                    'icon' => 'fal fa-comments-alt',
                ],
            ],
        ],
        [
            'label' => lkn_hn_lang('Analytics'),
            'icon' => 'fal fa-chart-line',
            'items' => [
                [
                    'label' => lkn_hn_lang('Notification Reports'),
                    'endpoint' => 'notification-reports',
                    'icon' => 'fal fa-table',
                ],
                [
                    'label' => lkn_hn_lang('Usage & Billing'),
                    'endpoint' => 'notification-analytics',
                    'icon' => 'fal fa-chart-bar',
                ],
            ],
        ],
        [
            'label' => lkn_hn_lang('Security'),
            'icon' => 'far fa-shield-check',
            'items' => [
                ['divisor' => true, 'title' => lkn_hn_lang('WhatsApp 2FA'), 'icon' => 'fal fa-key'],
                [
                    'label' => lkn_hn_lang('Client Logs'),
                    'endpoint' => 'notification-2fa-user-logs',
                    'icon' => 'fal fa-user',
                ],
                [
                    'label' => lkn_hn_lang('Admin Logs'),
                    'endpoint' => 'notification-2fa-admin-logs',
                    'icon' => 'fal fa-user-shield',
                ],
            ],
        ],
        [
            'label' => lkn_hn_lang('Settings'),
            'icon' => 'far fa-cog',
            'items' => [
                ['divisor' => true, 'title' => lkn_hn_lang('Module'), 'icon' => 'fal fa-comment'],
                [
                    'label' => lkn_hn_lang('General'),
                    'endpoint' => 'platforms/mod/settings',
                    'icon' => 'fal fa-cog',
                ],
                [
                    'label' => lkn_hn_lang('Bulk Message'),
                    'endpoint' => 'platforms/bulk/settings',
                    'icon' => 'fal fa-cog',
                ],
                ['divisor' => true, 'title' => lkn_hn_lang('WhatsApp Providers'), 'icon' => 'fal fa-plug'],
                [
                    'label' => lkn_hn_lang('Evolution API'),
                    'endpoint' => 'platforms/wp-evo/settings',
                    'icon' => 'fal fa-cog',
                    'block' => LicenseService::getInstance()->mustBlockProFeatures(),
                ],
                [
                    'label' => lkn_hn_lang('Baileys'),
                    'endpoint' => 'platforms/baileys/settings',
                    'icon' => 'fal fa-cog',
                    'block' => LicenseService::getInstance()->mustBlockProFeatures(),
                ],
                [
                    'label' => lkn_hn_lang('Botms.in'),
                    'endpoint' => 'platforms/botms/settings',
                    'icon' => 'fal fa-cog',
                    'block' => LicenseService::getInstance()->mustBlockProFeatures(),
                ],
                [
                    'label' => lkn_hn_lang('WhatsApp Meta'),
                    'endpoint' => 'platforms/wp/settings',
                    'icon' => 'fal fa-cog',
                ],
                ['divisor' => true, 'title' => lkn_hn_lang('Chatwoot'), 'icon' => 'fal fa-comment'],
                [
                    'label' => lkn_hn_lang('Settings'),
                    'endpoint' => 'platforms/cw/settings',
                    'icon' => 'fal fa-cog',
                ],
                [
                    'label' => lkn_hn_lang('Integration'),
                    'endpoint' => 'platforms/cw/live-chat/settings',
                    'icon' => 'fal fa-headset',
                ],
            ],
        ]
    ],
    'right' => [
        [
            'label' => lkn_hn_lang('Help'),
            'icon' => 'far fa-question-circle',
            'items' => [
                [
                    'label' => lkn_hn_lang('Report Error'),
                    'url' => 'https://dctlab.directcybertech.com/',
                    'external' => true,
                    'icon' => 'far fa-exclamation-triangle',
                ],
                [
                    'label' => lkn_hn_lang('Logs'),
                    'icon' => 'fal fa-bug',
                    'endpoint' => 'logs'
                ],
                [
                    'icon' => 'glyphicon glyphicon-download',
                    'label' => 'v5.12.0',
                    'external' => true,
                    'url' => 'https://dctlab.directcybertech.com/'
                ]
            ],
        ],
    ],
];
