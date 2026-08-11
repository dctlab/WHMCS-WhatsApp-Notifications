<?php

namespace Dct\HookNotification\Core\Platforms\MetaWhatsApp\Application;

use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Infrastructure\MetaWhatsAppApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\ApiResponse;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Repository\SettingsRepository;

final class MetaWhatsAppService
{
    private MetaWhatsAppApiClient $metaWhatsAppApiClient;

    public function __construct()
    {
        $rawMetaWhatsAppSettings = (new SettingsRepository())->getSettingsForPlatform(Platforms::WHATSAPP);

        $metaWhatsAppSettings = PlatformSettingsFactory::makeMetaWhatsAppSettings();

        $this->metaWhatsAppApiClient = new MetaWhatsAppApiClient(
            $metaWhatsAppSettings->apiVersion,
            $metaWhatsAppSettings->phoneNumberId,
            $metaWhatsAppSettings->userAccessToken,
            $metaWhatsAppSettings->businessAccountId
        );
    }

    public function getMessageTemplatesForView(): ApiResponse
    {
        return $this->metaWhatsAppApiClient->getMessageTemplates(
            [
                'fields' => 'name,language,components,status&status=APPROVED',
                'limit' => 9999,
            ]
        );
    }
}
