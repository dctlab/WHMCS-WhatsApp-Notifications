<?php

namespace Lkn\HookNotification\Core\Platforms\Botms\Infrastructure;

use Lkn\HookNotification\Core\Shared\Infrastructure\ApiResponse;
use Lkn\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;

/**
 * Client for the botms.in WhatsApp REST API.
 *
 * @see https://botms.in (API docs as provided by the account owner)
 */
final class BotmsApiClient extends BaseApiClient
{
    private const BASE_URL = 'https://botms.in/api';

    public function __construct(
        private readonly ?string $instanceId,
        private readonly ?string $accessToken,
    ) {
    }

    public function areSettingsFilled(): bool
    {
        return !empty($this->instanceId) && !empty($this->accessToken);
    }

    /**
     * Sends a plain text message.
     */
    public function sendTextMessage(string $toPhoneNumber, string $message): ApiResponse
    {
        $toPhoneNumber = ltrim(str_replace('+', '', $toPhoneNumber));

        $requestBody = [
            'number' => $toPhoneNumber,
            'type' => 'text',
            'message' => $message,
            'instance_id' => $this->instanceId,
            'access_token' => $this->accessToken,
        ];

        $apiResponse = $this->httpRequest('POST', self::BASE_URL, 'send', body: $requestBody);

        lkn_hn_log(
            Platforms::BOTMS->value . ': send text message',
            ['toPhoneNumber' => $toPhoneNumber, 'message' => $message],
            $apiResponse,
            [$toPhoneNumber]
        );

        return $apiResponse;
    }

    /**
     * Sends a media/file message (image, video, or document) with an
     * optional caption.
     */
    public function sendMediaMessage(
        string $toPhoneNumber,
        string $mediaUrl,
        ?string $caption = null,
        ?string $filename = null,
    ): ApiResponse {
        $toPhoneNumber = ltrim(str_replace('+', '', $toPhoneNumber));

        $requestBody = array_filter([
            'number' => $toPhoneNumber,
            'type' => 'media',
            'message' => $caption ?? '',
            'media_url' => $mediaUrl,
            'filename' => $filename,
            'instance_id' => $this->instanceId,
            'access_token' => $this->accessToken,
        ], fn ($value) => $value !== null);

        $apiResponse = $this->httpRequest('POST', self::BASE_URL, 'send', body: $requestBody);

        lkn_hn_log(
            Platforms::BOTMS->value . ': send media message',
            ['toPhoneNumber' => $toPhoneNumber, 'mediaUrl' => $mediaUrl, 'filename' => $filename],
            $apiResponse,
            [$toPhoneNumber]
        );

        return $apiResponse;
    }

    /**
     * Registers (or re-registers) the URL botms.in should call with events:
     * connection status, incoming/outgoing messages, disconnects, battery
     * changes, etc.
     */
    public function setWebhook(string $webhookUrl, bool $enable = true): ApiResponse
    {
        $queryParams = [
            'webhook_url' => urlencode($webhookUrl),
            'enable' => $enable ? 'true' : 'false',
            'instance_id' => $this->instanceId,
            'access_token' => $this->accessToken,
        ];

        $apiResponse = $this->httpRequest('GET', self::BASE_URL, 'set_webhook', queryParams: $queryParams);

        lkn_hn_log(
            Platforms::BOTMS->value . ': set webhook',
            ['webhookUrl' => $webhookUrl, 'enable' => $enable],
            $apiResponse,
        );

        return $apiResponse;
    }
}
