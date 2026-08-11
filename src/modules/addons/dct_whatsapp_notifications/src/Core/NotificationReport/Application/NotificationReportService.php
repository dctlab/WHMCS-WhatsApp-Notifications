<?php

namespace Dct\HookNotification\Core\NotificationReport\Application;

use DateTime;
use Dct\HookNotification\Core\Notification\Application\NotificationFactory;
use Dct\HookNotification\Core\Notification\Application\Services\NotificationSender;
use Dct\HookNotification\Core\NotificationReport\Domain\DeliveryStatus;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReport;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Dct\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Dct\HookNotification\Core\NotificationReport\Domain\WhatsAppConversation;
use Dct\HookNotification\Core\NotificationReport\Infrastructure\NotificationReportRepository;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Dct\HookNotification\Core\Shared\Infrastructure\Hooks;
use Dct\HookNotification\Core\Shared\Infrastructure\Result;
use Throwable;

final class NotificationReportService
{
    private NotificationReportRepository $notificationReportRepository;

    public function __construct()
    {
        $this->notificationReportRepository = new NotificationReportRepository();
    }

    /**
     * @param  integer $reportsPerPage
     * @param  integer $currentPage
     *
     * @return array{reports: NotificationReport[], totalReports: int}
     */
    public function getReportsForView(int $reportsPerPage, int $currentPage): array
    {
        return $this->searchReportsForView([], $reportsPerPage, $currentPage);
    }

    /**
     * Searches reports for the reports page, supporting filtering by client
     * (id, name, email or phone), invoice, domain, status, delivery status,
     * platform and date range.
     *
     * @param array<string, string> $filters
     *
     * @return array{reports: NotificationReport[], totalReports: int}
     */
    public function searchReportsForView(array $filters, int $reportsPerPage, int $currentPage): array
    {
        $offset = ($currentPage - 1) * $reportsPerPage;

        $repoResponse = $this->notificationReportRepository->search($filters, $offset, $reportsPerPage);

        $reports = array_map(
            fn ($row) => $this->hydrateReport($row),
            $repoResponse['reports']
        );

        return [
            'reports' => $reports,
            'totalReports' => $repoResponse['totalReports'],
        ];
    }

    private function hydrateReport(object $row): NotificationReport
    {
        $status = NotificationReportStatus::tryFrom($row->status);

        // Any message we attempted to send can be resent - not just failed ones.
        // A successfully delivered message might still need resending (e.g. the
        // client says they never got it, or wants it again).
        $canResend = $status !== null;

        return new NotificationReport(
            $row->id,
            $row->client_id ?? null,
            $row->category_id ?? null,
            $row->category ? NotificationReportCategory::tryFrom($row->category) : null,
            $status,
            $row->msg,
            $row->platform ? Platforms::tryFrom($row->platform) : null,
            $row->notification,
            $row->hook ? Hooks::tryFrom($row->hook) : null,
            new DateTime($row->created_at),
            $row->target,
            $row->wa_message_id ?? null,
            !empty($row->delivery_status) ? DeliveryStatus::tryFrom($row->delivery_status) : null,
            !empty($row->delivery_updated_at) ? new DateTime($row->delivery_updated_at) : null,
            $row->resent_from_report_id ?? null,
            $row->queue_id ?? null,
            $canResend,
            isset($row->billable) ? (bool) $row->billable : null,
            $row->wa_category ?? null,
            $row->message_preview ?? null,
        );
    }

    public function createReport(
        int $clientId,
        ?int $categoryId,
        ?NotificationReportCategory $reportCategory,
        NotificationReportStatus $reportStatus,
        ?string $reportMsg,
        ?Platforms $platform,
        string $notificationCode,
        ?Hooks $hook,
        ?int $queueId = null,
        ?string $target = null,
        ?string $waMessageId = null,
        ?array $whmcsHookParams = null,
        ?int $resentFromReportId = null,
        ?string $messagePreview = null,
    ) {
        $insertResult = $this->notificationReportRepository
            ->insertReport(
                $clientId,
                $categoryId,
                $reportCategory,
                $reportStatus,
                $reportMsg,
                $platform,
                $notificationCode,
                $hook,
                $queueId,
                $target,
                $waMessageId,
                $whmcsHookParams,
                $resentFromReportId,
                $messagePreview,
            );

        if (!$insertResult) {
            lkn_hn_log(
                'unable to create report',
                [
                    'clientId' => $clientId,
                    'categoryId' => $categoryId,
                    'reportCategory' => $reportCategory,
                    'reportStatus' => $reportStatus,
                    'reportMsg' => $reportMsg,
                    'platform' => $platform,
                    'notificationCode' => $notificationCode,
                    'hook' => $hook,
                    'queueId' => $queueId,
                    'target' => $target,
                ],
                [
                    'insertResult' => $insertResult,
                ]
            );
        }

        return $insertResult;
    }

