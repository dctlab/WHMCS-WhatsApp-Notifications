<?php

/**
 * Code: DomainRenewalReminderFourth
 *
 * Fourth Renewal Notice (1 day after expiry)
 *
 * Cron-based notification, fires for active domains whose expiry date is
 * exactly 1 day(s) after today. Shares its parameter set
 * and payload logic with the other 6 renewal reminders via
 * DomainRenewalReminderTrait, but has its own code, template, enable/disable
 * toggle, and notification report.
 */

namespace Dct\HookNotification\Notifications\Custom;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractCronNotification;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

final class DomainRenewalReminderFourthNotification extends AbstractCronNotification
{
    use DomainRenewalReminderTrait;

    public function __construct()
    {
        $this->offsetDays     = 1;
        $this->isBeforeExpiry = false;

        parent::__construct(
            'DomainRenewalReminderFourth',
            NotificationReportCategory::DOMAIN,
            Hooks::DAILY_CRON_JOB,
            $this->buildDomainReminderParameters(),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }
}
