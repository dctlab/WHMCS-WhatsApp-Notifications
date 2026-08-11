<?php

/**
 * Code: UserLoginNotification
 */

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class UserLoginNotification extends AbstractNotification
{
    /**
     * Memoizes resolveClientId() so a login with multiple parameters
     * referencing the client id doesn't re-query tblusers_clients for each one.
     */
    private ?int $resolvedClientId = null;

    public function __construct()
    {
        parent::__construct(
            'UserLoginNotification',
            null,
            Hooks::USER_LOGIN,
            new NotificationParameterCollection([

                new NotificationParameter(
                    'client_id',
                    lkn_hn_lang('Client ID'),
                    fn (): int => $this->resolveClientId()
                ),

                new NotificationParameter(
                    'client_first_name',
                    lkn_hn_lang('Client First Name'),
                    fn (): string => $this->whmcsHookParams['user']->first_name ?? ''
                ),

                new NotificationParameter(
                    'client_last_name',
                    lkn_hn_lang('Client Last Name'),
                    fn (): string => $this->whmcsHookParams['user']->last_name ?? ''
                ),

                new NotificationParameter(
                    'client_full_name',
                    lkn_hn_lang('Client Full Name'),
                    fn (): string => trim(
                        ($this->whmcsHookParams['user']->first_name ?? '') . ' ' .
                        ($this->whmcsHookParams['user']->last_name ?? '')
                    )
                ),

                new NotificationParameter(
                    'client_email',
                    lkn_hn_lang('Client Email'),
                    fn (): string => $this->whmcsHookParams['user']->email ?? ''
                ),

                new NotificationParameter(
                    'login_date',
                    lkn_hn_lang('Login Date'),
                    fn (): string => date('d M Y')
                ),

                new NotificationParameter(
                    'login_time',
                    lkn_hn_lang('Login Time'),
                    fn (): string => date('h:i A')
                ),

                new NotificationParameter(
                    'login_ip',
                    lkn_hn_lang('Login IP'),
                    fn (): string => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ),

                new NotificationParameter(
                    'whmcs_domain',
                    lkn_hn_lang('WHMCS Domain'),
                    fn (): string => parse_url(\WHMCS\Config\Setting::getValue('SystemURL'), PHP_URL_HOST) ?? ''
                ),

            ]),
            fn (): int => $this->resolveClientId()
        );
    }

    /**
     * Public (not private): AbstractNotification rebinds the findClientId
     * closure that calls this to its own scope (bindTo($this, self::class)
     * inside AbstractNotification), so the closure can only reach public
     * methods on this instance from that point on - a private method here
     * would throw "Call to private method ... from scope AbstractNotification".
     *
     * WHMCS's UserLogin hook gives $vars['user']->id, which is tblusers.id -
     * the login/authentication identity - NOT tblclients.id. On installs
     * that don't use "one user manages multiple client accounts", WHMCS's
     * migration from the old single-account model often keeps these two
     * ids numerically identical, so using one in place of the other can
     * appear to work by coincidence. But they're not guaranteed to match,
     * and a user who's a sub-account/manages more than one client account
     * would resolve to the wrong client (or no client) silently - confirmed
     * on this install specifically (userid 666355/666043 etc were never
     * real tblclients.id values at all - "Client Not Found").
     *
     * This looks up the real mapping via tblusers_clients: the client
     * account this user OWNS, if any (the common single-account case);
     * otherwise falls back to any client account they're associated with
     * (a sub-account user managing someone else's account); and only as a
     * last resort - if tblusers_clients has no rows for this user at all,
     * e.g. on an older/simpler install where it's never been populated -
     * falls back to treating the user id as the client id directly, which
     * is this notification's original (buggy, for this install) behavior.
     */
    public function resolveClientId(): int
    {
        if ($this->resolvedClientId !== null) {
            return $this->resolvedClientId;
        }

        $userId = (int) $this->whmcsHookParams['user']->id;

        try {
            $ownedClientId = Capsule::table('tblusers_clients')
                ->where('auth_user_id', $userId)
                ->where('owner', 1)
                ->value('client_id');

            if ($ownedClientId) {
                return $this->resolvedClientId = (int) $ownedClientId;
            }

            $anyClientId = Capsule::table('tblusers_clients')
                ->where('auth_user_id', $userId)
                ->value('client_id');

            if ($anyClientId) {
                return $this->resolvedClientId = (int) $anyClientId;
            }
        } catch (\Throwable $th) {
            lkn_hn_log(
                'UserLoginNotification: tblusers_clients lookup failed, falling back to user id as client id',
                ['userId' => $userId],
                ['exception' => $th->__toString()]
            );
        }

        return $this->resolvedClientId = $userId;
    }
}