    public function getReportsForCategory(
        NotificationReportCategory $category,
        int $categoryId
    ): array {
        $reports = [];

        $rawReports = $this->notificationReportRepository->getReportsForCategory(
            $category,
            $categoryId,
        );

        foreach ($rawReports as $report) {
            $reports[] = $this->hydrateReport($report);
        }

        return $reports;
    }

    /**
     * Deletes a single report row from the log. This does not "unsend" the
     * message or affect the client in any way - it only removes this record
     * from the Notification Reports page.
     */
    public function deleteReport(int $reportId): Result
    {
        $deleted = $this->notificationReportRepository->deleteReport($reportId);

        if (!$deleted) {
            return lkn_hn_result('error', msg: lkn_hn_lang('Report not found.'));
        }

        return lkn_hn_result('success', msg: lkn_hn_lang('Report deleted.'));
    }

    /**
     * Re-sends a previously failed/errored notification (or a message Meta marked as
     * `failed` on the status webhook), reusing the original WHMCS hook params that were
     * stored when the report was first created.
     *
     * This creates a brand new report row linked to the original one via
     * `resent_from_report_id`, it does not mutate/delete the original report so the
     * history is preserved.
     */
    public function resendReport(int $reportId): Result
    {
        try {
            $row = $this->notificationReportRepository->findById($reportId);

            if (!$row) {
                return lkn_hn_result('error', msg: lkn_hn_lang('Report not found.'));
            }

            $notification = NotificationFactory::getInstance()->makeByCode($row->notification);

            if (!$notification) {
                return lkn_hn_result(
                    'error',
                    msg: lkn_hn_lang('This notification is not registered/enabled anymore, it cannot be resent.')
                );
            }

            $whmcsHookParams = !empty($row->whmcs_hook_params)
                ? json_decode($row->whmcs_hook_params, true)
                : [];

            $result = NotificationSender::getInstance()->send(
                $notification,
                $whmcsHookParams,
                $row->queue_id ?? null,
            );

            $isSuccess = property_exists($result, 'status')
                && $result->status === NotificationReportStatus::SENT;

            // Link the newest report (just created by ->send()) back to the original one.
            $latestReport = $this->notificationReportRepository->search(
                ['notification' => $row->notification],
                0,
                1,
            )['reports'][0] ?? null;

            if ($latestReport) {
                $this->notificationReportRepository->query
                    ->table('mod_dct_hook_notification_reports')
                    ->where('id', $latestReport->id)
                    ->update([
                        'resent_from_report_id' => $reportId,
                        'status' => NotificationReportStatus::RESENT->value,
                    ]);
            }

            return lkn_hn_result(
                $isSuccess ? 'success' : 'error',
                msg: $isSuccess
                    ? lkn_hn_lang('The message was resent.')
                    : lkn_hn_lang('The message could not be resent: [1]', [$result->msg ?? ''])
            );
        } catch (Throwable $th) {
            lkn_hn_log('resendReport error', ['reportId' => $reportId], ['exception' => $th->__toString()]);

            return lkn_hn_result(
                'error',
                msg: lkn_hn_lang('Internal error while resending the message.'),
                errors: ['exception' => $th->__toString()]
            );
        }
    }

    /**
     * Called by the Meta WhatsApp status webhook to update the delivery status
     * (sent/delivered/read/failed) of a message previously sent by this module.
     */
    public function updateDeliveryStatusFromWebhook(
        string $waMessageId,
        string $status,
        ?DateTime $eventAt = null,
        ?bool $billable = null,
        ?string $waCategory = null,
    ): void {
        $this->notificationReportRepository->updateDeliveryStatusByMessageId(
            $waMessageId,
            $status,
            $eventAt,
            $billable,
            $waCategory,
        );
    }

