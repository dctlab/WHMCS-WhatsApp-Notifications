<?php

namespace Lkn\HookNotification\Core\Shared\Infrastructure\Setup;

use Lkn\HookNotification\Core\Notification\Infrastructure\Repositories\NotificationRepository;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use Throwable;
use WHMCS\Database\Capsule;

final class DatabaseUpgrade
{
    public static function v230(): void
    {
        $query = Capsule::table('mod_lkn_hook_notification_configs')
            ->where('platform', Platforms::WHATSAPP->value)
            ->where('setting', 'send-to-chatwoot');

        if ($query->exists()) {
            $query = $query->update([
                'platform' => Platforms::CHATWOOT->value,
                'setting' => Settings::CW_LISTEN_WHATSAPP->value,
            ]);
        }
    }

    public static function v200(): void
    {
        // 1. Renammes table
        Capsule::schema()->rename(
            'mod_lkn_hook_notification_platform_settings',
            'mod_lkn_hook_notification_configs'
        );

        // 2. migrates module settings to the table
        $oldConfigs = (array) Capsule::table('tbladdonmodules')
            ->where('module', 'lknhooknotification')
            ->get(['setting', 'value']);

        $updateConfig = function ($setting, $platform, $value): void {
            Capsule::table('mod_lkn_hook_notification_configs')
                ->insert([
                    'platform' => $platform,
                    'setting' => $setting,
                    'value' => $value,
                ]);
        };

        foreach ($oldConfigs as $data) {
            $newSettingName = match ($data->setting) {
                'custom_field_id_whatsapp' => [
                    'setting' => Settings::WP_CUSTOM_FIELD_ID,
                    'platform' => Platforms::WHATSAPP,
                ],
                'whatsapp_user_access_token' => [
                    'setting' => Settings::WP_USER_ACCESS_TOKEN,
                    'platform' => Platforms::WHATSAPP,
                ],
                'whatsapp_phone_number_id' => [
                    'setting' => Settings::WP_PHONE_NUMBER_ID,
                    'platform' => Platforms::WHATSAPP,
                ],
                'chatwoot_url' => [
                    'setting' => Settings::CW_URL,
                    'platform' => Platforms::CHATWOOT,
                ],
                'chatwoot_api_access_token' => [
                    'setting' => Settings::CW_API_ACCESS_TOKEN,
                    'platform' => Platforms::CHATWOOT,
                ],
                'chatwoot_account_id' => [
                    'setting' => Settings::CW_ACCOUNT_ID,
                    'platform' => Platforms::CHATWOOT,
                ],
                'chatwoot_whatsapp_inbox_id' => [
                    'setting' => Settings::CW_WHATSAPP_INBOX_ID,
                    'platform' => Platforms::CHATWOOT,
                ],
                'enable_debug' => [
                    'setting' => Settings::ENABLE_LOG,
                    'platform' => Platforms::MODULE,
                ],
                default => false
            };

            if (is_array($newSettingName)) {
                $updateConfig(
                    $newSettingName['setting']->value,
                    $newSettingName['platform']->value,
                    $data->value
                );
            }
        }

        // 3. Migrates assocs saving format
        $assocs = Capsule::table('mod_lkn_hook_notification_configs')
            ->where('platform', 'whatsapp')
            ->where('setting', 'msg_templates_assoc')
            ->first('value')
            ->value;

        $newAssocs = array_map(function ($assoc): array {
            if ($assoc['hook_id'] === 4) {
                $hook = 'InvoiceReminder';
            } elseif ($assoc['hook_id'] === 5) {
                $hook = 'InvoiceReminderPdf';
            } else {
                $hook = 'OrderCreated';
            }

            $body = array_map(function ($param): array {
                return [
                    'key' => $param['key'],
                    'value' => $param['replace'],
                ];
            }, $assoc['components']['body']);

            if (!empty($assoc['components']['header'])) {
                $header = [
                    'type' => $assoc['components']['header']['type'],
                    'value' => $assoc['components']['header']['replace'],
                ];
            }

            if (!empty($assoc['components']['btn'])) {
                $assocBtn = $assoc['components']['btn'];

                $button = [
                    [
                        'index' => 1,
                        'type' => $assocBtn['type'],
                        'params' => [
                            [
                                'key' => 1,
                                'type' => $assocBtn['type'],
                                'value' => $assocBtn['paramReplace'],
                            ],
                        ],
                    ],
                ];
            }

            $newAssocs = [
                'hook' => $hook,
                'template' => $assoc['tpl_name'],
                'components' => [],
            ];

            if (isset($header)) {
                $newAssocs['components']['header'] = $header;
            }

            $newAssocs['components']['body'] = $body;

            if (isset($button)) {
                $newAssocs['components']['button'] = $button;
            }
            return $newAssocs;
        }, json_decode($assocs, true));

        Capsule::table('mod_lkn_hook_notification_configs')
            ->where('platform', 'whatsapp')
            ->where('setting', 'msg_templates_assoc')
            ->update(['value' => json_encode($newAssocs)]);
    }

