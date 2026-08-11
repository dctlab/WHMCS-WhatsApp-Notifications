<?php

namespace Dct\HookNotification\Core\Platforms\Baileys\Domain;

use Dct\HookNotification\Core\Platforms\Common\AbstractPlatformSettings;

class BaileysSettings extends AbstractPlatformSettings
{
    public function __construct(
        public readonly string $enabled,
        public readonly string $apiToken,
        public ?int $wpCustomFieldId,
        public readonly string $endpoint,
    ) {
    }
}
