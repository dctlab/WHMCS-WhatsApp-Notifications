<?php

namespace Dct\HookNotification\Core\NotificationReport\Http\Controllers;

use DateTime;
use Dct\HookNotification\Core\NotificationReport\Application\ChatMediaService;
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
        $this->requireCsrfToken();

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
            'id' => $message['id'] ?? null,
            'direction' => $message['direction'],
            'body' => $message['body'],
            'type' => $message['type'],
            'status' => $message['status'],
            'sent_at' => $message['sent_at']->format('Y-m-d H:i:s'),
            'has_media' => $message['has_media'] ?? false,
            'media_mime' => $message['media_mime'] ?? null,
            'media_filename' => $message['media_filename'] ?? null,
            'media_size' => $message['media_size'] ?? null,
            'media_unavailable' => $message['media_unavailable'] ?? false,
        ];
    }

    /**
     * Streams a chat message's image/audio/video/document to the admin
     * (downloading it from Meta first if it isn't cached yet).
     *
     * GET api.php?endpoint=chat/media&id={messageId}[&download=1]
     *
     * @since 5.13.0
     */
    public function media(string $id, ?string $download = null): array
    {
        $this->requireAdminSession();

        // Release the session lock so other chat requests aren't blocked
        // while a large file downloads/streams.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        (new ChatMediaService())->stream((int) $id, $download === '1');

        return [];
    }

    /**
     * Sends a file attachment or recorded voice note.
     *
     * POST (multipart/form-data) api.php?endpoint=chat/send-media&phone={phone}
     * fields: file, caption (optional), as_document (optional "1")
     *
     * @since 5.13.0
     *
     * @return array{success: bool, error?: string}
     */
    public function sendMedia(): array
    {
        $this->requireAdminSession();
        $this->requireCsrfToken();

        $phone = trim((string) ($_GET['phone'] ?? ''));

        if ($phone === '') {
            return ['success' => false, 'error' => 'Phone is required.'];
        }

        if (empty($_FILES['file'])) {
            // post_max_size exceeded empties $_POST and $_FILES entirely.
            return ['success' => false, 'error' => 'No file received - it may exceed the server post_max_size / upload_max_filesize limit.'];
        }

        @set_time_limit(180);

        $result = $this->notificationReportService->sendChatMedia(
            $phone,
            $_FILES['file'],
            isset($_POST['caption']) ? (string) $_POST['caption'] : null,
            !empty($_POST['as_document']),
        );

        if ($result->code !== 'success') {
            return ['success' => false, 'error' => $result->errors['message'] ?? 'Failed to send file.'];
        }

        return ['success' => true];
    }

    /**
     * Per-session token issued by WhatsAppChatController::viewChat() and sent
     * back by the chat page in the X-DCT-Chat-Token header. Blocks cross-site
     * requests from making a logged-in admin send WhatsApp messages.
     *
     * @since 5.13.0
     */
    private function requireCsrfToken(): void
    {
        $expected = (string) ($_SESSION['dct_hn_chat_token'] ?? '');
        $received = (string) ($_SERVER['HTTP_X_DCT_CHAT_TOKEN'] ?? $_POST['dct_chat_token'] ?? '');

        if ($expected !== '' && $received !== '' && hash_equals($expected, $received)) {
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Security token expired - please reload the page and try again.']);
        exit;
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
