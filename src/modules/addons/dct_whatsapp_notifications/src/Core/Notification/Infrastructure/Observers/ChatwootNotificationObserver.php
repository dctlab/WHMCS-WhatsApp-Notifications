<?php

namespace Dct\HookNotification\Core\Notification\Infrastructure\Observers;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationObserverInterface;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Platforms\Chatwoot\Application\ChatwootNotificationListenerService;
use Dct\HookNotification\Core\Platforms\Common\AbstractPlatform;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformFactory;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;

final class ChatwootNotificationObserver implements NotificationObserverInterface
{
    public function onNotificationSent(
        AbstractNotification $notification,
        NotificationTemplate $template,
        AbstractPlatform $platform
    ): void {
        /** @var ChatwootPlatform $chatwootPlatform */
        $chatwootPlatform = (new PlatformFactory)->make(Platforms::CHATWOOT);

        if ($chatwootPlatform->platformSettings->listenSendAsPrivateNote) {
            (new ChatwootNotificationListenerService($chatwootPlatform))->run($notification);
        }
    }
}
