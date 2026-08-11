<?php

namespace Dct\HookNotification\Core\NotificationReport\Http\Controllers;

use Dct\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Dct\HookNotification\Core\NotificationReport\Domain\DeliveryStatus;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;

final class NotificationReportController extends BaseController
{
    private NotificationReportService $notificationReportService;

    public function __construct(View $view)
    {
        $this->notificationReportService = new NotificationReportService();

        parent::__construct($view);
    }

    private const ALLOWED_PER_PAGE = [10, 25, 50, 100];

    public function viewReports(array $request): void
    {
        if (!empty($request['resend'])) {
            $result = $this->notificationReportService->resendReport(intval($request['resend']));

            $this->view->alert(
                $result->code === 'success' ? 'success' : 'warning',
                $result->msg ?? '',
            );
        }

        if (!empty($request['delete'])) {
            $result = $this->notificationReportService->deleteReport(intval($request['delete']));

            $this->view->alert(
                $result->code === 'success' ? 'success' : 'warning',
                $result->msg ?? '',
            );
        }

        if (!empty($request['bulk_action']) && !empty($request['selected_ids']) && is_array($request['selected_ids'])) {
            $this->handleBulkAction($request['bulk_action'], $request['selected_ids']);
        }

        $currentPage    = $request['pageN'] ?? 1;
        $reportsPerPage = in_array((int) ($request['per_page'] ?? 30), self::ALLOWED_PER_PAGE, true)
            ? (int) $request['per_page']
            : 30;

        $filters = [
            'client' => $request['f_client'] ?? null,
            'invoice' => $request['f_invoice'] ?? null,
            'domain' => $request['f_domain'] ?? null,
            'status' => $request['f_status'] ?? null,
            'delivery_status' => $request['f_delivery_status'] ?? null,
            'platform' => $request['f_platform'] ?? null,
            'date_from' => $request['f_date_from'] ?? null,
            'date_to' => $request['f_date_to'] ?? null,
        ];

        $filters = array_filter($filters, fn ($value) => !empty($value));

        if (isset($request['f_billable']) && $request['f_billable'] !== '') {
            $filters['billable'] = $request['f_billable'];
        }

        $reportsForView = $this->notificationReportService->searchReportsForView(
            $filters,
            $reportsPerPage,
            $currentPage
        );

        $viewParams = [
            'reports' => $reportsForView['reports'],
            'current_page' => $currentPage,
            'reports_per_page' => $reportsPerPage,
            'per_page_options' => self::ALLOWED_PER_PAGE,
            'total_reports' => $reportsForView['totalReports'],
            'filters' => $request,
            'field_options' => [
                'status_options' => array_map(
                    fn ($case) => ['label' => $case->label(), 'value' => $case->value],
                    NotificationReportStatus::cases()
                ),
                'delivery_status_options' => array_map(
                    fn ($case) => ['label' => $case->label(), 'value' => $case->value],
                    DeliveryStatus::cases()
                ),
                'platform_options' => array_map(
                    fn ($case) => ['label' => $case->label(), 'value' => $case->value],
                    Platforms::cases()
                ),
                'billable_options' => [
                    ['label' => lkn_hn_lang('Billable'), 'value' => '1'],
                    ['label' => lkn_hn_lang('Free'), 'value' => '0'],
                    ['label' => lkn_hn_lang('Unknown'), 'value' => 'unknown'],
                ],
            ],
        ];

        $this->view->view('pages/reports', $viewParams);
    }

    /**
     * @param string        $action Either "delete" or "resend".
     * @param array<string> $ids
     */
    private function handleBulkAction(string $action, array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return;
        }

        $succeeded = 0;

        foreach ($ids as $id) {
            $result = $action === 'delete'
                ? $this->notificationReportService->deleteReport($id)
                : $this->notificationReportService->resendReport($id);

            if ($result->code === 'success') {
                $succeeded++;
            }
        }

        $total = count($ids);
        $verb  = $action === 'delete' ? lkn_hn_lang('deleted') : lkn_hn_lang('resent');

