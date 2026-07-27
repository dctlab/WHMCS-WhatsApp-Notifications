<?php

/**
 * Code: TwoFactorAuthentication
 *
 * Manual notification (not hook-triggered): sends the WhatsApp 2FA login
 * code to a CLIENT, using whatever template you configure here like any
 * other notification. Triggered directly by the separate "WhatsApp
 * Verification" 2FA module (modules/security/lknwa2fa) at the moment a
 * client needs a code, via NotificationSender::send() directly - not by
 * WHMCS firing a hook.
 *
 * Uses Hooks::USER_LOGIN purely as a descriptive label (shown on the
 * Notifications page) - it is never actually registered to fire, since
 * AbstractManualNotification is excluded from hook auto-registration
 * regardless of which hook value it carries (see NotificationHookListener).
 * A real Hooks value is still required here (not null): the Notifications
 * list template reads $notification->hook->value unconditionally.
 *
 * This only covers client logins. Admin logins keep using a simple,
 * non-customizable message sent directly - WHMCS admin users aren't WHMCS
 * clients, and this whole notification system (client name/email/etc
 * merge fields) is built around a real client record existing, which an
 * admin login doesn't have.
 */

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\Notification\Domain\AbstractManualNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;

final class TwoFactorAuthenticationNotification extends AbstractManualNotification
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
                fn (): string => "Indian Server Hosting Team"
            ),
            new NotificationParameter(
                'whmcs_domain',
                lkn_hn_lang('WHMCS domain'),
                fn (): string => self::WHMCS_DOMAIN
            ),
            new NotificationParameter(
                'verification_code',
                lkn_hn_lang('Verification code'),
                fn (): string => (string) $this->whmcsHookParams['verification_code']
            ),
            new NotificationParameter(
                'code_valid_minutes',
                lkn_hn_lang('Code valid (minutes)'),
                fn (): string => (string) $this->whmcsHookParams['code_valid_minutes']
            ),
        ];

        parent::__construct(
            'TwoFactorAuthentication',
            NotificationReportCategory::SERVICE,
            Hooks::USER_LOGIN,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['client_id'],
        );
    }
}
