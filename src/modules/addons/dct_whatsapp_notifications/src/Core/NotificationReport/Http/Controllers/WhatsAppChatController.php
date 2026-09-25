<?php

namespace Dct\HookNotification\Core\NotificationReport\Http\Controllers;

use Dct\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Dct\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;

/**
 * Live chat-style view of WhatsApp conversations: a contact list plus the
 * full message thread for the selected contact, with polling for new
 * messages and a free-form reply box (only works within Meta's 24h
 * customer service window).
 *
 * Polling and sending are handled by WhatsAppChatApiController, routed
 * through src/Core/api.php rather than through this admin page router, so
 * those responses are clean JSON rather than mixed in with WHMCS's admin
 * page chrome.
 *
 * @since 4.5.7
 */
final class WhatsAppChatController extends BaseController
{
    private NotificationReportService $notificationReportService;

    public function __construct(View $view)
    {
        $this->notificationReportService = new NotificationReportService();

        parent::__construct($view);
    }

    public function viewChat(array $request): void
    {
        $conversations = $this->notificationReportService->getChatConversationsList();

        $selectedPhone = $request['phone'] ?? ($conversations[0]['phone_number'] ?? null);

        $thread = $selectedPhone ? $this->notificationReportService->getChatThread($selectedPhone) : [];

        // CSRF token for the chat send / send-media API calls (5.13.0).
        if (empty($_SESSION['dct_hn_chat_token'])) {
            $_SESSION['dct_hn_chat_token'] = bin2hex(random_bytes(32));
        }

        $this->view->view('pages/chat', [
            'conversations' => $conversations,
            'selected_phone' => $selectedPhone,
            'thread' => $thread,
            'thread_json' => json_encode(
                array_map(static fn (array $m) => [
                    'id' => $m['id'],
                    'direction' => $m['direction'],
                    'body' => $m['body'],
                    'type' => $m['type'],
                    'status' => $m['status'],
                    'sent_at' => $m['sent_at']->format('Y-m-d H:i:s'),
                    'has_media' => $m['has_media'],
                    'media_mime' => $m['media_mime'],
                    'media_filename' => $m['media_filename'],
                    'media_size' => $m['media_size'],
                    'media_unavailable' => $m['media_unavailable'],
                ], $thread),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ) ?: '[]',
            'chat_token' => $_SESSION['dct_hn_chat_token'],
            'lame_js_url' => lkn_hn_get_module_root_url() . '/assets/js/vendor/lame.min.js',
            'upload_max_bytes' => $this->uploadMaxBytes(),
            'chat_i18n_json' => json_encode([
                'image' => lkn_hn_lang('Image'),
                'voice' => lkn_hn_lang('Voice message'),
                'unavailable' => lkn_hn_lang('media no longer available'),
                'captionLabel' => lkn_hn_lang('Caption (optional)'),
                'messageLabel' => lkn_hn_lang('Message'),
                'micDenied' => lkn_hn_lang('Microphone access was denied or is not available in this browser.'),
                'tooBig' => lkn_hn_lang('This file is larger than the server upload limit'),
                'netError' => lkn_hn_lang('Network error sending message.'),
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}',
        ]);
    }

    private function uploadMaxBytes(): int
    {
        $toBytes = static function (string $value): int {
            $value = trim($value);
            $unit  = strtolower(substr($value, -1));
            $num   = (float) $value;

            return (int) match ($unit) {
                'g' => $num * 1073741824,
                'm' => $num * 1048576,
                'k' => $num * 1024,
                default => $num,
            };
        };

        $limits = array_filter([
            $toBytes((string) ini_get('upload_max_filesize')),
            $toBytes((string) ini_get('post_max_size')),
        ]);

        return $limits ? min($limits) : 0;
    }
}