    /**
     * @param  string      $conversationId
     * @param  ?string     $category
     * @param  ?string     $pricingModel
     * @param  ?bool       $billable      Meta's `pricing.billable` flag, when present on the payload.
     * @param  ?string     $originType
     * @param  DateTime    $eventAt
     * @param  ?DateTime   $expirationAt
     * @param  ?string     $waMessageId   Used to resolve the WHMCS client id and target phone number
     *                                    tied to this conversation (Meta's payload does not carry them).
     */
    public function upsertConversationFromWebhook(
        string $conversationId,
        ?string $category,
        ?string $pricingModel,
        ?bool $billable,
        ?string $originType,
        DateTime $eventAt,
        ?DateTime $expirationAt,
        ?string $waMessageId = null,
    ): void {
        $clientId    = null;
        $phoneNumber = null;

        if ($waMessageId) {
            $context = $this->notificationReportRepository->getReportContextByMessageId($waMessageId);

            $clientId    = $context['client_id'] ?? null;
            $phoneNumber = $context['target'] ?? null;
        }

        $this->notificationReportRepository->upsertConversation(
            $conversationId,
            $category,
            $pricingModel,
            $billable,
            $originType,
            $eventAt,
            $expirationAt,
            $clientId,
            $phoneNumber,
        );
    }

    /**
     * Records an inbound (customer-initiated) WhatsApp message so it's reflected
     * in the WhatsApp Conversations page (bumping an already-open conversation,
     * or opening a placeholder one that gets adopted once Meta reports the real
     * conversation id via a business reply's status webhook).
     *
     * @since 4.5.5
     */
    public function recordInboundMessage(
        string $phoneNumber,
        DateTime $eventAt,
        ?string $messagePreview = null,
        ?string $waMessageId = null,
    ): void {
        $clientId = $this->notificationReportRepository->guessClientIdByPhone($phoneNumber);

        $this->notificationReportRepository->recordInboundMessage($phoneNumber, $eventAt, $clientId, $messagePreview);
        $this->notificationReportRepository->insertMessage(
            $phoneNumber,
            'inbound',
            'text',
            $messagePreview,
            $waMessageId,
            $eventAt,
            $clientId,
        );
    }

    /**
     * Updates the delivery status (sent/delivered/read/failed) of a chat message
     * previously recorded via sendChatMessage(), so the thread reflects ticks.
     */
    public function updateChatMessageStatus(string $waMessageId, string $status): void
    {
        $this->notificationReportRepository->updateMessageStatusByWaId($waMessageId, $status);
    }

    /**
     * Searches/paginates WhatsApp conversations for the "WhatsApp Conversations" page.
     *
     * @param array<string, string> $filters
     *
     * @return array{conversations: WhatsAppConversation[], totalConversations: int}
     */
    public function searchConversationsForView(array $filters, int $perPage, int $currentPage): array
    {
        $offset = ($currentPage - 1) * $perPage;

        $repoResponse = $this->notificationReportRepository->searchConversations($filters, $offset, $perPage);

        $conversations = array_map(
            fn ($row) => new WhatsAppConversation(
                $row->id,
                $row->conversation_id,
                $row->client_id ?? null,
                $row->phone_number ?? null,
                $row->category ?? null,
                $row->pricing_model ?? null,
                isset($row->billable) ? (bool) $row->billable : null,
                $row->origin_type ?? null,
                (int) $row->message_count,
                !empty($row->first_message_at) ? new DateTime($row->first_message_at) : null,
                !empty($row->last_message_at) ? new DateTime($row->last_message_at) : null,
                !empty($row->expiration_at) ? new DateTime($row->expiration_at) : null,
                $row->last_message_preview ?? null,
                $row->last_message_direction ?? null,
            ),
            $repoResponse['conversations']
        );

        return [
            'conversations' => $conversations,
            'totalConversations' => $repoResponse['totalConversations'],
        ];
    }

    public function getStatistics(): array
    {
        return [
            'last_our' => [
                'notifications_sent' => $this->notificationReportRepository->getReportsForLastHour(),
                'failed_sendings' => $this->notificationReportRepository->getFailedReports(),
                'top_notifications' => $this->notificationReportRepository->getTopNotificationsForLastHour(),
            ],
        ];
    }

