<?php

namespace Lkn\HookNotification\Core\NotificationReport\Http\Controllers;

use Lkn\HookNotification\Core\NotificationReport\Application\NotificationReportService;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Lkn\HookNotification\Core\NotificationReport\Domain\DeliveryStatus;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Lkn\HookNotification\Core\Shared\Infrastructure\View\View;

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

    public function viewAnalytics(array $request): void
    {
        $dateFrom = $request['f_date_from'] ?? null;
        $dateTo   = $request['f_date_to'] ?? null;

        $analytics = $this->notificationReportService->getAnalytics($dateFrom, $dateTo);

        $delivery = $analytics['delivery'];
        $totalSentAttempts = array_sum($delivery);

        $viewParams = [
            'delivery' => $delivery,
            'total_sent_attempts' => $totalSentAttempts,
            'conversations' => $analytics['conversations'],
            'top_notifications' => $analytics['top_notifications'],
            'message_billable' => $analytics['message_billable'],
            'approximate_charges' => $analytics['approximate_charges'],
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
