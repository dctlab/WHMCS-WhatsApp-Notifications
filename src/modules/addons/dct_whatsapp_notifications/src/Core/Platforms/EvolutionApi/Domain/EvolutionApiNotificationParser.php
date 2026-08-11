<?php

namespace Dct\HookNotification\Core\Platforms\EvolutionApi\Domain;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotificationParser;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Shared\Infrastructure\BaseApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\Result;

/**
 * This should return the platform-api-specific paylod based om
 *  NotificationTemplate->platformPayload.
 */
final class EvolutionApiNotificationParser extends AbstractNotificationParser
{
    public function parse(
        AbstractNotification $notification,
        NotificationTemplate $template,
        ?BaseApiClient $apiClient = null,
    ): array|Result {
        return [];
    }
}
