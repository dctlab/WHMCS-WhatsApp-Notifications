<?php

/**
 * Code: NewClientRegistration
 *
 * Instant notification: fires directly off WHMCS's ClientAdd hook, right
 * after a new client account is created.
 *
 * @see https://developers.whmcs.com/hooks-reference/client/#clientadd
 */

namespace Dct\HookNotification\Notifications\Custom;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

final class NewClientRegistrationNotification extends AbstractNotification
{
    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    public function __construct()
    {
        $parameters = [
            new NotificationParameter(
                'client_first_name',
                lkn_hn_lang('Client first name'),
                fn (): string => getClientFirstNameByClientId($this->client->id)
            ),
            new NotificationParameter(
                'client_last_name',
                lkn_hn_lang('Client last name'),
                fn (): string => getClientLastNameByClientId($this->client->id)
            ),
            new NotificationParameter(
                'client_full_name',
                lkn_hn_lang('Client full name'),
                fn (): string => getClientFullNameByClientId($this->client->id)
            ),
            new NotificationParameter(
                'message_signature',
                lkn_hn_lang('Message signature'),
                fn (): string => "Thank you for choosing Indian Server Hosting!\nIndian Server Hosting Team"
            ),
            new NotificationParameter(
                'whmcs_domain',
                lkn_hn_lang('WHMCS domain'),
                fn (): string => self::WHMCS_DOMAIN
            ),
        ];

        parent::__construct(
            'NewClientRegistration',
            null,
            Hooks::CLIENT_ADD,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
        );
    }

    /**
     * WHMCS's ClientAdd hook gives the new client's id directly as `userid`
     * - no DB lookup needed, all the parameters above resolve from
     * $this->client once finishInit() runs.
     *
     * @param array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null null skips sending.
     */
    public function transformHookParams(array $whmcsHookParams): ?array
    {
        $clientId = $whmcsHookParams['userid'] ?? $whmcsHookParams['client_id'] ?? null;

        if (!$clientId) {
            return null;
        }

        return ['client_id' => $clientId];
    }
}
