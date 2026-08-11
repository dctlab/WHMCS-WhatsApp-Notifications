<?php

namespace Dct\HookNotification\Core\Platforms\Common;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Shared\Infrastructure\BaseApiClient;

abstract class BasePlatformApiClient extends BaseApiClient
{
    abstract protected function sendNotification(
        AbstractNotification $notification,
        NotificationTemplate $template
    );
}
