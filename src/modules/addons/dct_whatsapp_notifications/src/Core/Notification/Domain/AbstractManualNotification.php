<?php

namespace Dct\HookNotification\Core\Notification\Domain;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

abstract class AbstractManualNotification extends AbstractNotification
{
    public readonly bool $isManual;

    /**
     * @param  string                          $code           Must be unique.
     * @param  NotificationReportCategory      $category
     * @param  null|Hooks|array                $hook
     * @param  NotificationParameterCollection $parameters
     * @param  callable                        $findClientId
     * @param  callable                        $findCategoryId
     */
    public function __construct(
        string $code,
        NotificationReportCategory $category,
        null|Hooks|array $hook,
        NotificationParameterCollection $parameters,
        $findClientId,
        $findCategoryId,
    ) {
        $this->isManual = true;

        parent::__construct(
            $code,
            $category,
            $hook,
            $parameters,
            $findClientId,
            $findCategoryId,
        );
    }
}
