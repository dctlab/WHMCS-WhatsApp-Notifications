<?php

namespace Dct\HookNotification\Core\NotificationReport\Domain;

use DateTime;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;

final class NotificationReport
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $clientId,
        public readonly ?int $categoryId,
        public readonly ?NotificationReportCategory $category,
        public readonly ?NotificationReportStatus $status,
        public readonly ?string $msg,
        public readonly ?Platforms $platform,
        public readonly string $notificationCode,
        public readonly ?Hooks $notificationHook,
        public readonly DateTime $createdAt,
        public readonly ?string $target,
        public readonly ?string $waMessageId = null,
        public readonly ?DeliveryStatus $deliveryStatus = null,
        public readonly ?DateTime $deliveryUpdatedAt = null,
        public readonly ?int $resentFromReportId = null,
        public readonly ?int $queueId = null,
        public readonly bool $canResend = false,
        public readonly ?bool $billable = null,
        public readonly ?string $waCategory = null,
        public readonly ?string $messagePreview = null,
    ) {
    }

    public function billableLabel(): string
    {
        if ($this->billable === null) {
            return lkn_hn_lang('Unknown');
        }

        return $this->billable ? lkn_hn_lang('Yes') : lkn_hn_lang('No');
    }

    public function billableBadgeClass(): string
    {
        if ($this->billable === null) {
            return 'label-default';
        }

        return $this->billable ? 'label-danger' : 'label-success';
    }

    public function waCategoryLabel(): string
    {
        return lkn_hn_wa_category_label($this->waCategory);
    }
}
