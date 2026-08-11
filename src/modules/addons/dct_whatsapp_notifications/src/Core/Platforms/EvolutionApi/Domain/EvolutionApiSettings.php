<?php

namespace Dct\HookNotification\Core\Platforms\EvolutionApi\Domain;

use Dct\HookNotification\Core\Platforms\Common\AbstractPlatformSettings;

class EvolutionApiSettings extends AbstractPlatformSettings
{
    public function __construct(
        public bool $enabled,
        public string $apiUrl,
        public string $apiKey,
        public string $instanceName,
        public ?int $wpCustomFieldId = null,
    ) {
    }
}