        $this->view->alert(
            $succeeded === $total ? 'success' : 'warning',
            lkn_hn_lang("[1] of [2] selected reports [3].", [$succeeded, $total, $verb]),
        );
    }

    /**
     * Resolves the Analytics date range from the request - same preset
     * pattern as the Dashboard's resolveDateRange() (Phase 2), but using
     * this page's own existing param names (f_date_from/f_date_to) rather
     * than switching to the Dashboard's different naming, since changing
     * an existing, working param name would break bookmarks/links and
     * isn't something this presentation phase should do.
     *
     * @param array<mixed> $request
     *
     * @return array{0: string, 1: string, 2: string} [rangeKey, dateFrom, dateTo] (Y-m-d strings)
     */
    private function resolveAnalyticsDateRange(array $request): array
    {
        $today = new \DateTime();
        $todayStr = $today->format('Y-m-d');

        if (!empty($request['f_date_from']) && !empty($request['f_date_to'])) {
            return ['custom', (string) $request['f_date_from'], (string) $request['f_date_to']];
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

    public function viewAnalytics(array $request): void
    {
        [$rangeKey, $dateFrom, $dateTo] = $this->resolveAnalyticsDateRange($request);

        try {
            $overview = $this->notificationReportService->getPerformanceOverview($dateFrom, $dateTo);
            $analyticsError = null;
        } catch (\Throwable $th) {
            lkn_hn_log('Analytics: failed to load performance overview', [], ['exception' => $th->__toString()]);
            $overview = null;
            $analyticsError = lkn_hn_lang('Unable to load analytics.');
        }

        $delivery = $overview['delivery'] ?? ['sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0];
        $totalSentAttempts = array_sum($delivery);

        // "Delivered" and "read" are mutually exclusive buckets in the raw
        // data (delivery_status holds one current value per message), but a
        // read message was necessarily delivered first - so delivery rate
        // must include both, or read messages would be excluded from it
        // entirely. Matches the same logic already used correctly in
        // getNotificationPerformance() (Phase 2) for consistency.
        $deliveryRate = $totalSentAttempts > 0
            ? round(($delivery['delivered'] + $delivery['read']) / $totalSentAttempts * 100, 1)
            : 0.0;
        $readRate     = $totalSentAttempts > 0 ? round($delivery['read'] / $totalSentAttempts * 100, 1) : 0.0;
        $failureRate  = $totalSentAttempts > 0 ? round($delivery['failed'] / $totalSentAttempts * 100, 1) : 0.0;

        $viewParams = [
            'delivery' => $delivery,
            'total_sent_attempts' => $totalSentAttempts,
            'delivery_rate' => $deliveryRate,
            'read_rate' => $readRate,
            'failure_rate' => $failureRate,
            'conversations' => $overview['conversations'] ?? null,
            'notification_performance' => $overview['notification_performance'] ?? [],
            'daily_activity' => $overview['daily_activity'] ?? [],
            'message_billable' => $overview['message_billable'] ?? null,
            'approximate_charges' => $overview['approximate_charges'] ?? null,
            'analytics_error' => $analyticsError,
            'date_range_key' => $rangeKey,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_range_options' => [
                ['key' => 'today', 'label' => lkn_hn_lang('Today')],
                ['key' => 'yesterday', 'label' => lkn_hn_lang('Yesterday')],
                ['key' => '7d', 'label' => lkn_hn_lang('Last 7 Days')],
                ['key' => '30d', 'label' => lkn_hn_lang('Last 30 Days')],
                ['key' => 'month', 'label' => lkn_hn_lang('This Month')],
            ],
            'filters' => $request,
        ];

        $this->view->view('pages/analytics', $viewParams);
    }

    public function viewConversations(array $request): void
    {
        $currentPage = $request['pageN'] ?? 1;
        $perPage     = 30;

        $filters = [
            'client' => $request['f_client'] ?? null,
            'category' => $request['f_category'] ?? null,
            'billable' => isset($request['f_billable']) ? $request['f_billable'] : null,
            'date_from' => $request['f_date_from'] ?? null,
            'date_to' => $request['f_date_to'] ?? null,
        ];

        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        $conversationsForView = $this->notificationReportService->searchConversationsForView(
            $filters,
            $perPage,
            $currentPage
        );

        $viewParams = [
            'conversations' => $conversationsForView['conversations'],
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_conversations' => $conversationsForView['totalConversations'],
            'filters' => $request,
            'field_options' => [
                'category_options' => lkn_hn_wa_category_options(),
                'billable_options' => [
                    ['label' => lkn_hn_lang('Billable'), 'value' => '1'],
                    ['label' => lkn_hn_lang('Free'), 'value' => '0'],
                    ['label' => lkn_hn_lang('Unknown'), 'value' => 'unknown'],
                ],
            ],
        ];

        $this->view->view('pages/conversations', $viewParams);
    }
}