    /**
     * Assembles delivery, conversation, billing, and performance data for a
     * given date range - originally built for the Dashboard (Phase 2), now
     * also reused by the Analytics page (Phase 5) since it is a strict
     * superset of what getAnalytics() alone provides. Renamed from
     * getDashboardData() to reflect that it is no longer specific to one
     * page - same behavior, same queries, just a clearer name and one
     * additional key exposed (see below).
     *
     * Deliberately reuses getAnalytics() (already powers Message Analytics)
     * rather than duplicating its delivery/conversation/billing queries -
     * only notification_performance and daily_activity are genuinely new
     * queries (added in Phase 2), since no existing method already provided
     * a per-notification-type breakdown or a daily time series
     * (getReportsForLastHour()/getTopNotificationsForLastHour() are
     * hardcoded to a 1-hour window, too narrow for either page's actual
     * needs).
     *
     * message_billable added in Phase 5: this data was already being
     * computed internally via the getAnalytics() call on the line below,
     * just not previously included in this method's return array - adding
     * it here is exposing already-computed data, not a new query.
     *
     * @return array{
     *     delivery: array<string, int>,
     *     conversations: array,
     *     approximate_charges: array,
     *     message_billable: array,
     *     notification_performance: array,
     *     recent_activity: array<int, object>,
     * }
     */
    public function getPerformanceOverview(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $from = $dateFrom ? new DateTime($dateFrom) : (new DateTime())->modify('-6 days');
        $to   = $dateTo ? new DateTime($dateTo) : new DateTime();

        $analytics = $this->getAnalytics($from->format('Y-m-d'), $to->format('Y-m-d'));

        $dailyActivity = $this->notificationReportRepository->getDailyMessageActivity($from, $to);

        return [
            'delivery' => $analytics['delivery'],
            'conversations' => $analytics['conversations'],
            'message_billable' => $analytics['message_billable'],
            'approximate_charges' => $analytics['approximate_charges'],
            'notification_performance' => $this->notificationReportRepository->getNotificationPerformance($from, $to),
            'recent_activity' => $this->notificationReportRepository->getRecentReports(10),
            'daily_activity' => $dailyActivity,
            'daily_activity_has_data' => array_sum($dailyActivity) > 0,
        ];
    }

    /**
     * @return array{
     *     delivery: array<string, int>,
     *     conversations: array{total_conversations: int, by_category: array<string, int>, billable: int, free_or_unbilled: int, unknown: int},
     *     top_notifications: array,
     * }
     */
    public function getAnalytics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $from = $dateFrom ? new DateTime($dateFrom) : null;
        $to   = $dateTo ? new DateTime($dateTo) : null;

