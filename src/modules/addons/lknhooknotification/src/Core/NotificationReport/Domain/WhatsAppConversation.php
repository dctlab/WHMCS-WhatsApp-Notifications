<?php

namespace Lkn\HookNotification\Core\NotificationReport\Domain;

use DateTime;

/**
 * Represents a single WhatsApp conversation (Meta's 24-hour billing window),
 * as reported by the Cloud API status webhook.
 *
 * @since 4.5.1
 */
final class WhatsAppConversation
{
    public function __construct(
        public readonly int $id,
        public readonly string $conversationId,
        public readonly ?int $clientId,
        public readonly ?string $phoneNumber,
        public readonly ?string $category,
        public readonly ?string $pricingModel,
        /**
         * true: Meta reported this conversation as billable.
         * false: Meta reported it as free (e.g. within a free tier/entry point).
         * null: Meta did not report a billable flag for this conversation (some
         *       API versions/categories no longer send it).
         */
        public readonly ?bool $billable,
        public readonly ?string $originType,
        public readonly int $messageCount,
        public readonly ?DateTime $firstMessageAt,
        public readonly ?DateTime $lastMessageAt,
        public readonly ?DateTime $expirationAt,
        public readonly ?string $lastMessagePreview = null,
        public readonly ?string $lastMessageDirection = null,
    ) {
    }

    public function lastMessageDirectionLabel(): string
    {
        return match ($this->lastMessageDirection) {
            'inbound' => lkn_hn_lang('Received'),
            'outbound' => lkn_hn_lang('Sent'),
            default => '',
        };
    }

    public function lastMessageDirectionIcon(): string
    {
        return match ($this->lastMessageDirection) {
            'inbound' => 'fa-arrow-down text-success',
            'outbound' => 'fa-arrow-up text-muted',
            default => '',
        };
    }

    public function categoryLabel(): string
    {
        return lkn_hn_wa_category_label($this->category);
    }

    public function billableLabel(): string
    {
        if ($this->billable === null) {
            return lkn_hn_lang('Unknown');
        }

        return $this->billable ? lkn_hn_lang('Billable') : lkn_hn_lang('Free');
    }

    public function billableBadgeClass(): string
    {
        if ($this->billable === null) {
            return 'label-default';
        }

        return $this->billable ? 'label-danger' : 'label-success';
    }

    public function isExpired(): bool
    {
        return $this->expirationAt !== null && $this->expirationAt < new DateTime();
    }
}
