<?php

namespace Dct\HookNotification\Core\NotificationReport\Domain;

/**
 * Represents the delivery lifecycle of a message as confirmed by the Meta
 * WhatsApp Cloud API status webhook (`messages.statuses`).
 *
 * This is intentionally kept separate from NotificationReportStatus, which
 * only represents whether the module's *attempt* to call the send API
 * succeeded or not. DeliveryStatus instead represents what actually
 * happened to the message on WhatsApp's side, and is only ever filled in
 * asynchronously, after the webhook receives an update from Meta.
 *
 * @since 4.5.0
 */
enum DeliveryStatus: string
{
    case SENT      = 'sent';
    case DELIVERED = 'delivered';
    case READ      = 'read';
    case FAILED    = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::SENT => lkn_hn_lang('Sent'),
            self::DELIVERED => lkn_hn_lang('Delivered'),
            self::READ => lkn_hn_lang('Read'),
            self::FAILED => lkn_hn_lang('Failed'),
        };
    }

    /**
     * Bootstrap class for a Bootstrap 3 `label-*` class, used on the reports table.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::SENT => 'label-info',
            self::DELIVERED => 'label-primary',
            self::READ => 'label-success',
            self::FAILED => 'label-danger',
        };
    }
}
