<?php

namespace Dct\HookNotification\Core\Notification\Domain;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;

/**
 * Used for notifications that runs on cron hooks.
 */
abstract class AbstractCronNotification extends AbstractNotification
{
    /**
     * @return array<mixed>
     */
    abstract public function getPayload(): array;
}
