<?php

namespace Lkn\HookNotification\Core\Platforms\Botms\Http\Controllers;

use DateTime;
use Lkn\HookNotification\Core\NotificationReport\Application\NotificationReportService;

/**
 * Receives events botms.in posts to the URL registered via
 * BotmsApiClient::setWebhook(): connection status, incoming/outgoing
 * messages, disconnects, battery changes, etc.
 *
 * Based on a real observed payload (see module log "botms webhook: received
 * event"), botms.in wraps a Baileys-style (WhatsApp multi-device library)
 * event under `payload`:
 *
 * {
 *   "payload": {
 *     "instance_id": "...",
 *     "event": "messages.upsert",
 *     "data": {
 *       "messages": [{
 *         "key": {"remoteJid": "...", "fromMe": bool, "id": "..."},
 *         "messageTimestamp": 1234567890,
 *         "pushName": "...",
 *         "message": {"conversation": "text"} // absent on system/stub events
 *         "messageStubType": 2,                // present on system events, no real content
 *         "messageStubParameters": ["..."]
 *       }],
 *       "type": "notify" | "append"
 *     }
 *   },
 *   "timestamp": "..."
 * }
 *
 * Entries with `fromMe: true` (messages the connected number itself sent) or
 * a `messageStubType` (protocol/system events - session housekeeping, not
 * real messages, no `message` payload) are skipped.
 *
 * `remoteJid` can end in `@s.whatsapp.net`/`@c.us` (a real phone number) or
 * `@lid` (WhatsApp's newer "linked ID" privacy identifier, which is NOT a
 * phone number) - the `@lid` case is recorded as-is since there's no phone
 * number to extract, so it won't match a WHMCS client by phone.
 *
 * Routed through src/Core/api.php (see ApiHandler), the same as the Meta
 * WhatsApp webhook.
 *
 * @since 4.5.14
 */
final class BotmsWebhookController
{
    private NotificationReportService $notificationReportService;

    public function __construct()
    {
        $this->notificationReportService = new NotificationReportService();
    }

    /**
     * @return array{__raw: string}
     */
    public function handle(): array
    {
        try {
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];

            lkn_hn_log('Botms webhook: received event', [], ['payload' => $payload]);

            $event    = $payload['payload']['event'] ?? $payload['event'] ?? null;
            $messages = $payload['payload']['data']['messages']
                ?? $payload['data']['messages']
                ?? [];

            if ($event === 'messages.upsert' && is_array($messages)) {
                foreach ($messages as $message) {
                    $this->processMessage($message);
                }
            }
        } catch (\Throwable $th) {
            lkn_hn_log('Botms webhook: processing error', [], ['exception' => $th->__toString()]);
        }

        // botms.in just needs a 200 response; body content isn't otherwise specified.
        return ['__raw' => json_encode(['received' => true])];
    }

    /**
     * @param array<mixed> $message
     */
    private function processMessage(array $message): void
    {
        $key = $message['key'] ?? [];

        // Sent by the connected number itself (an outbound message echoed
        // back), not an inbound reply.
        if (($key['fromMe'] ?? false) === true) {
            return;
        }

        // Protocol/system events (session housekeeping, receipts, etc.) carry
        // a stub type and no real `message` content - nothing to record.
        if (isset($message['messageStubType'])) {
            return;
        }

        $remoteJid = $key['remoteJid'] ?? null;

        if (!$remoteJid) {
            return;
        }

        // Strip the "@s.whatsapp.net" / "@c.us" / "@lid" suffix. For @lid
        // (WhatsApp's newer privacy identifier) what remains is NOT a phone
        // number, but there's nothing better to key on for that case.
        $from = strtok((string) $remoteJid, '@');

        $body = $this->extractMessageBody($message['message'] ?? null);

        if ($body === null) {
            // No text content this module knows how to preview (e.g. a
            // sticker/reaction/unsupported media type) - skip rather than
            // recording an empty message.
            return;
        }

        $waMessageId = $key['id'] ?? null;

        $timestamp = $message['messageTimestamp'] ?? null;

        $eventAt = $timestamp
            ? (new DateTime())->setTimestamp((int) $timestamp)
            : new DateTime();

        $this->notificationReportService->recordInboundMessage(
            $from,
            $eventAt,
            mb_substr(trim($body), 0, 200),
            $waMessageId !== null ? (string) $waMessageId : null,
        );
    }

    /**
     * Extracts a readable preview from a Baileys-style `message` object.
     * Returns null for message types this doesn't know how to preview
     * (falls back to a generic label for recognized-but-textless types).
     *
     * @param array<mixed>|null $message
     */
    private function extractMessageBody(?array $message): ?string
    {
        if ($message === null) {
            return null;
        }

        if (isset($message['conversation'])) {
            return (string) $message['conversation'];
        }

        if (isset($message['extendedTextMessage']['text'])) {
            return (string) $message['extendedTextMessage']['text'];
        }

        foreach (['imageMessage', 'videoMessage', 'documentMessage'] as $mediaType) {
            if (isset($message[$mediaType])) {
                $caption = $message[$mediaType]['caption'] ?? null;

                return $caption !== null && $caption !== ''
                    ? (string) $caption
                    : '[' . preg_replace('/Message$/', '', $mediaType) . ']';
            }
        }

        if (isset($message['audioMessage'])) {
            return '[Audio]';
        }

        if (isset($message['stickerMessage'])) {
            return '[Sticker]';
        }

        if (isset($message['buttonsResponseMessage']['selectedDisplayText'])) {
            return (string) $message['buttonsResponseMessage']['selectedDisplayText'];
        }

        if (isset($message['listResponseMessage']['title'])) {
            return (string) $message['listResponseMessage']['title'];
        }

        return null;
    }
}
