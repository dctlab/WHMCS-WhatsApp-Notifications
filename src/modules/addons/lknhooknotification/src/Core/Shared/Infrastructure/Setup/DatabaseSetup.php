<?php

namespace Lkn\HookNotification\Core\Shared\Infrastructure\Setup;

use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Throwable;
use WHMCS\Database\Capsule;

final class DatabaseSetup
{
    public static function activate(): array
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        try {
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

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS `mod_lkn_hook_notification_reports` (
                    `id` int NOT NULL AUTO_INCREMENT,
                    `client_id` int DEFAULT NULL,
                    `category_id` bigint unsigned DEFAULT NULL,
                    `queue_id` int DEFAULT NULL,
                    `resent_from_report_id` int DEFAULT NULL,
                    `category` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `target` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `wa_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `delivery_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `delivery_updated_at` datetime DEFAULT NULL,
                    `billable` tinyint(1) DEFAULT NULL,
                    `wa_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `msg` text COLLATE utf8mb4_unicode_ci,
                    `message_preview` text COLLATE utf8mb4_unicode_ci,
                    `whmcs_hook_params` longtext COLLATE utf8mb4_unicode_ci,
                    `platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `channel` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `notification` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `hook` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `fk_queue_id` (`queue_id`),
                    KEY `idx_wa_message_id` (`wa_message_id`),
                    KEY `idx_client_id` (`client_id`),
                    KEY `idx_category` (`category`, `category_id`),
                    CONSTRAINT `fk_queue_id` FOREIGN KEY (`queue_id`) REFERENCES `mod_lkn_hook_notification_notif_queue` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            $statement->execute();

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS `mod_lkn_hook_notification_conversations` (
                    `id` int NOT NULL AUTO_INCREMENT,
                    `conversation_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `client_id` int DEFAULT NULL,
                    `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `pricing_model` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `billable` tinyint(1) DEFAULT NULL,
                    `origin_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `message_count` int NOT NULL DEFAULT 1,
                    `first_message_at` datetime DEFAULT NULL,
                    `last_message_at` datetime DEFAULT NULL,
                    `last_message_preview` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `last_message_direction` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `expiration_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uniq_conversation_id` (`conversation_id`),
                    KEY `idx_client_id` (`client_id`),
                    KEY `idx_phone_number` (`phone_number`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            $statement->execute();

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS `mod_lkn_hook_notification_messages` (
                    `id` int NOT NULL AUTO_INCREMENT,
                    `client_id` int DEFAULT NULL,
                    `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `wa_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `direction` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `message_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `body` text COLLATE utf8mb4_unicode_ci,
                    `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `sent_at` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uniq_wa_message_id` (`wa_message_id`),
                    KEY `idx_phone_number` (`phone_number`),
                    KEY `idx_client_id` (`client_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            $statement->execute();

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_configs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    platform VARCHAR(255) NOT NULL,
                    setting VARCHAR(255) NOT NULL,
                    value LONGTEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
            );

            $statement->execute();

            $statement = $pdo->prepare(
                'CREATE TABLE IF NOT EXISTS mod_lkn_hook_notification_localized_tpls (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    notif_code VARCHAR(255) NOT NULL,
                    platform VARCHAR(255) NOT NULL,
                    platform_payload TEXT NULL,
                    lang VARCHAR(255) NOT NULL,
                    tpl LONGTEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
            );

            $statement->execute();

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            $newIdentifierHash         = bin2hex(random_bytes((24 - (24 % 2)) / 2));
            $chatwootModIdentifierHash = null;

            if (Capsule::schema()->hasTable('mod_chatwoot')) {
                $modChatwootSigningHash = Capsule::table('mod_chatwoot')->where('setting', 'signing_hash')->first('value')->value;

                if (!is_null($modChatwootSigningHash)) {
                    $chatwootModIdentifierHash = $modChatwootSigningHash;
                }
            }

            $identifierHash = $chatwootModIdentifierHash ?? $newIdentifierHash;

            lkn_hn_config_set(
                Platforms::CHATWOOT,
                Settings::CW_CLIENT_IDENTIFIER_KEY,
                $identifierHash
            );

            return ['status' => 'success'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            lkn_hn_log('mod: database table creation', [], $e->getMessage());

            return [
                'status' => 'error',
                'description' => "Unable to create database table: {$e->__toString()}",
            ];
        }
    }

    public static function deactivate(): array
    {
        try {
            return [
                'status' => 'success',
                'description' => 'Module deactivated. This module does not delete its database tables after deactivation.',
            ];
        } catch (Throwable $e) {
            lkn_hn_log('mod: deactivation error', [], $e->getMessage());

            return [
                'status' => 'error',
                'description' => "Unable to deactivate module: {$e->__toString()}",
            ];
        }
    }
}
