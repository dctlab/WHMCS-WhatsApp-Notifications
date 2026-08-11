<?php

namespace Dct\HookNotification\Core\Platforms\Common\Infrastructure;

use InvalidArgumentException;
use Dct\HookNotification\Core\Platforms\Baileys\Domain\BaileysPlatform;
use Dct\HookNotification\Core\Platforms\Botms\Domain\BotmsPlatform;
use Dct\HookNotification\Core\Platforms\Chatwoot\Domain\ChatwootPlatform;
use Dct\HookNotification\Core\Platforms\Common\AbstractPlatform;
use Dct\HookNotification\Core\Platforms\EvolutionApi\Domain\EvolutionApiNotificationParser;
use Dct\HookNotification\Core\Platforms\EvolutionApi\Domain\EvolutionApiPlatform;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Domain\MetaWhatsAppNotificationParser;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Domain\MetaWhatsAppPlatform;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Repository\SettingsRepository;

final class PlatformFactory
{
    private SettingsRepository $settingsRepository;
    private PlatformSettingsFactory $settingsFactory;
    private PlatformApiClientFactory $apiClientFactory;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
        $this->settingsFactory    = new PlatformSettingsFactory();
        $this->apiClientFactory   = new PlatformApiClientFactory();
    }

    public function make(Platforms $platform): AbstractPlatform
    {
        $raw = $this->settingsRepository->getSettingsForPlatform($platform);

        return match ($platform) {
            Platforms::BAILEYS => $this->makeBaileys($raw),
            Platforms::BOTMS => $this->makeBotms($raw),
            Platforms::WP_EVO => $this->makeEvolution($raw),
            Platforms::WHATSAPP => $this->makeMetaWhatsApp($raw),
            Platforms::CHATWOOT => $this->makeChatwoot($raw),
            default => throw new \InvalidArgumentException("Unsupported platform: $platform"),
        };
    }

    public function makeSettings(Platforms $platform)
    {
        $raw = $this->settingsRepository->getSettingsForPlatform($platform);

        return match ($platform) {
            Platforms::BAILEYS => $this->settingsFactory->makeBaileysSettings($raw),
            Platforms::BOTMS => $this->settingsFactory->makeBotmsSettings($raw),
            Platforms::WP_EVO => $this->settingsFactory->makeEvolutionApiSettings($raw),
            Platforms::WHATSAPP => $this->settingsFactory->makeMetaWhatsAppSettings(),
            Platforms::CHATWOOT => $this->settingsFactory->makeChatwootSettings(
                $raw,
                $this->settingsFactory::makeLiveChatSettings($raw),
                $this->settingsFactory::makeModuleSettings($raw)
            ),
            default => throw new InvalidArgumentException("Unsupported platform: $platform"),
        };
    }

    private function makeBaileys(array $raw): BaileysPlatform
    {
        $settings = $this->settingsFactory::makeBaileysSettings($raw);
        $client   = $this->apiClientFactory->makeBaileysClient($settings);

        return new BaileysPlatform($settings, new EvolutionApiNotificationParser(), $client);
    }

    private function makeBotms(array $raw): BotmsPlatform
    {
        $settings = $this->settingsFactory::makeBotmsSettings($raw);
        $client   = $this->apiClientFactory->makeBotmsClient($settings);

        return new BotmsPlatform($settings, new EvolutionApiNotificationParser(), $client);
    }

    private function makeEvolution(array $raw): EvolutionApiPlatform
    {
        $settings = $this->settingsFactory::makeEvolutionApiSettings($raw);
        $client   = $this->apiClientFactory->makeEvolutionApiClient($settings);

        return new EvolutionApiPlatform($settings, new EvolutionApiNotificationParser(), $client);
    }

    private function makeMetaWhatsApp(array $raw): MetaWhatsAppPlatform
    {
        $settings = $this->settingsFactory::makeMetaWhatsAppSettings();
        $client   = $this->apiClientFactory->makeMetaWhatsAppClient($settings);

        return new MetaWhatsAppPlatform($settings, new MetaWhatsAppNotificationParser(), $client);
    }

    private function makeChatwoot(array $raw): ChatwootPlatform
    {
        $moduleSettings   = $this->settingsFactory::makeModuleSettings(
            $this->settingsRepository->getSettingsForPlatform(Platforms::MODULE)
        );
        $liveChatSettings = $this->settingsFactory::makeLiveChatSettings($raw);
        $settings         = $this->settingsFactory::makeChatwootSettings($raw, $liveChatSettings, $moduleSettings);
        $client           = $this->apiClientFactory->makeChatwootClient($settings);

        return new ChatwootPlatform($settings, new EvolutionApiNotificationParser(), $client);
    }
}
