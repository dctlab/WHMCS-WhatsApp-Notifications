<?php

namespace Dct\HookNotification\Core\Notification\Domain;

use Dct\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\Result;

abstract class AbstractNotificationParser
{
    public function __construct(
        public null|BaseApiClient $baseApiClient = null
    ) {
    }

    abstract public function parse(
        AbstractNotification $notification,
        NotificationTemplate $template,
        ?BaseApiClient $apiClient = null,
    ): array|Result;
}
