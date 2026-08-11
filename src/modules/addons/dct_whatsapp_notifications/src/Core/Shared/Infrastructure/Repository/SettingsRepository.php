<?php

namespace Dct\HookNotification\Core\Shared\Infrastructure\Repository;

use Exception;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;

final class SettingsRepository extends BaseRepository
{
    /**
     * @param  array     $newValuesBySetting [setting => value]
     * @param  Platforms $platform
     * @param  string[]  $maskValues Credential values to redact from the log entry (WHMCS's own
     *                                logModuleCall masking mechanism - see lkn_hn_log()'s $masks
     *                                param) - the caller passes the actual password-type values
     *                                being saved here, since this method has no way to know on its
     *                                own which of $newValuesBySetting's values are secrets.
     *
     * @return \Dct\HookNotification\Core\Shared\Infrastructure\Result
     */
    public function massUpsert(Platforms $platform, array $newValuesBySetting, array $maskValues = [])
    {
        try {
            $upsertStatus = [];

            foreach ($newValuesBySetting as $setting => $value) {
                $result = $this->query->table('mod_dct_hook_notification_configs')
                    ->updateOrInsert(
                        ['platform' => $platform->value, 'setting' => $setting],
                        ['value' => $value]
                    );

                $upsertStatus[$setting] = $result;
            }

            lkn_hn_log(
                'Update platform settings',
                [
                    'platform' => $platform,
                    'newValuesBySetting' => $newValuesBySetting,
                ],
                ['upsertStatus' => $upsertStatus],
                $maskValues
            );

            return lkn_hn_result(
                'success',
                data: ['upsertStatus' => $upsertStatus]
            );
        } catch (Exception $e) {
            lkn_hn_log(
                'Update platform settings failed',
                [
                    'platform' => $platform,
                    'newValuesBySetting' => $newValuesBySetting,
                ],
                ['exception' => $e->getMessage()],
                $maskValues
            );

            return lkn_hn_result(
                'error',
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    public function getSettingsForPlatform(Platforms $platform): array
    {
        $rawPlatformSettings = $this
            ->query->table('mod_dct_hook_notification_configs')
            ->where('platform', $platform->value)
            ->get()
            ->toArray();

        return array_column($rawPlatformSettings, 'value', 'setting');
    }

    public function updateSettingsForPlatform(
        Platforms $platform,
        Settings $setting,
        mixed $newValue
    ) {
        $updateResults = $this->query
            ->table('mod_dct_hook_notification_configs')
            ->where('setting', $setting->value)
            ->when('platform', $platform->value)
            ->update(['value' => $newValue]);

        return $updateResults;
    }
}
