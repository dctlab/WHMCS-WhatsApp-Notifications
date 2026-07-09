<?php

namespace Lkn\HookNotification\Core\NotificationReport\Http\Controllers;

use Lkn\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Lkn\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Lkn\HookNotification\Core\Shared\Infrastructure\View\View;

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

        $this->view->view('pages/chat', [
            'conversations' => $conversations,
            'selected_phone' => $selectedPhone,
            'thread' => $thread,
        ]);
    }
}
