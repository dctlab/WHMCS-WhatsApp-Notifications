<?php

namespace Dct\HookNotification\Core\Platforms\MetaWhatsApp\Infrastructure;

use Dct\HookNotification\Core\Shared\Infrastructure\ApiResponse;
use Dct\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;

/**
 * Holds methods that used to comunicate with the WhatsApp business API and
 * cloud API.
 */
final class MetaWhatsAppApiClient extends BaseApiClient
{
    public function __construct(
        private readonly ?string $apiVersion,
        private readonly ?string $phoneNumberId,
        private readonly ?string $userAccessToken,
        private readonly ?string $businessAccountId,
    ) {
    }

    public function areSettingsFilled()
    {
        return $this->apiVersion && $this->phoneNumberId && $this->userAccessToken && $this->businessAccountId;
    }

    /**
     * Performs a request to WhatsApp API.
     *
     * @since 3.0.0
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $body
     * @param array  $headers
     * @param array  $queryParams
     *
     * @return ApiResponse raw WhatsApp response converted to array or an empty array on failure.
     */
    final public function request(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
        array $queryParams = []
    ): ApiResponse {
        $baseUrl = 'https://graph.facebook.com/' . $this->apiVersion;
        $headers = array_merge(
            $headers,
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->userAccessToken,
            ]
        );

