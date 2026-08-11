<?php

namespace Dct\HookNotification\Core\Platforms\MetaWhatsApp\Domain;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotificationParser;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Platforms\Common\AbstractPlatform;
use Dct\HookNotification\Core\Platforms\Common\AbstractPlatformSettings;
use Dct\HookNotification\Core\Platforms\Common\PlatformNotificationSendResult;
use Dct\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\Result;

final class MetaWhatsAppPlatform extends AbstractPlatform
{
    /**
     * @var MetaWhatsAppSettings
     */
    public readonly AbstractPlatformSettings $platformSettings;

    /**
     * @var MetaWhatsAppNotificationParser
     */
    public readonly AbstractNotificationParser $notificationParser;

    /**
     * @var \Dct\HookNotification\Core\Platforms\MetaWhatsApp\Infrastructure\MetaWhatsAppApiClient
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

        $phoneNumber = $this->getPhoneNumber($notification);

        if (!$phoneNumber) {
            return new PlatformNotificationSendResult(
                NotificationReportStatus::NOT_SENT,
                'Client has no valid phone number.'
            );
        }

        if (empty($template->platformPayload['msgTemplateLang'])) {
            return new PlatformNotificationSendResult(
                NotificationReportStatus::NOT_SENT,
                'Please, click save inside the notification page to update it to the new format.'
            );
        }

        $filledTemplate = $this->notificationParser->parse($notification, $template);

        if ($filledTemplate instanceof Result) {
            lkn_hn_log(
                "{$template->platform->value}: template parsing error",
                [
                    'phoneNumber' => $phoneNumber,
                    'notification' => $notification,
                    'template' => $template,
                ],
                [
                    'result' => $filledTemplate,
                ]
            );

            return new PlatformNotificationSendResult(
                NotificationReportStatus::ERROR,
                $filledTemplate->msg ?? 'Failed to build the WhatsApp message from the template (see module log for details).'
            );
        }

        $apiResponse = $this->apiClient->sendMessageTemplate(
            $phoneNumber,
            $template->template,
            $filledTemplate,
            $template->platformPayload['msgTemplateLang']
        );

        if (
            !isset($apiResponse->body['messages'][0]['message_status']) ||
            $apiResponse->body['messages'][0]['message_status'] !== 'accepted'
        ) {
            lkn_hn_log(
                "{$template->platform->value}: api error",
                [
                    'phoneNumber' => $phoneNumber,
                    'notification' => $notification,
                    'template' => $template,
                ],
                [
                    'api_response' => $apiResponse,
                ]
            );

            return new PlatformNotificationSendResult(
                NotificationReportStatus::ERROR,
                isset($apiResponse->body['error']['message'])
                    ? $apiResponse->body['error']['message']
                    : 'API Error'
            );
        }

        return new PlatformNotificationSendResult(
            NotificationReportStatus::SENT,
            'The notification was sent.',
            $phoneNumber,
            $apiResponse->body['messages'][0]['id'] ?? null,
            $this->buildMessagePreview($filledTemplate),
        );
    }

    /**
     * Builds a human-readable preview of what was actually sent, from the
     * resolved WhatsApp template components (header/body/button parameter
     * values). This is a snapshot taken at send time - not a reconstruction
     * of Meta's exact approved template wording (that would need an extra
     * API call per send) - but it shows exactly which values went out,
     * which is what the Notification Reports page needs to answer "what did
     * this client actually receive".
     *
     * @param array<int, array{type?: string, parameters?: array<int, array<string, mixed>>}> $filledTemplate
     */
    private function buildMessagePreview(array $filledTemplate): ?string
    {
        $parts = [];

        foreach ($filledTemplate as $component) {
            foreach ($component['parameters'] ?? [] as $parameter) {
                $value = $parameter['text']
                    ?? $parameter['image']['link']
                    ?? $parameter['document']['link']
                    ?? $parameter['video']['link']
                    ?? null;

                if ($value !== null && $value !== '') {
                    $parts[] = $value;
                }
            }
        }

        return !empty($parts) ? implode(' | ', $parts) : null;
    }
}
