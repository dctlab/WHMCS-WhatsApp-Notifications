<?php

use Dct\HookNotification\Core\AdminUI\Infrastructure\AdminUIRenderer;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Dct\HookNotification\Core\Shared\Infrastructure\Setup\DatabaseSetup;
use Dct\HookNotification\Core\Shared\Infrastructure\Setup\DatabaseUpgrade;
use WHMCS\Database\Capsule;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Core/Shared/Infrastructure/helpers.php';

/**
 * @since 1.0.0View.php
 *
 * @return array
 */
function dct_whatsapp_notifications_config()
{
    $language = Capsule::table('tblconfiguration')->where('setting', 'Language')->first('value')->value;

    if (!in_array($language, ['english', 'portuguese-br', 'portuguese-pt'], true)) {
        $language = 'english';
    }

    $version = '5.12.0'; // CHANGE MANUALLY ON RELEASE

    return [
        'name' => lkn_hn_lang('WhatsApp and Chatwoot'),
        'description' => '<div style="margin-bottom: 10px">'.lkn_hn_lang('Send notifications to your customers through WhatsApp or Chatwoot.') . '</div>
        <div>By <a href="https://dctlab.directcybertech.com/" target="_blank"><strong>DCTLAB</strong></a></div>',
        'author' => '<a href="https://dctlab.directcybertech.com/" target="_blank">DCTLAB</a>',
        'language' => $language,
        'version' => $version,
        'fields' => [
            'header' => [
                'Description' => '<div style="margin: 30px;">
                    <div>
                        <a href="addonmodules.php?module=dct_whatsapp_notifications">
                            <strong>' . lkn_hn_lang('Access Module Settings') . '</strong>
                        </a> &#x2022
                        <a href="logs/module-log">
                            <strong>' . lkn_hn_lang('Access Module Logs') . '</strong>
                        </a>
                    </div>
                    <p style="margin-top: 12px">
                        <i class="fas fa-exclamation-triangle fa-sm"></i>
                        ' . lkn_hn_lang('Grant Access Control to your group to access the module settings page.') . '
                    </p>
                    <p style="margin-top: 12px">
                        <i class="fas fa-exclamation-triangle fa-sm"></i>
                        ' . lkn_hn_lang('If you encounter activation issues due to database tables, make sure that the tblclients table is using the InnoDB engine with the utf8mb4_unicode_ci collation.<br>We recommend backing up the tblclients table before making any changes.') . '
                    </p>
                </div>',
            ],
        ],
    ];
}

/**
 * @since 2.0.0
 *
 * @param array $vars
 *
 * @see https://developers.whmcs.com/addon-modules/upgrades/
 *
 * @return void
 */
function dct_whatsapp_notifications_upgrade($vars): void
{
    $currentlyInstalledVersion = $vars['version'];

    lkn_hn_config_set(Platforms::MODULE, Settings::MODULE_PREVIOUS_VERSION, $currentlyInstalledVersion);

    if (!$currentlyInstalledVersion) {
        return;
    }

    if ($currentlyInstalledVersion < 2.0) {
        DatabaseUpgrade::v200();
    }

    if ($currentlyInstalledVersion < 2.3) {
        DatabaseUpgrade::v230();
    }

    if ($currentlyInstalledVersion < 3.1) {
        DatabaseUpgrade::v310();
    }

    if ($currentlyInstalledVersion < 3.2) {
        DatabaseUpgrade::v320();
    }

    if ($currentlyInstalledVersion < 3.3) {
        DatabaseUpgrade::v330();
    }

    if ($currentlyInstalledVersion < 3.7) {
        DatabaseUpgrade::v370();
    }

    if ($currentlyInstalledVersion < 3.8) {
        DatabaseUpgrade::v380();
    }

    if (version_compare($currentlyInstalledVersion, '3.9.0', '<')) {
        DatabaseUpgrade::v390();
    }

    if (version_compare($currentlyInstalledVersion, '4.0.0', '<')) {
        DatabaseUpgrade::v400();
    }

    if (version_compare($currentlyInstalledVersion, '4.1.2', '<')) {
        DatabaseUpgrade::v412();
    }

    if (version_compare($currentlyInstalledVersion, '4.3.0', '<')) {
        DatabaseUpgrade::v430();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.0', '<')) {
        DatabaseUpgrade::v450();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.1', '<')) {
        DatabaseUpgrade::v451();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.6', '<')) {
        DatabaseUpgrade::v452();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.7', '<')) {
        DatabaseUpgrade::v453();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.8', '<')) {
        DatabaseUpgrade::v454();
    }

    if (version_compare($currentlyInstalledVersion, '4.5.13', '<')) {
        DatabaseUpgrade::v455();
    }

    if (version_compare($currentlyInstalledVersion, '5.0.0', '<')) {
        DatabaseUpgrade::v500RenameTablesForModuleRename();
    }

    if (version_compare($currentlyInstalledVersion, '5.1.2', '<')) {
        DatabaseUpgrade::v512EnsureUtf8mb4();
    }

    if (version_compare($currentlyInstalledVersion, '5.3.1', '<')) {
        DatabaseUpgrade::v531AddReportsCreatedAtStatusIndex();
    }

    // ensureDeliveryTrackingSchema() (introduced in 4.5.3) also self-heals
    // v450-v455 on every request as a safety net, in case this _upgrade()
    // hook was never triggered by WHMCS after a file update.

    (new Smarty())->clearAllCache();
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
}

