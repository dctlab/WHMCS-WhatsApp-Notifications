<?php

namespace Dct\HookNotification\Core\Shared\Application;

use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Repository\SettingsRepository;
use Dct\HookNotification\Core\Shared\Infrastructure\Result;

/**
 * This class should use {Platform}SetupService when a platform settings changes.
 */
final class SettingsService
{
    private readonly SettingsRepository $settingsRepository;

    public function __construct()
    {
        $this->settingsRepository = new SettingsRepository();
    }

    /**
     * @param  Platforms   $platform
     * @param  string|null $subpage
     * @return array
     */
    public function getSettingsForView(Platforms $platform, ?string $subpage = null): array
    {
        $platformFolder = match ($platform) {
            Platforms::BAILEYS => 'Baileys',
            Platforms::BOTMS => 'Botms',
            Platforms::WP_EVO => 'EvolutionApi',
            Platforms::WHATSAPP => 'MetaWhatsApp',
            Platforms::CHATWOOT => 'Chatwoot',
            Platforms::MODULE => 'Module',
            Platforms::BULK_MESSAGING => '../BulkMessaging',
        };

        $settingsDefPath = __DIR__ . "/../../Platforms/{$platformFolder}/Infrastructure/";

        if ($subpage) {
            $settingsDefPath .= str_replace('-', '_', $subpage) . '_settings.php';
        } else {
            $settingsDefPath .= 'settings.php';
        }

        $settingsDef = require $settingsDefPath;

        $filledSettingsDef = array_map(
            function ($settingDef) {
                if (isset($settingDef['separator'])) {
                    return $settingDef;
                }

                $settingDef['id']      = $settingDef['setting']->value;
                $settingDef['current'] = lkn_hn_config($settingDef['setting']);

                return $settingDef;
            },
            $settingsDef
        );

        return $filledSettingsDef;
    }

    /**
     * @param  Platforms                           $platform
     * @param  string|null                         $subpage
     * @param  array<string, string|array<string>> $incomingSettings
     *
     * @return \Dct\HookNotification\Core\Shared\Infrastructure\Result
     */
    public function updateSettings(Platforms $platform, ?string $subpage, array $incomingSettings): Result
    {
        $settingsDef     = $this->getSettingsForView($platform, $subpage);
        $validSettingIds = array_column($settingsDef, 'id');

        // Maps each setting id to its 'type' (e.g. 'password', 'text',
        // 'checkbox') so the loop below can tell which fields are
        // credentials without relying on anything from the HTML/request -
        // separator entries have neither 'id' nor 'type', so they are
        // automatically skipped by array_column() here, same as the
        // $validSettingIds line above already does.
        $settingTypeById = array_column($settingsDef, 'type', 'id');

        $filteredSettings = [];
        $credentialValuesToMask = [];

        foreach ($validSettingIds as $settingId) {
            $isPasswordType = ($settingTypeById[$settingId] ?? null) === 'password';

            if (!isset($incomingSettings[$settingId])) {
                if ($isPasswordType) {
                    // No input for this credential in the submission at
                    // all (shouldn't normally happen for a text-style
                    // field, but handled defensively) - preserve whatever
                    // is currently stored by not touching this setting.
                    continue;
                }

                $filteredSettings[$settingId] = '';

                continue;
            }

            $newValue = $incomingSettings[$settingId];

            if (is_string($newValue)) {
                $newValue = rtrim(trim($newValue), '/');
            }

            if ($isPasswordType && $newValue === '') {
                // Credential field submitted blank - the browser never
                // received the real value in the first place (see the
                // template), so a blank submission unambiguously means
                // "leave it as-is", not "clear it". Skip this setting
                // entirely rather than writing an empty string over it.
                continue;
            }

            if ($isPasswordType && is_string($newValue) && $newValue !== '') {
                $credentialValuesToMask[] = $newValue;
            }

            $filteredSettings[$settingId] = $newValue;
        }

        return $this->settingsRepository->massUpsert($platform, $filteredSettings, $credentialValuesToMask);
    }
}
