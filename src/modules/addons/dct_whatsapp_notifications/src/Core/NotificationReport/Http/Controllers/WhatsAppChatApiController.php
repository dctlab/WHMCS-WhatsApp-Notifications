<?php

namespace Dct\HookNotification\Core\NotificationReport\Http\Controllers;

use DateTime;
use Dct\HookNotification\Core\NotificationReport\Application\NotificationReportService;

/**
 * Routed through src/Core/api.php (see ApiHandler), NOT through the admin
 * page router — that way the response is clean JSON, not mixed in with
 * WHMCS's admin page chrome (header/sidebar), which would happen if this
 * went through the normal `_output()` admin page pathway instead.
 *
 * Since api.php has no built-in authentication (it also serves the public
 * Meta webhook), every method here must check for an active WHMCS admin
 * session before doing anything.
 *
 * @since 4.5.7
 */
final class WhatsAppChatApiController
{
    private NotificationReportService $notificationReportService;

    public function __construct()
    {
        $this->notificationReportService = new NotificationReportService();
    }

    /**
     * @return array{messages?: array, conversations?: array, error?: string}
     */
    public function poll(string $phone, ?string $since = null): array
    {
        $this->requireAdminSession();

        $newMessages = $since
            ? $this->notificationReportService->getNewChatMessages($phone, new DateTime($since))
            : $this->notificationReportService->getChatThread($phone);

        $conversations = $this->notificationReportService->getChatConversationsList();

        return [
            'messages' => array_map([$this, 'formatMessage'], $newMessages),
            'conversations' => array_map([$this, 'formatConversation'], $conversations),
        ];
    }

    /**
     * Reads `phone` from the query string and `message` from the raw JSON
     * POST body (ApiHandler only auto-injects query-string params).
     *
     * @return array{success: bool, error?: string}
     */
    public function send(): array
    {
        $this->requireAdminSession();

        $phone = trim((string) ($_GET['phone'] ?? ''));
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $text  = trim((string) ($body['message'] ?? ''));

        if ($phone === '' || $text === '') {
            return ['success' => false, 'error' => 'Phone and message are required.'];
        }

        $result = $this->notificationReportService->sendChatMessage($phone, $text);

        if ($result->code !== 'success') {
            return ['success' => false, 'error' => $result->errors['message'] ?? 'Failed to send message.'];
        }

        return ['success' => true];
    }

    /**
     * @return array{direction: string, body: ?string, type: ?string, status: ?string, sent_at: string}
     */
    private function formatMessage(array $message): array
    {
        return [
            'direction' => $message['direction'],
            'body' => $message['body'],
            'type' => $message['type'],
            'status' => $message['status'],
            'sent_at' => $message['sent_at']->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array{phone_number: string, client_id: ?int, client_name: ?string, last_message_preview: ?string, last_message_direction: ?string, last_message_at: ?string}
     */
    private function formatConversation(array $conversation): array
    {
        return [
            'phone_number' => $conversation['phone_number'],
            'client_id' => $conversation['client_id'],
            'client_name' => $conversation['client_name'],
            'last_message_preview' => $conversation['last_message_preview'],
            'last_message_direction' => $conversation['last_message_direction'],
            'last_message_at' => $conversation['last_message_at'] ? $conversation['last_message_at']->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * WHMCS stores the logged-in admin's id in $_SESSION['adminid'] once
     * authenticated in the admin area. Since this controller is reached
     * through api.php (which has no auth of its own, as it also serves the
     * public Meta webhook), every method must be gated behind this check.
     */
    private function requireAdminSession(): void
    {
        if (!empty($_SESSION['adminid'])) {
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
