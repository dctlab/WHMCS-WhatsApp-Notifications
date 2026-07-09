<?php

namespace Lkn\HookNotification\Core\Platforms\Common;

use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotificationParser;
use Lkn\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Lkn\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;

abstract class AbstractPlatform
{
    public function __construct(
        public readonly AbstractPlatformSettings $platformSettings,
        public readonly AbstractNotificationParser $notificationParser,
        protected readonly BaseApiClient $apiClient
    ) {
    }

    abstract public function sendNotification(
        AbstractNotification $notification,
        NotificationTemplate $template,
    ): PlatformNotificationSendResult;

    /**
     * Notification codes meant for staff/admin, not the client. These are
     * always sent to Settings::ADMIN_ALERT_WHATSAPP_NUMBER instead of
     * resolving a phone number from the client involved (the client's own
     * details are still used to fill the message's parameters - only the
     * delivery target is different).
     */
    private const ADMIN_FACING_NOTIFICATION_CODES = [
        'AdminTicketOpened',
        'AdminTicketUserReplied',
    ];

    protected function getPhoneNumber(AbstractNotification $notification): false|int
    {
        if (in_array($notification->code, self::ADMIN_FACING_NOTIFICATION_CODES, true)) {
            $adminNumber = lkn_hn_config(Settings::ADMIN_ALERT_WHATSAPP_NUMBER);

            return $adminNumber ? intval(preg_replace('/\D+/', '', $adminNumber)) : false;
        }

        if ($notification->code === 'DefaultTICKETADMINREPLY') {
            $ticketWpCustomFieldId = lkn_hn_config(Settings::TICKET_WP_CUSTOM_FIELD_ID);

            /** @var null|int $customFieldForTicket */
            $customFieldForTicket = null;

            if ($ticketWpCustomFieldId) {
                $customFieldForTicket = $ticketWpCustomFieldId;
            } else {
                if (isset($this->platformSettings->wpCustomFieldIdForTicket)) {
                    $customFieldForTicket = $this->platformSettings->wpCustomFieldIdForTicket;
                }
            }

            if (!is_int($customFieldForTicket)) {
                return $this->getPhoneNumber_($notification);
            }

            $ticketId = $notification->categoryId;

            $phoneNumber = $notification->client->getCustomField(
                $ticketId,
                $customFieldForTicket,
            );

            if ($phoneNumber) {
                return intval($phoneNumber);
            }
        }

        return $this->getPhoneNumber_($notification);
    }

    private function getPhoneNumber_(AbstractNotification $notification): false|int
    {
        $platformSpecificWpCustomFieldId = null;

        if (isset($this->platformSettings->wpCustomFieldId)) {
            $platformSpecificWpCustomFieldId = $this->platformSettings->wpCustomFieldId;
        } else {
            $platformSpecificWpCustomFieldId = null;
        }

        $phoneNumber = $notification->client->getWpPhoneNumberOrWhmcsPhoneNumber($platformSpecificWpCustomFieldId);

        return $phoneNumber;
    }
}
