<?php

namespace Lkn\HookNotification\Core\Platforms\Common;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;

final class PlatformNotificationSendResult
{
    /**
     * @param  NotificationReportStatus $status
     * @param  string|null              $msg
     * @param  string|null              $target    This can be a phone number, WhatsApp phone number, email.
     * @param  string|null              $messageId The message id returned by the platform API (e.g. WhatsApp
     *                                              `messages[0].id`), used later to match delivery status webhooks
     *                                              back to this send attempt.
     * @param  string|null              $messagePreview A human-readable preview of what was actually sent
     *                                              (e.g. the resolved WhatsApp template parameter values), shown
     *                                              on the Notification Reports page.
     */
    public function __construct(
        public NotificationReportStatus $status,
        public ?string $msg = null,
        public ?string $target = null,
        public ?string $messageId = null,
        public ?string $messagePreview = null,
    ) {
    }
}
