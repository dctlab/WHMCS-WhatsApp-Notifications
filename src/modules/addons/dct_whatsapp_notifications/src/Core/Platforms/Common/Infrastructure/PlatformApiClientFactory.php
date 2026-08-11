<?php

namespace Dct\HookNotification\Core\Platforms\Common\Infrastructure;

use Dct\HookNotification\Core\Platforms\Baileys\BaileysApiClient;
use Dct\HookNotification\Core\Platforms\Baileys\Domain\BaileysSettings;
use Dct\HookNotification\Core\Platforms\Botms\Domain\BotmsSettings;
use Dct\HookNotification\Core\Platforms\Botms\Infrastructure\BotmsApiClient;
use Dct\HookNotification\Core\Platforms\Chatwoot\Domain\ChatwootSettings;
use Dct\HookNotification\Core\Platforms\Chatwoot\Infrastructure\ChatwootApiClient;
use Dct\HookNotification\Core\Platforms\EvolutionApi\Domain\EvolutionApiSettings;
use Dct\HookNotification\Core\Platforms\EvolutionApi\Infrastructure\EvolutionApiClient;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Domain\MetaWhatsAppSettings;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Infrastructure\MetaWhatsAppApiClient;

final class PlatformApiClientFactory
{
    public function makeBaileysClient(BaileysSettings $settings): BaileysApiClient
    {
        return new BaileysApiClient($settings->endpoint, $settings->apiToken);
    }

    public function makeBotmsClient(BotmsSettings $settings): BotmsApiClient
    {
        return new BotmsApiClient($settings->instanceId, $settings->accessToken);
    }

    public function makeEvolutionApiClient(EvolutionApiSettings $settings): EvolutionApiClient
    {
        return new EvolutionApiClient($settings->apiUrl, $settings->apiKey);
    }

    public function makeMetaWhatsAppClient(MetaWhatsAppSettings $settings): MetaWhatsAppApiClient
    {
        return new MetaWhatsAppApiClient(
            $settings->apiVersion,
            $settings->phoneNumberId,
            $settings->userAccessToken,
            $settings->businessAccountId,
        );
    }

    public function makeChatwootClient(ChatwootSettings $settings): ChatwootApiClient
    {
        return new ChatwootApiClient(
            $settings->accountId,
            $settings->url,
            $settings->apiAccessToken,
        );
    }
}