    public static function v310(): void
    {
        $assocs = lkn_hn_config(Settings::WP_MSG_TEMPLATE_ASSOCS);

        $newAssocsFormat = array_map(
            function ($assoc): array {
                if (empty($assoc['components']['header'])) {
                    $header = [];
                } else {
                    $header = [
                        [
                            'key' => '1',
                            'type' => $assoc['components']['header']['type'] === 'doc' ? 'document' : 'text',
                            'value' => $assoc['components']['header']['value'],
                        ],
                    ];
                }

                if (empty($assoc['components']['body'])) {
                    $body = [];
                } else {
                    $body = array_map(
                        function (array $assoc): array {
                            return [
                                'key' => $assoc['key'],
                                'value' => $assoc['value'],
                                'type' => 'text',
                            ];
                        },
                        $assoc['components']['body']
                    );
                }

                if (empty($assoc['components']['button'])) {
                    $buttons = [];
                } else {
                    $buttons = array_map(
                        function (array $btn): array {
                            $params = array_map(
                                function (array $param): array {
                                    return [
                                        'key' => $param['key'],
                                        'value' => $param['value'],
                                    ];
                                },
                                $btn['params']
                            );

                            return [
                                'index' => $btn['index'],
                                'type' => $btn['type'],
                                'params' => $params,
                            ];
                        },
                        $assoc['components']['button']
                    );
                }

                return [
                    'notification' => $assoc['notification'],
                    'template' => $assoc['template'],
                    'components' => [
                        'header' => $header,
                        'body' => $body,
                        'button' => $buttons,
                    ],
                ];
            },
            $assocs
        );

        lkn_hn_config_set(
            Platforms::WHATSAPP,
            Settings::WP_MSG_TEMPLATE_ASSOCS,
            json_encode($newAssocsFormat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function v320(): void
    {
        Capsule::connection()->statement('ALTER TABLE mod_lkn_hook_notification_reports MODIFY client_id INT(10) NULL');
        Capsule::connection()->statement('ALTER TABLE mod_lkn_hook_notification_reports MODIFY category VARCHAR(20) NULL');
        Capsule::connection()->statement('ALTER TABLE mod_lkn_hook_notification_reports MODIFY category_id BIGINT UNSIGNED NULL');

        $newIdentifierHash         = md5(time());
        $chatwootModIdentifierHash = null;

        if (Capsule::schema()->hasTable('mod_chatwoot')) {
            $modChatwootSigningHash = Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first('value')->value;

            if (!is_null($modChatwootSigningHash)) {
                $chatwootModIdentifierHash = $modChatwootSigningHash;
            }
        }

        $identifierHash = $chatwootModIdentifierHash ?? $newIdentifierHash;

        lkn_hn_config_set(Platforms::CHATWOOT, Settings::CW_CLIENT_IDENTIFIER_KEY, $identifierHash);
    }

    public static function v330(): void
    {
        $activeChatwootNotifs = json_decode(Capsule::table('mod_lkn_hook_notification_configs')
            ->where('platform', Platforms::CHATWOOT->value)
            ->where('setting', Settings::CW_ACTIVE_NOTIFS->value)
            ->value('value'), true);

        if (!is_array($activeChatwootNotifs)) {
            return;
        }

        $activeChatwootNotifs = array_map(function (string $item) {
            return [
                'code' => $item,
                'settings' => [],
            ];
        }, $activeChatwootNotifs);

        Capsule::table('mod_lkn_hook_notification_configs')
            ->where('platform', Platforms::CHATWOOT->value)
            ->where('setting', Settings::CW_ACTIVE_NOTIFS->value)
            ->update(['value' => json_encode($activeChatwootNotifs)]);
    }

    public static function v370(): void
    {
        $assocs      = lkn_hn_config(Settings::WP_MSG_TEMPLATE_ASSOCS);
        $defaultLang = lkn_hn_config(Settings::WP_MSG_TEMPLATE_LANG) ?? 'pt_BR';

        $newAssocsFormat = array_map(
            function ($assoc) use ($defaultLang): array {
                return [
                    'notification' => $assoc['notification'],
                    'language' => $defaultLang,
                    'template' => $assoc['template'],
                    'components' => [
                        'header' => $assoc['components']['header'],
                        'body' => $assoc['components']['body'],
                        'button' => $assoc['components']['button'],
                    ],
                ];
            },
            $assocs
        );

        lkn_hn_config_set(Platforms::WHATSAPP, Settings::WP_MSG_TEMPLATE_ASSOCS, $newAssocsFormat);
    }

    public static function v380(): void
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            'CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_localized_tpls (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    notif_code VARCHAR(255) NOT NULL,
                    lang VARCHAR(255) NOT NULL,
                    tpl LONGTEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $statement->execute();
    }

    public static function v390(): void
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        $statement = $pdo->prepare(
            'ALTER TABLE `mod_lkn_hook_notification_localized_tpls` ADD `platform` VARCHAR(255) NOT NULL AFTER `notif_code`;'
        );

        $statement->execute();
    }

    public static function v400()
    {
        try {
            $pdo = Capsule::connection()->getPdo();
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_bulks (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    `status` VARCHAR(255) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    `description` TEXT,
                    platform VARCHAR(255) NULL,
                    template TEXT NOT NULL,
                    start_at DATETIME NOT NULL,
                    max_concurrency INT NOT NULL,
                    filters TEXT null,
                    progress FLOAT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    completed_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
            );

            $statement->execute();

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_notif_queue (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    bulk_id INT NULL,
                    client_id INT NOT NULL,
                    `status` VARCHAR(255) NOT NULL,
                    notif_code VARCHAR(255) NULL,
                    FOREIGN KEY (bulk_id) REFERENCES mod_lkn_hook_notification_bulks(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
            );

            $statement->execute();

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_reports
                ADD COLUMN target VARCHAR(255) NULL AFTER category
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_reports
                ADD COLUMN msg TEXT NULL AFTER status
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_reports
                MODIFY COLUMN platform VARCHAR(255) NULL
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_reports
                ADD COLUMN queue_id INT NULL AFTER category_id
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_reports
                ADD CONSTRAINT fk_queue_id
                FOREIGN KEY (queue_id) REFERENCES mod_lkn_hook_notification_notif_queue(id)
                ON DELETE SET NULL
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_localized_tpls
                MODIFY COLUMN platform VARCHAR(255) NULL
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_localized_tpls
                ADD COLUMN platform_payload TEXT NULL AFTER platform
            ');

            Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_localized_tpls
                MODIFY COLUMN tpl LONGTEXT NULL
            ');

            Capsule::table('mod_lkn_hook_notification_configs')
                ->limit(1)
                ->insert([
                    'platform' => 'wp',
                    'setting' => Settings::WP_META_ENABLE->value,
                    'value' => 'on',
                ]);

            Capsule::table('mod_lkn_hook_notification_configs')
                ->where('setting', 'phone_number_id')
                ->limit(1)
                ->update(['setting' => 'business_phone_number_id']);

            Capsule::table('mod_lkn_hook_notification_localized_tpls')
                ->where('lang', 'en_GB')
                ->update(['lang' => 'en_001']);

            Capsule::table('mod_lkn_hook_notification_localized_tpls')
                ->where('lang', 'en')
                ->update(['lang' => 'en_001']);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            $wpLegacyAssoc   = lkn_hn_config(Settings::WP_MSG_TEMPLATE_ASSOCS);
            $newFormatAssocs = [];

            foreach ($wpLegacyAssoc as $assoc) {
                $hookExists = Hooks::tryFrom($assoc['notification']);

                $notificationCode = $hookExists
                    ? 'Default' . strtoupper($hookExists->value)
                    : $assoc['notification'];

                $newFormatAssocs[] = [
                    'notif_code' => $notificationCode,
                    'platform' => Platforms::WHATSAPP->value,
                    'lang' => $assoc['language'],
                    'tpl' => $assoc['template'],
                    'platform_payload' => $assoc['components'],
                ];
            }

            $notificationRepository = new NotificationRepository();
            $results                = [];

            foreach ($newFormatAssocs as $newFormat) {
                $results[] = $notificationRepository->createNotificationTemplate(
                    $newFormat['notif_code'],
                    $newFormat['platform'],
                    $newFormat['lang'],
                    $newFormat['tpl'],
                    $newFormat['platform_payload'],
                );
            }

            lkn_hn_log('Database 4.0.0 upgrade', null, $results);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            lkn_hn_log('Database 4.0.0 upgrade failed', null, $e->__toString());
        }
    }

    public static function v412(): void
    {
        try {
            $result = Capsule::connection()->statement('
                ALTER TABLE `mod_lkn_hook_notification_reports` CHANGE `category_id` `category_id` BIGINT UNSIGNED NULL DEFAULT NULL;
            ');

            $result2 = Capsule::connection()->statement('
                ALTER TABLE `mod_lkn_hook_notification_reports` CHANGE `category` `category` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
            ');

            lkn_hn_log('Database 4.1.2 success', null, ['result' => $result, 'result2'=> $result2]);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.1.2 upgrade failed', null, $th->__toString());
        }
    }

    public static function v430(): void
    {
        try {
            $result1 = Capsule::connection()->statement('
                ALTER TABLE mod_lkn_hook_notification_bulks
                ADD COLUMN platform_payload TEXT NULL AFTER platform
            ');

            lkn_hn_log('Database 4.3.0 success', null, ['result1' => $result1]);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.3.0 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Adds WhatsApp delivery status tracking (sent/delivered/read/failed),
     * resend support and conversation analytics.
     */
    public static function v450(): void
    {
        try {
            $columns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_reports');

            if (!in_array('wa_message_id', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN wa_message_id VARCHAR(255) NULL AFTER target
                ');
            }

            if (!in_array('delivery_status', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN delivery_status VARCHAR(50) NULL AFTER wa_message_id
                ');
            }

            if (!in_array('delivery_updated_at', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN delivery_updated_at DATETIME NULL AFTER delivery_status
                ');
            }

            if (!in_array('whmcs_hook_params', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN whmcs_hook_params LONGTEXT NULL AFTER msg
                ');
            }

            if (!in_array('resent_from_report_id', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN resent_from_report_id INT NULL AFTER queue_id
                ');
            }

            if (!Capsule::schema()->hasTable('mod_lkn_hook_notification_reports_idx_wa')) {
                // Indexes cannot use IF NOT EXISTS on older MySQL, so guard with information_schema.
                $hasIndex = Capsule::connection()->select("
                    SELECT COUNT(1) as total FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'mod_lkn_hook_notification_reports'
                    AND INDEX_NAME = 'idx_wa_message_id'
                ");

                if (($hasIndex[0]->total ?? 0) == 0) {
                    Capsule::connection()->statement('
                        ALTER TABLE mod_lkn_hook_notification_reports
                        ADD INDEX idx_wa_message_id (wa_message_id),
                        ADD INDEX idx_client_id (client_id),
                        ADD INDEX idx_category (category, category_id)
                    ');
                }
            }

            Capsule::connection()->statement('
                CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_conversations (
                    id INT NOT NULL AUTO_INCREMENT,
                    conversation_id VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    category VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    pricing_model VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    origin_type VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    message_count INT NOT NULL DEFAULT 1,
                    first_message_at DATETIME DEFAULT NULL,
                    last_message_at DATETIME DEFAULT NULL,
                    expiration_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_conversation_id (conversation_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');

            lkn_hn_log('Database 4.5.0 success', null, []);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.5.0 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Adds the billable flag and client/phone linkage to the conversation
     * analytics table, so conversations can be searched/filtered by client
     * and their billing status shown (billable vs free-tier/unknown).
     */
    public static function v451(): void
    {
        try {
            $columns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_conversations');

            if (!in_array('billable', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_conversations
                    ADD COLUMN billable TINYINT(1) NULL AFTER pricing_model
                ');
            }

            if (!in_array('client_id', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_conversations
                    ADD COLUMN client_id INT NULL AFTER conversation_id
                ');
            }

            if (!in_array('phone_number', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_conversations
                    ADD COLUMN phone_number VARCHAR(255) NULL AFTER client_id
                ');
            }

            $hasIndex = Capsule::connection()->select("
                SELECT COUNT(1) as total FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'mod_lkn_hook_notification_conversations'
                AND INDEX_NAME = 'idx_client_id'
            ");

            if (($hasIndex[0]->total ?? 0) == 0) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_conversations
                    ADD INDEX idx_client_id (client_id),
                    ADD INDEX idx_phone_number (phone_number)
                ');
            }

            lkn_hn_log('Database 4.5.1 success', null, []);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.5.1 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Adds the last-message preview/direction to conversations, so an actual
     * received message (not just a bumped counter) is visible on the
     * WhatsApp Conversations page.
     */
    public static function v452(): void
    {
        try {
            $columns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_conversations');

            if (!in_array('last_message_preview', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_conversations
                    ADD COLUMN last_message_preview VARCHAR(500) NULL AFTER last_message_at
                ');
            }

            if (!in_array('last_message_direction', $columns, true)) {
                Capsule::connection()->statement("
                    ALTER TABLE mod_lkn_hook_notification_conversations
                    ADD COLUMN last_message_direction VARCHAR(20) NULL AFTER last_message_preview
                ");
            }

            lkn_hn_log('Database 4.5.6 success', null, []);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.5.6 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Adds a full message history table (both inbound and outbound), powering
     * the live WhatsApp Conversations chat view.
     */
    public static function v453(): void
    {
        try {
            Capsule::connection()->statement('
                CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_messages (
                    id INT NOT NULL AUTO_INCREMENT,
                    client_id INT DEFAULT NULL,
                    phone_number VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    wa_message_id VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    direction VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL,
                    message_type VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    body TEXT COLLATE utf8mb4_unicode_ci,
                    status VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    sent_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_wa_message_id (wa_message_id),
                    KEY idx_phone_number (phone_number),
                    KEY idx_client_id (client_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');

            lkn_hn_log('Database 4.5.7 success', null, []);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.5.7 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Adds message-level billable/conversation-category columns to the
     * reports table (as opposed to the conversation-level ones already on
     * mod_lkn_hook_notification_conversations), so the Reports page and
     * Message Analytics can show/report on a per-message basis.
     */
    public static function v454(): void
    {
        try {
            $columns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_reports');

            if (!in_array('billable', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN billable TINYINT(1) NULL AFTER delivery_updated_at
                ');
            }

            if (!in_array('wa_category', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN wa_category VARCHAR(50) NULL AFTER billable
                ');
            }

            lkn_hn_log('Database 4.5.8 success', null, []);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.5.8 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Adds a message_preview column to the reports table, storing a
     * human-readable snapshot of what was actually sent (resolved template
     * parameter values), for the "what did this client receive" view on the
     * Notification Reports page.
     */
    public static function v455(): void
    {
        try {
            $columns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_reports');

            if (!in_array('message_preview', $columns, true)) {
                Capsule::connection()->statement('
                    ALTER TABLE mod_lkn_hook_notification_reports
                    ADD COLUMN message_preview TEXT NULL AFTER msg
                ');
            }

            lkn_hn_log('Database 4.5.13 success', null, []);
        } catch (Throwable $th) {
            lkn_hn_log('Database 4.5.13 upgrade failed', null, $th->__toString());
        }
    }

    /**
     * Idempotent, cheap self-heal for the delivery-tracking/conversation-analytics
     * schema (v450/v451).
     *
     * WHMCS only calls the module's `_upgrade()` hook when an admin loads the
     * Setup > Addon Modules page (or the module's config page) *after* the code
     * on disk was updated. If the files are updated in place without that page
     * ever being visited, the new columns/table never get created even though
     * the new code is already running — most visibly, the WhatsApp status
     * webhook then silently fails to write delivery status / conversations
     * (the error is caught and only shows up in the module log).
     *
     * This method is safe to call on every request: each check is a fast
     * metadata lookup, and it is a no-op once the schema is up to date.
     *
     * @since 4.5.3
     */
    public static function ensureDeliveryTrackingSchema(): void
    {
        try {
            if (!Capsule::schema()->hasTable('mod_lkn_hook_notification_reports')) {
                // Module was never activated; nothing to heal here.
                return;
            }

            $reportColumns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_reports');

            $missingV450 = !in_array('delivery_status', $reportColumns, true)
                || !in_array('wa_message_id', $reportColumns, true)
                || !in_array('whmcs_hook_params', $reportColumns, true)
                || !in_array('resent_from_report_id', $reportColumns, true)
                || !Capsule::schema()->hasTable('mod_lkn_hook_notification_conversations');

            if ($missingV450) {
                lkn_hn_log('Delivery tracking schema self-heal: applying v450', [], []);
                self::v450();
            }

            if (Capsule::schema()->hasTable('mod_lkn_hook_notification_conversations')) {
                $conversationColumns = Capsule::schema()->getColumnListing('mod_lkn_hook_notification_conversations');

                $missingV451 = !in_array('billable', $conversationColumns, true)
                    || !in_array('client_id', $conversationColumns, true)
                    || !in_array('phone_number', $conversationColumns, true);

                if ($missingV451) {
                    lkn_hn_log('Delivery tracking schema self-heal: applying v451', [], []);
                    self::v451();
                }

                $missingV452 = !in_array('last_message_preview', $conversationColumns, true)
                    || !in_array('last_message_direction', $conversationColumns, true);

                if ($missingV452) {
                    lkn_hn_log('Delivery tracking schema self-heal: applying v452', [], []);
                    self::v452();
                }
            }

            if (!Capsule::schema()->hasTable('mod_lkn_hook_notification_messages')) {
                lkn_hn_log('Delivery tracking schema self-heal: applying v453', [], []);
                self::v453();
            }

            $reportColumns = $reportColumns ?? Capsule::schema()->getColumnListing('mod_lkn_hook_notification_reports');

            $missingV454 = !in_array('billable', $reportColumns, true)
                || !in_array('wa_category', $reportColumns, true);

            if ($missingV454) {
                lkn_hn_log('Delivery tracking schema self-heal: applying v454', [], []);
                self::v454();
            }

            $missingV455 = !in_array('message_preview', $reportColumns, true);

            if ($missingV455) {
                lkn_hn_log('Delivery tracking schema self-heal: applying v455', [], []);
                self::v455();
            }
        } catch (Throwable $th) {
            lkn_hn_log('Delivery tracking schema self-heal failed', [], ['exception' => $th->__toString()]);
        }
    }
}
