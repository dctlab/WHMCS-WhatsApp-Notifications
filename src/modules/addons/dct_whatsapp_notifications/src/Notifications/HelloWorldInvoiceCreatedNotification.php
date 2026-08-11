<?php

namespace Dct\HookNotification\Notifications\Custom;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

/**
 * @see https://developers.whmcs.com/hooks-reference/invoices-and-quotes/#invoicecreated
 */
final class HelloWorldInvoiceCreatedNotification extends AbstractNotification
{
    public function __construct()
    {
        parent::__construct(
            'HelloWorldInvoiceCreated',
            NotificationReportCategory::INVOICE,
            Hooks::INVOICE_CREATED,
            new NotificationParameterCollection([
                new NotificationParameter(
                    'invoice_id',
                    lkn_hn_lang('Invoice ID'),
                    fn (): int => $this->whmcsHookParams['invoiceid']
                ),
            ]),
            fn() => getClientIdByInvoiceId($this->whmcsHookParams['invoiceid']),
            fn() => $this->whmcsHookParams['invoiceid'],
        );
    }
}
