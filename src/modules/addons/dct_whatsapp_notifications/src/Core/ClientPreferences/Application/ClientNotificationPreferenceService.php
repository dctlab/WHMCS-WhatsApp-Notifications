<?php

namespace Dct\HookNotification\Core\ClientPreferences\Application;

use Dct\HookNotification\Core\Notification\Application\NotificationFactory;
use WHMCS\Database\Capsule;

/**
 * Lets a client opt out of WhatsApp notifications entirely, or of specific
 * notification types, from a client-area page (modules/addons/dct_whatsapp_notifications
 * -> clientarea, index.php?m=dct_whatsapp_notifications).
 *
 * Enforced centrally in NotificationSender::send() - see
 * ClientNotificationPreferenceService::isNotificationAllowed() - so every
 * platform (Meta/Botms.in/Baileys) respects it uniformly without each
 * needing its own check.
 *
 * @since 4.6.3
 */
final class ClientNotificationPreferenceService
{
    private const TABLE = 'mod_dct_hook_notification_client_prefs';

    /**
     * Notification codes clients are never shown a toggle for - either
     * because they're not actually about the client (staff/admin-facing
     * alerts, which go to a fixed staff number regardless of anything
     * here), or because opting out would be a security downgrade rather
     * than a notification preference (2FA login codes).
     */
    private const NON_TOGGLEABLE_CODES = [
        'AdminTicketOpened',
        'AdminTicketUserReplied',
        'TwoFactorAuthentication',
    ];

    public function ensureTableExists(): void
    {
        if (Capsule::schema()->hasTable(self::TABLE)) {
            return;
        }

        Capsule::schema()->create(self::TABLE, function ($table) {
            $table->unsignedInteger('client_id')->primary();
            $table->boolean('whatsapp_enabled')->default(true);
            // JSON array of notification codes this client has individually
            // disabled, even while whatsapp_enabled is still true overall.
            $table->text('disabled_notifications')->nullable();
            $table->dateTime('updated_at');
        });
    }

    /**
     * @return array{whatsapp_enabled: bool, disabled_notifications: string[]}
     */
    public function getPreferences(int $clientId): array
    {
        $this->ensureTableExists();

        $row = Capsule::table(self::TABLE)->where('client_id', $clientId)->first();

        if ($row === null) {
            return ['whatsapp_enabled' => true, 'disabled_notifications' => []];
        }

        return [
            'whatsapp_enabled' => (bool) $row->whatsapp_enabled,
            'disabled_notifications' => $row->disabled_notifications
                ? (json_decode($row->disabled_notifications, true) ?: [])
                : [],
        ];
    }

    /**
     * @param string[] $disabledNotificationCodes
     */
    public function savePreferences(int $clientId, bool $whatsappEnabled, array $disabledNotificationCodes): void
    {
        $this->ensureTableExists();

        Capsule::table(self::TABLE)->updateOrInsert(
            ['client_id' => $clientId],
            [
                'whatsapp_enabled' => $whatsappEnabled,
                'disabled_notifications' => json_encode(array_values($disabledNotificationCodes)),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * The central enforcement check - called from NotificationSender::send()
     * before any platform actually sends anything.
     */
    public function isNotificationAllowed(int $clientId, string $notificationCode): bool
    {
        if (in_array($notificationCode, self::NON_TOGGLEABLE_CODES, true)) {
            return true;
        }

        $prefs = $this->getPreferences($clientId);

        if (!$prefs['whatsapp_enabled']) {
            return false;
        }

        return !in_array($notificationCode, $prefs['disabled_notifications'], true);
    }

    /**
     * Every notification type a client can individually toggle, for
     * rendering the checkbox list - excludes the non-toggleable ones above.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function getToggleableNotificationTypes(): array
    {
        $notifications = NotificationFactory::getInstance()->makeAll(false);
        $seen          = [];
        $result        = [];

        foreach ($notifications as $notification) {
            $code = $notification->code;

            if (isset($seen[$code]) || in_array($code, self::NON_TOGGLEABLE_CODES, true)) {
                continue;
            }

            $seen[$code] = true;

            $result[] = [
                'code' => $code,
                'label' => trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $code)),
            ];
        }

        usort($result, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $result;
    }
}
