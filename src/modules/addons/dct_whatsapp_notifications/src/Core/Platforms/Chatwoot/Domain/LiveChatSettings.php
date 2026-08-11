<?php

namespace Dct\HookNotification\Core\Platforms\Chatwoot\Domain;

use Dct\HookNotification\Core\Platforms\Common\AbstractPlatformSettings;

class LiveChatSettings extends AbstractPlatformSettings
{
    public function __construct(
        public ?bool $enableLiveChat,
        public ?string $clientIdentifierKey,
        public ?string $userIdentityValidation,
        public ?string $liveChatScript,
        public ?array $clientStatsToSend,
        public ?array $customFieldsToSend,
        public ?array $liveChatModuleAttrsToSend,
    ) {
    }
}
