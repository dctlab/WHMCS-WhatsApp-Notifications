<?php

namespace Dct\HookNotification\Core\Notification\Infrastructure\Observers;

use Dct\HookNotification\Core\Shared\Infrastructure\Singleton;

final class NotificationObserverFactory extends Singleton {
    /**
     * @return array<\Dct\HookNotification\Core\Notification\Domain\NotificationObserverInterface>
     */
    public static function make(): array
    {
        return [new ChatwootNotificationObserver()];
    }
}
