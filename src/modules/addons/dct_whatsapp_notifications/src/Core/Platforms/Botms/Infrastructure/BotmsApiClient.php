<?php

namespace Dct\HookNotification\Core\Platforms\Botms\Infrastructure;

use Dct\HookNotification\Core\Shared\Infrastructure\ApiResponse;
use Dct\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;

/**
 * Client for the botms.in WhatsApp REST API.
 *
 * @see https://botms.in (API docs as provided by the account owner)
 */
final class BotmsApiClient extends BaseApiClient
{
    private const BASE_URL = 'https://botms.in/api';

    /**
     * botms.in intermittently reports errors like "Connection Closed" when
     * the WhatsApp session behind an instance is momentarily unstable, which
     * often clears up within a couple of seconds - retry a few times before
     * giving up, rather than failing on the first hiccup.
     */
    private const MAX_SEND_ATTEMPTS = 3;
    private const RETRY_DELAY_SECONDS = 2;

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
     * Sends a plain text message. Automatically retries up to
     * self::MAX_SEND_ATTEMPTS times if botms.in reports an error (e.g. a
     * transient "Connection Closed"), returning as soon as one attempt
     * succeeds, or the last attempt's result if none do.
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

        return $this->sendWithRetry('send text message', $requestBody, ['toPhoneNumber' => $toPhoneNumber, 'message' => $message], [$toPhoneNumber]);
    }

    /**
     * Sends a media/file message (image, video, or document) with an
     * optional caption. Same retry behavior as sendTextMessage().
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

        return $this->sendWithRetry(
            'send media message',
            $requestBody,
            ['toPhoneNumber' => $toPhoneNumber, 'mediaUrl' => $mediaUrl, 'filename' => $filename],
            [$toPhoneNumber]
        );
    }

    /**
     * Posts to the /send endpoint, retrying up to self::MAX_SEND_ATTEMPTS
     * times while botms.in reports {"status": "error", ...} - most commonly
     * seen as a transient "Connection Closed" - returning immediately once
     * a request comes back with {"status": "success"}.
     *
     * @param array<string, mixed> $requestBody Actual API request body (includes credentials - never logged directly).
     * @param array<string, mixed> $logPayload  Curated, credential-free version of the request, safe to log.
     * @param array<int, string>   $logMasks
     */
    private function sendWithRetry(string $logAction, array $requestBody, array $logPayload, array $logMasks): ApiResponse
    {
        $apiResponse = null;

        for ($attempt = 1; $attempt <= self::MAX_SEND_ATTEMPTS; $attempt++) {
            $apiResponse = $this->httpRequest('POST', self::BASE_URL, 'send', body: $requestBody);

            $succeeded = ($apiResponse->body['status'] ?? null) === 'success';

            lkn_hn_log(
                Platforms::BOTMS->value . ': ' . $logAction . ($attempt > 1 ? " (attempt {$attempt})" : ''),
                $logPayload,
                $apiResponse,
                $logMasks
            );

            if ($succeeded) {
                return $apiResponse;
            }

            if ($attempt < self::MAX_SEND_ATTEMPTS) {
                sleep(self::RETRY_DELAY_SECONDS);
            }
        }

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
