<?php

namespace Lkn\HookNotification\Core\Platforms\Botms\Domain;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotificationParser;
use Lkn\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Lkn\HookNotification\Core\Platforms\Botms\Infrastructure\BotmsApiClient;
use Lkn\HookNotification\Core\Platforms\Common\AbstractPlatform;
use Lkn\HookNotification\Core\Platforms\Common\AbstractPlatformSettings;
use Lkn\HookNotification\Core\Platforms\Common\PlatformNotificationSendResult;
use Lkn\HookNotification\Core\Shared\Infrastructure\BaseApiClient;

final class BotmsPlatform extends AbstractPlatform
{
    /**
     * @var BotmsSettings
     */
    public readonly AbstractPlatformSettings $platformSettings;
    public readonly AbstractNotificationParser $notificationParser;

    /**
     * @var BotmsApiClient
     */
    protected readonly BaseApiClient $apiClient;

    public function sendNotification(
        AbstractNotification $notification,
        NotificationTemplate $template,
    ): PlatformNotificationSendResult {
        if (!$this->platformSettings->enabled) {
            return new PlatformNotificationSendResult(
                NotificationReportStatus::NOT_SENT,
                'The platform is disabled.'
            );
        }

        if (!$this->apiClient->areSettingsFilled()) {
            return new PlatformNotificationSendResult(
                NotificationReportStatus::NOT_SENT,
                'Botms.in instance ID / access token are not configured.'
            );
        }

        $phoneNumber = $this->getPhoneNumber($notification);

        if (!$phoneNumber) {
            return new PlatformNotificationSendResult(
                NotificationReportStatus::NOT_SENT,
                'Client has no valid phone number.'
            );
        }

        $filledTemplate = $notification->fillTemplate($template);

        $apiResponse = $this->apiClient->sendTextMessage(
            $phoneNumber,
            $filledTemplate,
        );

        // botms.in returns { "status": "success", ... } on success, and either
        // a non-200 HTTP status or a { "status": "error", "message": "..." }
        // body on failure - handle both shapes defensively.
        $isSuccess = $apiResponse->httpStatusCode >= 200
            && $apiResponse->httpStatusCode < 300
            && ($apiResponse->body['status'] ?? null) !== 'error';

        if (!$isSuccess) {
            $errorMessage = $apiResponse->body['message']
                ?? $apiResponse->body['error']
                ?? 'API Error';

            return new PlatformNotificationSendResult(
                NotificationReportStatus::ERROR,
                $this->addConnectionErrorHint($errorMessage)
            );
        }

        return new PlatformNotificationSendResult(
            NotificationReportStatus::SENT,
            'The notification was sent.',
            (string) $phoneNumber,
            $apiResponse->body['message_id'] ?? $apiResponse->body['id'] ?? null,
            $filledTemplate,
        );
    }

    /**
     * Botms.in returns generic messages like "Connection Closed" or "Send
     * failed" whenever the WhatsApp session behind the instance isn't
     * currently connected - append a plain-language hint so this is
     * self-explanatory from the Notification Reports page alone.
     */
    private function addConnectionErrorHint(string $errorMessage): string
    {
        $connectionRelated = ['connection closed', 'send failed', 'not connected', 'session', 'disconnect'];

        foreach ($connectionRelated as $needle) {
            if (stripos($errorMessage, $needle) !== false) {
                return $errorMessage
                    . ' (This usually means the WhatsApp session for this Botms.in instance is not currently connected. '
                    . 'Reconnect/re-scan the QR code for this instance in your Botms.in dashboard, then try again.)';
            }
        }

        return $errorMessage;
    }
}
