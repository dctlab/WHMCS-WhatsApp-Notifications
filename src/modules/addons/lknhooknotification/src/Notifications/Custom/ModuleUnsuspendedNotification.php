<?php

/**
 * Code: ModuleUnsuspended
 *
 * Instant notification: fires directly off WHMCS's AfterModuleUnsuspend hook,
 * right after a product/service is unsuspended.
 *
 * WHMCS only gives this hook `serviceid` (and `userid`), so
 * transformHookParams() below enriches it into the full payload this
 * notification's parameters need, via a DB lookup.
 *
 * @see https://developers.whmcs.com/hooks-reference/module/#aftermoduleunsuspend
 */

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class ModuleUnsuspendedNotification extends AbstractNotification implements ResendableNotificationInterface
{
    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    /**
     * Guards against sending twice for the same service, in case WHMCS ever
     * fires AfterModuleUnsuspend more than once for it.
     */
    private const SENT_TABLE = 'mod_ishost_module_unsuspended_sent';

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
            new NotificationParameter(
                'product_name',
                lkn_hn_lang('Product name'),
                fn (): string => (string) $this->whmcsHookParams['product_name']
            ),
            new NotificationParameter(
                'product_domain',
                lkn_hn_lang('Product domain'),
                fn (): string => (string) $this->whmcsHookParams['product_domain']
            ),
        ];

        parent::__construct(
            'ModuleUnsuspended',
            NotificationReportCategory::SERVICE,
            Hooks::AFTER_MODULE_UNSUSPEND,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }

    /**
     * @param array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null null skips sending.
     */
    public function transformHookParams(array $whmcsHookParams): ?array
    {
        $serviceId = $whmcsHookParams['serviceid'] ?? $whmcsHookParams['service_id'] ?? null;

        if (!$serviceId) {
            return null;
        }

        $this->ensureSentTableExistsForService();

        $alreadySent = Capsule::table(self::SENT_TABLE)
            ->where('service_id', $serviceId)
            ->exists();

        if ($alreadySent) {
            return null;
        }

        $payload = $this->buildPayloadForService((int) $serviceId);

        if ($payload === null) {
            return null;
        }

        Capsule::table(self::SENT_TABLE)->insert([
            'service_id' => $serviceId,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        return $payload;
    }

    private function buildPayloadForService(int $serviceId): ?array
    {
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();

        if ($service === null) {
            return null;
        }

        $productName = Capsule::table('tblproducts')
            ->where('id', $service->packageid)
            ->value('name');

        return [
            'client_id'           => $service->userid,
            'report_category_id' => $service->id,
            'product_name'        => $productName ?: 'N/A',
            'product_domain'      => $service->domain ?: 'N/A',
        ];
    }

    private function ensureSentTableExistsForService(): void
    {
        if (Capsule::schema()->hasTable(self::SENT_TABLE)) {
            return;
        }

        Capsule::schema()->create(self::SENT_TABLE, function ($table) {
            $table->unsignedInteger('service_id')->primary();
            $table->dateTime('sent_at');
        });
    }

    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        return $this->buildPayloadForService($categoryId);
    }
}
