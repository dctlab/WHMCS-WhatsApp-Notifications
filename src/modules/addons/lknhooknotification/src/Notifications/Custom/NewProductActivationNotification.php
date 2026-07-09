<?php

/**
 * Code: NewProductActivation
 *
 * Instant notification: fires directly off WHMCS's AfterModuleCreate hook,
 * right after a product/service is successfully provisioned - whether that
 * happened automatically (e.g. right after payment) or an admin clicked
 * "Create" on the service manually.
 *
 * WHMCS only gives this hook `serviceid` (and `userid`), so
 * transformHookParams() below enriches it into the full payload this
 * notification's parameters need, via a DB lookup.
 *
 * @see https://developers.whmcs.com/hooks-reference/module/#aftermodulecreate
 */

namespace Lkn\HookNotification\Notifications\Custom;

use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\Notification\Domain\AbstractNotification;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameter;
use Lkn\HookNotification\Core\Notification\Domain\NotificationParameterCollection;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use WHMCS\Database\Capsule;

final class NewProductActivationNotification extends AbstractNotification implements ResendableNotificationInterface
{
    /**
     * Domain shown in the "WHMCS domain" parameter.
     * Update this if the installation domain ever changes.
     */
    private const WHMCS_DOMAIN = 'https://indianserverhosting.com';

    /**
     * Guards against sending twice for the same service, in case WHMCS ever
     * fires AfterModuleCreate more than once for it (e.g. a retried module
     * call after a transient failure).
     */
    private const SENT_TABLE = 'mod_ishost_new_product_activation_sent';

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
                // Static, editable closing signature used at the end of the message.
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
            new NotificationParameter(
                'service_id',
                lkn_hn_lang('Service ID'),
                fn (): string => (string) $this->whmcsHookParams['service_id']
            ),
        ];

        parent::__construct(
            'NewProductActivation',
            NotificationReportCategory::SERVICE,
            Hooks::AFTER_MODULE_CREATE,
            new NotificationParameterCollection($parameters),
            fn () => $this->whmcsHookParams['client_id'],
            fn () => $this->whmcsHookParams['report_category_id'],
        );
    }

    /**
     * Enriches WHMCS's AfterModuleCreate hook payload (just `serviceid` /
     * `userid`) into the full set of fields this notification's parameters
     * need, and guards against sending more than once for the same service.
     *
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

        $this->ensureSentTableExists();

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

    /**
     * Builds the full parameter payload for a single service, fresh from
     * the database. Used both by transformHookParams() (instant send) and
     * buildResendPayload() (manual resend from the Reports page).
     */
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
            'service_id'          => $service->id,
            // Falls back to placeholders rather than an empty string: WhatsApp
            // template messages reject empty parameters outright, and not every
            // product has a domain (e.g. licenses, non-domain-based services).
            'product_name'        => $productName ?: 'N/A',
            'product_domain'      => $service->domain ?: 'N/A',
        ];
    }

    private function ensureSentTableExists(): void
    {
        if (Capsule::schema()->hasTable(self::SENT_TABLE)) {
            return;
        }

        Capsule::schema()->create(self::SENT_TABLE, function ($table) {
            $table->unsignedInteger('service_id')->primary();
            $table->dateTime('sent_at');
        });
    }

    /**
     * Rebuilds the payload for a single service, fresh from the database,
     * so this notification can be resent from the Notification Reports page.
     */
    public function buildResendPayload(int $categoryId, ?int $clientId): ?array
    {
        return $this->buildPayloadForService($categoryId);
    }
}
