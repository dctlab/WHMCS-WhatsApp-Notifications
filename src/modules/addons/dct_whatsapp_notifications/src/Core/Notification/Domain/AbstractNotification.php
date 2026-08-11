<?php

namespace Dct\HookNotification\Core\Notification\Domain;

use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

require_once __DIR__ . '/../../Shared/param_funcs.php';

abstract class AbstractNotification
{
    public array $whmcsHookParams;

    public Client $client;

    public ?int $categoryId;

    /**
     * @var \Closure(): int
     */
    public $findClientId;

    protected int $clientId;

    /**
     * @var ?\Closure(): int
     */
    public $findCategoryId;

    /**
     * @var array<NotificationTemplate>
     */
    public array $templates;

    public int $priority;

    /**
     * @param  string                          $code           Must be unique.
     * @param  NotificationReportCategory      $category
     * @param  null|Hooks                      $hook
     * @param  NotificationParameterCollection $parameters
     * @param  \Closure                        $findClientId
     * @param  \Closure                        $findCategoryId
     */
    public function __construct(
        public readonly string $code,
        public readonly ?NotificationReportCategory $category,
        public readonly ?Hooks $hook,
        public readonly NotificationParameterCollection $parameters,
        $findClientId,
        $findCategoryId = null,
        public readonly ?string $description = null,
    ) {
        $this->findClientId   = $findClientId->bindTo($this, self::class);
        $this->findCategoryId = $findCategoryId ? $findCategoryId->bindTo($this, self::class) : null;
        $this->priority       = 999;

        $this->parameters->fixThisBindOnValueGetters($this);
    }

    /**
     * IMPORTANT! IMPORTANT! IMPORTANT! IMPORTANT!
     *
     * This method should be called before sending the notification.
     *
     * It initializes properties that require the $whmcsHookParams.
     *
     * @param  null|array $whmcsHookParams
     *
     * @return void
     */
    public function finishInit(?array $whmcsHookParams)
    {
        // This MUST come before calling $this->findCategoryId or $this->findClientId.
        $this->whmcsHookParams = $whmcsHookParams;

        // Needs this because of the parameter closures in built_in_notifications_recipes.php.
        $this->parameters->fixThisBindOnValueGetters($this);

        // This MUST come before calling $this->findClientId.
        $this->categoryId = $this->findCategoryId ? ($this->findCategoryId)() : null;

        $this->clientId = ($this->findClientId)();
        $this->client   = new Client($this->clientId);
    }

    /**
     * @param  NotificationTemplate[] $templates
     *
     * @return void
     */
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
    }

    /**
     * Used for file-based notification to define custom logic for identifying if the notification
     * should run or not.
     *
     * @return boolean Default to true.
     */
    public function shouldRun(): bool
    {
        return true;
    }

    /**
     * Lets a notification listen on more than one WHMCS hook, in addition to
     * its primary $this->hook (e.g. an invoice can be created via a client
     * order/cron generation, which fires InvoiceCreated, OR created directly
     * in the admin area, which WHMCS does NOT route through InvoiceCreated -
     * it fires the separate InvoiceCreationAdminArea hook instead).
     *
     * Every hook returned here dispatches through the exact same
     * transformHookParams()/parameters as the primary hook.
     *
     * @return Hooks[]
     */
    public function additionalHooks(): array
    {
        return [];
    }

    /**
     * Lets a hook-triggered notification transform/enrich the raw WHMCS hook
     * params before they're used to resolve this notification's parameters
     * and dispatched to the platform.
     *
     * Some WHMCS hooks are very sparse (e.g. InvoicePaid only provides
     * `invoiceid`), while a notification's parameter closures may need much
     * more (client id, invoice number, totals, etc). Override this to look
     * up and return the full payload your parameters need.
     *
     * Returning null skips sending entirely for this event (e.g. to
     * implement a "send once" dedup guard, or to ignore an event that
     * doesn't apply).
     *
     * Default: no transformation.
     *
     * @param  array<mixed> $whmcsHookParams
     *
     * @return array<mixed>|null
     */
    public function transformHookParams(array $whmcsHookParams): ?array
    {
        return $whmcsHookParams;
    }

    public function fillTemplate(NotificationTemplate $template): string
    {
        $parameterCodesInTemplate = $template->getUsedParameterCodes();
        $notificationParameters   = $this->parameters->getParametersByCode($parameterCodesInTemplate);

        $filledTpl = $template->template;

        foreach ($notificationParameters as $notificationParameter) {
            $paramPlaceholder = "{{{$notificationParameter->code}}}";

            $filledTpl = str_replace(
                $paramPlaceholder,
                ($notificationParameter->valueGetter)(),
                $filledTpl
            );
        }

        return $filledTpl;
    }

}
