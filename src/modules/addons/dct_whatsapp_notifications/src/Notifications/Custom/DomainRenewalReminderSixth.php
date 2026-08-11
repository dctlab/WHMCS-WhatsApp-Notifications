<?php

/**
 * Code: DomainRenewalReminderSixth
 *
 * Sixth Renewal Notice (14 days after expiry)
 *
 * Cron-based notification, fires for active domains whose expiry date is
 * exactly 14 day(s) after today. Shares its parameter set
 * and payload logic with the other 6 renewal reminders via
 * DomainRenewalReminderTrait, but has its own code, template, enable/disable
 * toggle, and notification report.
 */

namespace Dct\HookNotification\Notifications\Custom;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractCronNotification;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

final class DomainRenewalReminderSixthNotification extends AbstractCronNotification
{
    use DomainRenewalReminderTrait;

    public function __construct()
    {
        $this->offsetDays     = 14;
        $this->isBeforeExpiry = false;

        parent::__construct(
            'DomainRenewalReminderSixth',
            NotificationReportCategory::DOMAIN,
            Hooks::DAILY_CRON_JOB,
            $this->buildDomainReminderParameters(),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }
}