        return [
            'delivery' => $this->notificationReportRepository->getDeliveryStatusCounts($from, $to),
            'conversations' => $this->notificationReportRepository->getConversationAnalytics($from, $to),
            'top_notifications' => $this->notificationReportRepository->getTopNotificationsForLastHour(),
            'message_billable' => $this->notificationReportRepository->getMessageBillableCounts($from, $to),
            'approximate_charges' => $this->getApproximateCharges($from, $to),
        ];
    }

    /**
     * Estimates approximate charges from billable *conversations* (Meta's
     * actual billing unit) times admin-configured per-category rates.
     *
     * These are estimates only, not an exact invoice: Meta's real rates vary
     * by country/market and change periodically, and this module has no way
     * to know the actual rate applied to a given conversation. Categories
     * with no rate configured are excluded from the total but still listed.
     *
     * @return array{
     *     currency: string,
     *     total: float,
     *     by_category: array<string, array{count: int, rate: ?float, subtotal: ?float}>,
     * }
     */
    private function getApproximateCharges(?DateTime $from, ?DateTime $to): array
    {
        $counts = $this->notificationReportRepository->getBillableConversationCountsByCategory($from, $to);

        $rates = [
            'marketing' => lkn_hn_config(Settings::WP_RATE_MARKETING),
            'utility' => lkn_hn_config(Settings::WP_RATE_UTILITY),
            'authentication' => lkn_hn_config(Settings::WP_RATE_AUTHENTICATION),
            'authentication_international' => lkn_hn_config(Settings::WP_RATE_AUTHENTICATION_INTL),
            'service' => lkn_hn_config(Settings::WP_RATE_SERVICE),
        ];

        $currency = lkn_hn_config(Settings::WP_CHARGE_CURRENCY) ?: '';
        $total    = 0.0;
        $byCategory = [];

        foreach ($counts as $category => $count) {
            $rate = isset($rates[$category]) && $rates[$category] !== null && $rates[$category] !== ''
                ? (float) $rates[$category]
                : null;

            $subtotal = $rate !== null ? $rate * $count : null;

            if ($subtotal !== null) {
                $total += $subtotal;
            }

            $byCategory[$category] = [
                'count' => $count,
                'rate' => $rate,
                'subtotal' => $subtotal,
            ];
        }

        return [
            'currency' => $currency,
            'total' => $total,
            'by_category' => $byCategory,
        ];
    }

    /**
     * Contact list for the live WhatsApp Conversations chat view.
     *
     * @return array<int, array{
     *     phone_number: string,
     *     client_id: ?int,
     *     client_name: ?string,
     *     last_message_preview: ?string,
     *     last_message_direction: ?string,
     *     last_message_at: ?DateTime,
     * }>
     */
    public function getChatConversationsList(): array
    {
        return array_map(
            fn ($row) => [
                'phone_number' => $row->phone_number,
                'client_id' => $row->client_id ?? null,
                'client_name' => $row->client_name ?? null,
                'last_message_preview' => $row->last_message_preview ?? null,
                'last_message_direction' => $row->last_message_direction ?? null,
                'last_message_at' => !empty($row->last_message_at) ? new DateTime($row->last_message_at) : null,
            ],
            $this->notificationReportRepository->getChatConversationsList()
        );
    }

    /**
     * @return array<int, array{direction: string, body: ?string, type: ?string, status: ?string, sent_at: DateTime}>
     */
    public function getChatThread(string $phoneNumber): array
    {
        return $this->formatMessagesForView(
            $this->notificationReportRepository->getMessagesForPhone($phoneNumber)
        );
    }

    /**
     * @return array<int, array{direction: string, body: ?string, type: ?string, status: ?string, sent_at: DateTime}>
     */
    public function getNewChatMessages(string $phoneNumber, DateTime $since): array
    {
        return $this->formatMessagesForView(
            $this->notificationReportRepository->getNewMessagesForPhone($phoneNumber, $since)
        );
    }

    /**
     * @param object[] $rows
     *
     * @return array<int, array{direction: string, body: ?string, type: ?string, status: ?string, sent_at: DateTime}>
     */
    private function formatMessagesForView(array $rows): array
    {
        return array_map(
            fn ($row) => [
                'direction' => $row->direction,
                'body' => $row->body,
                'type' => $row->message_type,
                'status' => $row->status,
                'sent_at' => new DateTime($row->sent_at),
            ],
            $rows
        );
    }

    /**
     * Sends a free-form text reply to a contact (only works within Meta's
     * 24-hour customer service window) and records it in the chat history.
     */
    public function sendChatMessage(string $phoneNumber, string $text): Result
    {
        $settings = PlatformSettingsFactory::makeMetaWhatsAppSettings();

        if (!$settings->enabled) {
            return lkn_hn_result('error', errors: ['message' => lkn_hn_lang('The Meta WhatsApp platform is disabled.')]);
        }

        $client = (new PlatformApiClientFactory())->makeMetaWhatsAppClient($settings);

        if (!$client->areSettingsFilled()) {
            return lkn_hn_result('error', errors: ['message' => lkn_hn_lang('Meta WhatsApp settings are incomplete.')]);
        }

        $apiResponse = $client->sendTextMessage($phoneNumber, $text);

        if (
            !isset($apiResponse->body['messages'][0]['id'])
        ) {
            $errorMessage = $apiResponse->body['error']['message'] ?? lkn_hn_lang('Unknown error sending message.');

            return lkn_hn_result('error', errors: ['message' => $errorMessage]);
        }

        $waMessageId = $apiResponse->body['messages'][0]['id'];
        $now         = new DateTime();
        $clientId    = $this->notificationReportRepository->guessClientIdByPhone($phoneNumber);

        $this->notificationReportRepository->insertMessage(
            $phoneNumber,
            'outbound',
            'text',
            $text,
            $waMessageId,
            $now,
            $clientId,
            'sent',
        );

        $this->notificationReportRepository->touchConversationForOutboundChat(
            $phoneNumber,
            $now,
            $clientId,
            mb_substr($text, 0, 200),
        );

        return lkn_hn_result('success', data: ['wa_message_id' => $waMessageId]);
    }
}
