<?php

use Dct\HookNotification\Core\AdminUI\Http\Controllers\HomepageController;
use Dct\HookNotification\Core\AdminUI\Http\Controllers\LogsController;
use Dct\HookNotification\Core\AdminUI\Http\Controllers\SettingsController;
use Dct\HookNotification\Core\BulkMessaging\Http\Controllers\BulkController;
use Dct\HookNotification\Core\NotificationReport\Http\Controllers\NotificationReportController;
use Dct\HookNotification\Core\NotificationReport\Http\Controllers\TwoFactorAuthLogsController;
use Dct\HookNotification\Core\NotificationReport\Http\Controllers\WhatsAppChatController;
use Dct\HookNotification\Core\Notification\Http\Controllers\NotificationController;

return [
    '404' => [
        'class' => [
            HomepageController::class,
            'notFound404',
        ],
    ],
    'home' => [
        'class' => [
            HomepageController::class,
            'viewHomepage',
        ],
    ],
    'changelog' => [
        'class' => [
            HomepageController::class,
            'viewChangelog',
        ],
    ],
    'notifications' => [
        'class' => [
            NotificationController::class,
            'viewNotificationsTable',
        ],
    ],
    'platforms/{platform}/settings' => [
        'class' => [
            SettingsController::class,
            'viewSettings',
        ],
    ],
    'platforms/{platform}/{subpage}/settings' => [
        'class' => [
            SettingsController::class,
            'viewSubPageSettings',
        ],
    ],
    'platforms/{platform}/notifications' => [
        'class' => [
            NotificationController::class,
            'viewNotificationTemplate',
        ],
    ],
    'notification-reports' => [
        'class' => [
            NotificationReportController::class,
            'viewReports',
        ],
    ],
    'notification-analytics' => [
        'class' => [
            NotificationReportController::class,
            'viewAnalytics',
        ],
    ],
    'notification-conversations' => [
        'class' => [
            NotificationReportController::class,
            'viewConversations',
        ],
    ],
    'notification-chat' => [
        'class' => [
            WhatsAppChatController::class,
            'viewChat',
        ],
    ],
    'notification-2fa-user-logs' => [
        'class' => [
            TwoFactorAuthLogsController::class,
            'viewClientLogs',
        ],
    ],
    'notification-2fa-admin-logs' => [
        'class' => [
            TwoFactorAuthLogsController::class,
            'viewAdminLogs',
        ],
    ],
    'notifications/{notif_code}/templates/{tpl_lang}' => [
        'class' => [
            NotificationController::class,
            'viewNotification',
        ],
    ],
    'bulk/list' => [
        'class' => [
            BulkController::class,
            'viewBulkMessageList',
        ],
    ],
    'bulk/new' => [
        'class' => [
            BulkController::class,
            'viewNewBulkMessage',
        ],
    ],
    'bulks/{bulkId}' => [
        'class' => [
            BulkController::class,
            'viewEditBulk',
        ],
    ],
    'logs' => [
        'class' => [
            LogsController::class,
            'viewLogs',
        ],
    ],
];