/**
 * @since 1.0.0
 * @see https://developers.whmcs.com/addon-modules/installation-uninstallation/
 *
 * @return array
 */
function dct_whatsapp_notifications_activate(): array
{
    $response = DatabaseSetup::activate();

    if ($response['status'] === 'success') {
        lkn_hn_config_set(Platforms::MODULE, Settings::DISMISS_INSTALLATION_WELCOME, false);
    }

    return $response;
}

/**
 * @since 4.6.3
 * @see https://developers.whmcs.com/addon-modules/client-area-output/
 *
 * Lets a logged-in client manage their own WhatsApp notification
 * preferences: a master on/off toggle, plus per-notification-type opt-outs.
 * Accessible at index.php?m=dct_whatsapp_notifications in the client area (needs
 * "Client Area Display" enabled for this addon under Setup > Addon Modules
 * to show up as a menu link automatically).
 *
 * @param array $vars
 *
 * @return array
 */
function dct_whatsapp_notifications_clientarea(array $vars): array
{
    require_once __DIR__ . '/src/Core/ClientPreferences/Application/ClientNotificationPreferenceService.php';

    $clientId = (int) ($_SESSION['uid'] ?? 0);

    if ($clientId <= 0) {
        return [
            'pagetitle' => lkn_hn_lang('WhatsApp Notifications'),
            'breadcrumb' => ['index.php?m=dct_whatsapp_notifications' => lkn_hn_lang('WhatsApp Notifications')],
            'templatefile' => 'clientarea',
            'requirelogin' => true,
            'vars' => ['error' => lkn_hn_lang('Please log in to manage your WhatsApp notification preferences.')],
        ];
    }

    $service = new \Dct\HookNotification\Core\ClientPreferences\Application\ClientNotificationPreferenceService();
    $saved   = false;

    if (!empty($_POST['lkn_hn_save_prefs'])) {
        $whatsappEnabled = !empty($_POST['whatsapp_enabled']);
        $allCodes        = array_map('strval', $_POST['all_notification_codes'] ?? []);
        $enabledCodes    = array_map('strval', $_POST['enabled_notifications'] ?? []);
        $disabledCodes   = array_values(array_diff($allCodes, $enabledCodes));

        $service->savePreferences($clientId, $whatsappEnabled, $disabledCodes);

        $saved = true;
    }

    $prefs = $service->getPreferences($clientId);

    return [
        'pagetitle' => lkn_hn_lang('WhatsApp Notifications'),
        'breadcrumb' => ['index.php?m=dct_whatsapp_notifications' => lkn_hn_lang('WhatsApp Notifications')],
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars' => [
            'saved' => $saved,
            'whatsapp_enabled' => $prefs['whatsapp_enabled'],
            'disabled_notifications' => $prefs['disabled_notifications'],
            'notification_types' => $service->getToggleableNotificationTypes(),
        ],
    ];
}

/**
 * @since 1.0.0
 * @see https://developers.whmcs.com/addon-modules/admin-area-output/
 *
 * @param array $vars
 *
 * @return void
 */
function dct_whatsapp_notifications_output(array $vars): void
{
    try {
        DatabaseUpgrade::ensureDeliveryTrackingSchema();

        $receivedRoute = isset($_REQUEST['page']) ? strip_tags($_REQUEST['page']) : 'home';

        echo (new AdminUIRenderer())->getView($receivedRoute);
    } catch (Throwable $th) {
        $msg = 'Internal error';

        $error = $th->__toString();

        echo "
        <style>
            #lkn-hn-alert {
                margin: 0px;
                margin-top: 10px;
                margin-bottom: 30px;
            }

            #lkn-hn-alert pre {
                margin: 20px 0px;
                background-color: transparent;
                border-color: #00000014;
            }
        </style>

        <div
        id='lkn-hn-alert'
        class='alert alert-danger alert-dismissible'
        role='alert'
        style='margin: 0px; margin-top: 10px; margin-bottom: 30px;'
    >
            <div
                id='lkn-hn-alert'
                class='alert alert-danger alert-dismissible'
                role='alert'
            >
                <i class='fas fa-exclamation-square'></i>
                {$msg}
                <pre>{$error}</pre>
            </div>
    </div>
        ";
    }
}
