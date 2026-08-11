<?php

namespace Dct\HookNotification\Core\AdminUI\Http\Controllers;

use DateTime;
use Dct\HookNotification\Core\AdminUI\Application\Services\LicenseService;
use Dct\HookNotification\Core\AdminUI\Application\Services\VersionUpgradeWarningService;
use Dct\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Dct\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;
use Throwable;

final class HomepageController extends BaseController
{
    private readonly NotificationReportService $notificationReportService;

    public function __construct(View $view)
    {
        $this->notificationReportService = new NotificationReportService();

        parent::__construct($view);
    }

    public function viewHomepage(array $request): void
    {
        $licenseService = LicenseService::getInstance();

        $licenseCheckRes = $licenseService->isLicenseActive();

        [$rangeKey, $dateFrom, $dateTo] = $this->resolveDateRange($request);

        try {
            $dashboard = $this->notificationReportService->getPerformanceOverview($dateFrom, $dateTo);
            $dashboardError = null;
        } catch (Throwable $th) {
            lkn_hn_log('Dashboard: failed to load analytics', [], ['exception' => $th->__toString()]);
            $dashboard = null;
            $dashboardError = lkn_hn_lang('Unable to load analytics.');
        }

        $viewParams = [
            'license_status' => $licenseCheckRes->code,
            'new_version_alert' => $this->newVersion(),
            'dismiss_v400_alert' => true,
            'dashboard' => $dashboard,
            'dashboard_error' => $dashboardError,
            'date_range_key' => $rangeKey,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'providers' => $this->getProviderConfigurationStatus(),
            'date_range_options' => [
                ['key' => 'today', 'label' => lkn_hn_lang('Today')],
                ['key' => 'yesterday', 'label' => lkn_hn_lang('Yesterday')],
                ['key' => '7d', 'label' => lkn_hn_lang('Last 7 Days')],
                ['key' => '30d', 'label' => lkn_hn_lang('Last 30 Days')],
                ['key' => 'month', 'label' => lkn_hn_lang('This Month')],
            ],
        ];

        if (isset($request['dimisv400-alert'])) {
            lkn_hn_config_set(Platforms::MODULE, Settings::MODULE_DISMISS_V400_ALERT, true);

            header('Location: ?module=dct_whatsapp_notifications&page=home');

            exit;
        }

        if (!lkn_hn_config(Settings::MODULE_DISMISS_V400_ALERT)) {
            /** @var string $previousVersion */
            $previousVersion = lkn_hn_config(Settings::MODULE_PREVIOUS_VERSION);

            if (
                version_compare(
                    $previousVersion,
                    '4.0.0',
                    '<',
                )
             ) {
                $viewParams['dismiss_v400_alert'] = false;
            }
        }

        $this->view->view(
            'pages/homepage',
            $viewParams,
        );
    }

    public function viewChangelog(array $request): void
    {
        $statistics = $this->notificationReportService->getStatistics();

        $changelog = require_once __DIR__ . '/../../Infrastructure/changelog.php';

        $this->view->view(
            'pages/changelog',
            [
                ...$statistics,
                'changelog' => $changelog,
            ],
        );
    }

    public function newVersion(): ?string
    {
        if (isset($_GET['new-version-dismiss-on-admin-home'])) {
            VersionUpgradeWarningService::setDismissOnAdminHome(true);
        }

        $mustDismissAlert = VersionUpgradeWarningService::getDismissNewVersionAlert();

        if ($mustDismissAlert) {
            return null;
        }

        $currentAdminDetails = localAPI('GetAdminDetails');
        $adminPermissons     = $currentAdminDetails['allowedpermissions'];

        if (!str_contains($adminPermissons, 'Configure Addon Modules')) {
            return null;
        }

        $newVersion = VersionUpgradeWarningService::getNewVersion();

        $currentVersion = '4.3.3'; // CHANGE MANUALLY ON RELEASE

        if (version_compare($newVersion, $currentVersion, '>')) {
            return $this->view->view(
                'components/new_version_pop_up',
                ['new_version' => $newVersion]
            )->render();
        }

        return null;
    }

    public function handleNewVersionCheck(): void
    {
        return;
    }

    /**
     * Resolves the dashboard's date range from the request: either a
     * preset key (today/yesterday/7d/30d/month) or an explicit from/to
     * pair for a custom range. Defaults to the last 7 days.
     *
     * @param array<mixed> $request
     *
     * @return array{0: string, 1: string, 2: string} [rangeKey, dateFrom, dateTo] (Y-m-d strings)
     */
    private function resolveDateRange(array $request): array
    {
        $today = new DateTime();
        $todayStr = $today->format('Y-m-d');

        if (!empty($request['date_from']) && !empty($request['date_to'])) {
            return ['custom', (string) $request['date_from'], (string) $request['date_to']];
        }

        $rangeKey = (string) ($request['range'] ?? '7d');

        return match ($rangeKey) {
            'today' => ['today', $todayStr, $todayStr],
            'yesterday' => (function () use ($today) {
                $yesterday = (clone $today)->modify('-1 day')->format('Y-m-d');

                return ['yesterday', $yesterday, $yesterday];
            })(),
            '30d' => ['30d', (clone $today)->modify('-29 days')->format('Y-m-d'), $todayStr],
            'month' => ['month', (clone $today)->modify('first day of this month')->format('Y-m-d'), $todayStr],
            default => ['7d', (clone $today)->modify('-6 days')->format('Y-m-d'), $todayStr],
        };
    }

    /**
     * Configuration state per platform - "configured" here means the
     * required settings are saved, NOT that a live connection has been
     * verified (that would mean an API call on every dashboard render,
     * which Section 5/18 explicitly rule out). Credentials themselves are
     * never included, only whether they are present.
     *
     * @return array<string, array{enabled: bool, configured: bool}>
     */
    private function getProviderConfigurationStatus(): array
    {
        return [
            'meta' => [
                'enabled' => (bool) lkn_hn_config(Settings::WP_META_ENABLE),
                'configured' => (bool) lkn_hn_config(Settings::WP_PHONE_NUMBER_ID)
                    && (bool) lkn_hn_config(Settings::WP_USER_ACCESS_TOKEN),
            ],
            'botms' => [
                'enabled' => (bool) lkn_hn_config(Settings::BOTMS_ENABLE),
                'configured' => (bool) lkn_hn_config(Settings::BOTMS_INSTANCE_ID)
                    && (bool) lkn_hn_config(Settings::BOTMS_ACCESS_TOKEN),
            ],
            'baileys' => [
                'enabled' => (bool) lkn_hn_config(Settings::BAILEYS_ENABLE),
                'configured' => (bool) lkn_hn_config(Settings::BAILEYS_ENDPOINT_URL)
                    && (bool) lkn_hn_config(Settings::BAILEYS_API_KEY),
            ],
            'evolution' => [
                'enabled' => (bool) lkn_hn_config(Settings::WP_EVO_ENABLE),
                'configured' => (bool) lkn_hn_config(Settings::WP_EVO_API_URL)
                    && (bool) lkn_hn_config(Settings::WP_EVO_API_KEY),
            ],
            'chatwoot' => [
                'enabled' => (bool) lkn_hn_config(Settings::CW_ENABLED),
                'configured' => (bool) lkn_hn_config(Settings::CW_URL)
                    && (bool) lkn_hn_config(Settings::CW_API_ACCESS_TOKEN),
            ],
        ];
    }

    /**
     * @param  array<mixed> $request
     *
     * @return void
     */
    public function notFound404(array $request): void
    {
        $this->view->view('pages/404');
    }
}
