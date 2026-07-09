<?php

namespace Lkn\HookNotification\Core\Platforms\Botms\Domain;

use Lkn\HookNotification\Core\Platforms\Common\AbstractPlatformSettings;

class BotmsSettings extends AbstractPlatformSettings
{
    public function __construct(
        public readonly ?bool $enabled,
        public readonly ?string $instanceId,
        public readonly ?string $accessToken,
        public ?int $wpCustomFieldId,
    ) {
    }
}
