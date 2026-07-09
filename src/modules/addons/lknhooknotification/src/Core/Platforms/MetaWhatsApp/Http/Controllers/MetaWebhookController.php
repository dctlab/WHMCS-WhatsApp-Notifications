<?php

namespace Lkn\HookNotification\Core\Platforms\MetaWhatsApp\Http\Controllers;

use DateTime;
use Lkn\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Lkn\HookNotification\Core\Shared\Infrastructure\Setup\DatabaseUpgrade;
use Throwable;

/**
 * Receives events from the Meta WhatsApp Cloud API webhook:
 *
 * - GET requests are the subscription handshake (hub.mode / hub.verify_token / hub.challenge).
 * - POST requests carry `messages.statuses` updates (sent/delivered/read/failed) and
 *   conversation/pricing information, used to power delivery tracking and conversation
 *   analytics.
 *
 * @see https://developers.facebook.com/docs/graph-api/webhooks/getting-started
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/payload-examples#status--object
 *
 * @since 4.5.0
 */
final class MetaWebhookController
{
    private NotificationReportService $notificationReportService;

    public function __construct()
    {
        $this->notificationReportService = new NotificationReportService();
    }

    /**
     * @return array{__raw: string, __status?: int}
     */
    public function handle(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            return $this->handleVerification();
        }

        return $this->handleStatusUpdate();
    }

    /**
     * @return array{__raw: string, __status?: int}
     */
    private function handleVerification(): array
    {
        $mode         = $_GET['hub_mode'] ?? null;
        $verifyToken  = trim((string) ($_GET['hub_verify_token'] ?? ''));
        $challenge    = $_GET['hub_challenge'] ?? '';
        $expectedToken = trim((string) (lkn_hn_config(Settings::WP_WEBHOOK_VERIFY_TOKEN) ?? ''));

        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, $verifyToken)) {
            lkn_hn_log('WhatsApp webhook: verification success', [], []);

            return ['__raw' => (string) $challenge];
        }

        lkn_hn_log(
            'WhatsApp webhook: verification failed',
            [
                'mode' => $mode,
                'received_token' => $verifyToken,
                'has_expected_token_configured' => $expectedToken !== '',
            ],
            []
        );

        return ['__raw' => 'Forbidden', '__status' => 403];
    }

    /**
     * @return array{__raw: string}
     */
    private function handleStatusUpdate(): array
    {
        try {
            DatabaseUpgrade::ensureDeliveryTrackingSchema();

            $payload = json_decode(file_get_contents('php://input'), true) ?? [];

            lkn_hn_log('WhatsApp webhook: received event', [], $payload);

            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->processStatus($status);
                    }

                    foreach ($value['messages'] ?? [] as $message) {
                        $this->processInboundMessage($message);
                    }
                }
            }
        } catch (Throwable $th) {
            lkn_hn_log('WhatsApp webhook: processing error', [], ['exception' => $th->__toString()]);
        }

        // Meta only requires a 200 response within a few seconds; body content is ignored.
        return ['__raw' => 'EVENT_RECEIVED'];
    }

    /**
     * @param array{
     *     id?: string,
     *     status?: string,
     *     timestamp?: string,
     *     recipient_id?: string,
     *     conversation?: array{id?: string, origin?: array{type?: string}, expiration_timestamp?: string},
     *     pricing?: array{category?: string, pricing_model?: string, billable?: bool},
     * } $status
     */
    private function processStatus(array $status): void
    {
        $waMessageId = $status['id'] ?? null;
        $deliveryStatus = $status['status'] ?? null;

        if (!$waMessageId || !$deliveryStatus) {
            return;
        }

        $eventAt = isset($status['timestamp'])
            ? (new DateTime())->setTimestamp((int) $status['timestamp'])
            : new DateTime();

        $billable = array_key_exists('billable', $status['pricing'] ?? []) ? (bool) $status['pricing']['billable'] : null;
        $waCategory = $status['pricing']['category'] ?? null;

        $this->notificationReportService->updateDeliveryStatusFromWebhook(
            $waMessageId,
            $deliveryStatus,
            $eventAt,
            $billable,
            $waCategory,
        );

        $this->notificationReportService->updateChatMessageStatus($waMessageId, $deliveryStatus);

        $conversation = $status['conversation'] ?? null;

        if (!empty($conversation['id'])) {
            $expirationAt = !empty($conversation['expiration_timestamp'])
                ? (new DateTime())->setTimestamp((int) $conversation['expiration_timestamp'])
                : null;

            $this->notificationReportService->upsertConversationFromWebhook(
                $conversation['id'],
                $waCategory,
                $status['pricing']['pricing_model'] ?? null,
                $billable,
                $conversation['origin']['type'] ?? null,
                $eventAt,
                $expirationAt,
                $waMessageId,
            );
        }
    }

    /**
     * Handles an inbound customer message (a reply).
     *
     * Meta does NOT include a `conversation` object on inbound message events
     * (only on the outbound status events for messages the business sends) -
     * so a brand-new, reply-only conversation has no Meta conversation id to
     * key on yet. The service either bumps an already-open conversation for
     * this phone number, or opens a placeholder that gets adopted with the
     * real conversation id once Meta reports it via a business reply.
     *
     * @param array{
     *     from?: string,
     *     timestamp?: string,
     *     type?: string,
     *     text?: array{body?: string},
     *     button?: array{text?: string},
     *     interactive?: array{
     *         button_reply?: array{title?: string},
     *         list_reply?: array{title?: string},
     *     },
     * } $message
     */
    private function processInboundMessage(array $message): void
    {
        $from = $message['from'] ?? null;

        if (!$from) {
            return;
        }

        $eventAt = isset($message['timestamp'])
            ? (new DateTime())->setTimestamp((int) $message['timestamp'])
            : new DateTime();

        $type = $message['type'] ?? 'unknown';

        $preview = match ($type) {
            'text' => mb_substr(trim((string) ($message['text']['body'] ?? '')), 0, 200),
            'button' => $message['button']['text'] ?? '[Button reply]',
            'interactive' => $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? '[Interactive reply]',
            default => '[' . ucfirst((string) $type) . ' message]',
        };

        $this->notificationReportService->recordInboundMessage(
            $from,
            $eventAt,
            $preview !== '' ? $preview : null,
            $message['id'] ?? null,
        );
    }
}
