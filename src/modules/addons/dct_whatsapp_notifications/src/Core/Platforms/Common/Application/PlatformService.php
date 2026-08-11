<?php

namespace Dct\HookNotification\Core\Platforms\Common\Application;

use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;

final class PlatformService
{
    /**
     * @return Platforms[]
     */
    public function getEnabledPlatforms(bool $standardOnly = false): array
    {
        $enabledPlatforms = [];

        if (!$standardOnly && lkn_hn_config(Settings::CW_ENABLED)) {
            $enabledPlatforms[] = Platforms::CHATWOOT;
        }

        if (lkn_hn_config(Settings::WP_EVO_ENABLE)) {
            $enabledPlatforms[] = Platforms::WP_EVO;
        }

        if (lkn_hn_config(Settings::BAILEYS_ENABLE)) {
            $enabledPlatforms[] = Platforms::BAILEYS;
        }

        if (lkn_hn_config(Settings::BOTMS_ENABLE)) {
            $enabledPlatforms[] = Platforms::BOTMS;
        }

        if (lkn_hn_config(Settings::WP_META_ENABLE)) {
            $enabledPlatforms[] = Platforms::WHATSAPP;
        }

        return $enabledPlatforms;
    }
}