        return $this->httpRequest(
            $method,
            $baseUrl,
            $endpoint,
            $headers,
            $body,
            $queryParams,
        );
    }

    /**
     * @since 3.0.0
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $body
     * @param array  $headers
     * @param array  $queryParams
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/get-started
     *
     * @return ApiResponse
     */
    final public function apiCloud(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
        array $queryParams = [],
    ): ApiResponse {
        $endpoint = "{$this->phoneNumberId}/$endpoint";

        return $this->request(
            $method,
            $endpoint,
            $body,
            $headers,
            $queryParams,
        );
    }

    /**
     * @since 3.0.0
     *
     * @param string $method
     * @param string $endpoint
     * @param array  $body
     * @param array  $headers
     * @param array  $queryParams
     *
     * @link https://developers.facebook.com/docs/whatsapp/business-management-api
     *
     * @return ApiResponse
     */
    final public function apiBusiness(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
        array $queryParams = [],
    ): ApiResponse {
        $endpoint = $this->businessAccountId . '/' . $endpoint;

        return $this->request(
            $method,
            $endpoint,
            $body,
            $headers,
            $queryParams,
        );
    }

    /**
     * @see https://developers.facebook.com/docs/marketing-api/reference/business
     *
     * @return ApiResponse
     */
    public function getPhoneNumberStatus(): ApiResponse
    {
        $response = $this->apiBusiness('GET', '');

        lkn_hn_log(
            Platforms::WHATSAPP->value . ': getPhoneNumberStatus',
            [],
            $response,
        );

        return $response;
    }

    /**
     * @since 3.0.0
     *
     * @param array $params
     *
     * @link https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates/#retrieve-templates
     *
     * @return ApiResponse
     */
    public function getMessageTemplates(array $params = []): ApiResponse
    {
        $response = $this->apiBusiness(
            'GET',
            'message_templates',
            [],
            [],
            $params
        );

        lkn_hn_log(
            Platforms::WHATSAPP->value . ': getMessageTemplates',
            ['params' => $params],
            $response,
        );

        return $response;
    }

    /**
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates/#text-based
     *
     * @param  string $toPhoneNumber
     * @param  string $msgTemplateName
     * @param  array  $msgTemplateComponents
     * @param  string $msgTemplateLangCode
     *
     * @return ApiResponse
     */
    public function sendMessageTemplate(
        string $toPhoneNumber,
        string $msgTemplateName,
        array $msgTemplateComponents,
        string $msgTemplateLangCode
    ): ApiResponse {
        $requestBody = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $toPhoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $msgTemplateName,
                'language' => ['code' => $msgTemplateLangCode],
                'components' => $msgTemplateComponents,
            ],
        ];

        $apiResponse = $this->apiCloud('POST', 'messages', $requestBody);

        lkn_hn_log(
            Platforms::WHATSAPP->value . ': sendMessageTemplate',
            [
                'toPhoneNumber' => $toPhoneNumber,
                'msgTemplateName' => $msgTemplateName,
                'msgTemplateComponents' => $msgTemplateComponents,
                'msgTemplateLangCode' => $msgTemplateLangCode,
                'requestBody' => $requestBody,
            ],
            $apiResponse,
        );

        return $apiResponse;
    }

    /**
     * Sends a free-form text message (not a pre-approved template).
     *
     * Only works if the recipient has messaged the business within the last
     * 24 hours (Meta's "customer service window"); otherwise Meta rejects it
     * and an approved template must be used instead.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages
     *
     * @since 4.5.7
     */
    public function sendTextMessage(string $toPhoneNumber, string $text): ApiResponse
    {
        $requestBody = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $toPhoneNumber,
            'type' => 'text',
            'text' => ['body' => $text],
        ];

        $apiResponse = $this->apiCloud('POST', 'messages', $requestBody);

        lkn_hn_log(
            Platforms::WHATSAPP->value . ': sendTextMessage',
            [
                'toPhoneNumber' => $toPhoneNumber,
                'requestBody' => $requestBody,
            ],
            $apiResponse,
        );

        return $apiResponse;
    }

    /**
     * Resolves a WhatsApp media id to its short-lived download URL + metadata.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#retrieve-media-url
     *
     * @since 5.13.0
     *
     * @return array{url?: string, mime_type?: string, file_size?: int, sha256?: string, id?: string, error?: array}
     */
    public function getMediaInfo(string $mediaId): array
    {
        $response = $this->request('GET', rawurlencode($mediaId));

        return is_array($response->body) ? $response->body : [];
    }

    /**
     * Downloads the binary content of a media URL returned by getMediaInfo().
     * The URL itself requires the same Bearer token and is valid ~5 minutes.
     *
     * @since 5.13.0
     *
     * @return string|null Raw bytes, or null on failure.
     */
    public function downloadMediaBinary(string $url, int $maxBytes = 104857600): ?string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        // Only ever send our access token to Meta's own CDN hosts.
        if (!preg_match('/(^|\.)(fbsbx\.com|facebook\.com|fbcdn\.net|whatsapp\.net)$/i', $host)) {
            lkn_hn_log(Platforms::WHATSAPP->value . ': downloadMediaBinary refused host', ['host' => $host], []);

            return null;
        }

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            // Meta's media CDN rejects requests without a User-Agent.
            CURLOPT_USERAGENT => 'DCTLAB-WhatsApp-Notifications/1.0',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->userAccessToken],
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => static function ($ch, $dlTotal, $dlNow) use ($maxBytes) {
                return ($dlTotal > $maxBytes || $dlNow > $maxBytes) ? 1 : 0;
            },
        ]);

        $bytes    = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error    = curl_error($curl);

        curl_close($curl);

        if ($bytes === false || $httpCode !== 200 || $bytes === '') {
            lkn_hn_log(
                Platforms::WHATSAPP->value . ': downloadMediaBinary failed',
                ['http_code' => $httpCode, 'curl_error' => $error],
                []
            );

            return null;
        }

        return $bytes;
    }

    /**
     * Uploads a local file to Meta and returns the new media id.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#upload-media
     *
     * @since 5.13.0
     *
     * @return array{id?: string, error?: string}
     */
    public function uploadMedia(string $filePath, string $mimeType, string $fileName): array
    {
        $url = 'https://graph.facebook.com/' . $this->apiVersion . '/' . $this->phoneNumberId . '/media';

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->userAccessToken],
            CURLOPT_POSTFIELDS => [
                'messaging_product' => 'whatsapp',
                'type' => $mimeType,
                'file' => new \CURLFile($filePath, $mimeType, $fileName),
            ],
        ]);

        $raw      = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($curl);

        curl_close($curl);

        $body = is_string($raw) ? json_decode($raw, true) : null;

        lkn_hn_log(
            Platforms::WHATSAPP->value . ': uploadMedia',
            ['mime' => $mimeType, 'file_name' => $fileName, 'http_code' => $httpCode, 'curl_error' => $curlErr],
            $body ?? (string) $raw,
        );

        if (!empty($body['id'])) {
            return ['id' => (string) $body['id']];
        }

        return ['error' => $body['error']['error_user_msg'] ?? $body['error']['message'] ?? ($curlErr ?: 'Upload to WhatsApp failed (HTTP ' . $httpCode . ').')];
    }

    /**
     * Sends a media message (image, video, audio, document or sticker) that
     * references a previously uploaded media id. Subject to Meta's 24-hour
     * customer service window, same as free-form text.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/messages/image-messages
     *
     * @since 5.13.0
     */
    public function sendMediaMessage(
        string $toPhoneNumber,
        string $type,
        string $mediaId,
        ?string $caption = null,
        ?string $fileName = null,
    ): ApiResponse {
        $media = ['id' => $mediaId];

        // Audio and stickers don't support captions on WhatsApp.
        if ($caption !== null && $caption !== '' && in_array($type, ['image', 'video', 'document'], true)) {
            $media['caption'] = $caption;
        }

        if ($type === 'document' && $fileName) {
            $media['filename'] = $fileName;
        }

        $requestBody = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $toPhoneNumber,
            'type' => $type,
            $type => $media,
        ];

        $apiResponse = $this->apiCloud('POST', 'messages', $requestBody);

        lkn_hn_log(
            Platforms::WHATSAPP->value . ': sendMediaMessage',
            ['toPhoneNumber' => $toPhoneNumber, 'requestBody' => $requestBody],
            $apiResponse,
        );

        return $apiResponse;
    }

    /**
     * Marks an inbound message as read, so the customer sees blue ticks.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/guides/mark-message-as-read
     *
     * @since 5.14.0
     */
    public function markMessageAsRead(string $waMessageId): ApiResponse
    {
        $apiResponse = $this->apiCloud('POST', 'messages', [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);

        lkn_hn_log(Platforms::WHATSAPP->value . ': markMessageAsRead', ['message_id' => $waMessageId], $apiResponse);

        return $apiResponse;
    }
}
