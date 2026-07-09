<?php

namespace Lkn\HookNotification\Core\NotificationReport\Infrastructure;

use DateTime;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportCategory;
use Lkn\HookNotification\Core\NotificationReport\Domain\NotificationReportStatus;
use Lkn\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Lkn\HookNotification\Core\Shared\Infrastructure\Hooks;
use Lkn\HookNotification\Core\Shared\Infrastructure\Repository\BaseRepository;
use WHMCS\Database\Capsule;

final class NotificationReportRepository extends BaseRepository
{
    private const TABLE = 'mod_lkn_hook_notification_reports';

    public function paginate(int $offset, int $limit)
    {
        return $this->search([], $offset, $limit);
    }

    /**
     * Searches/paginates reports.
     *
     * @param array{
     *     client?: string,
     *     invoice?: string,
     *     domain?: string,
     *     status?: string,
     *     delivery_status?: string,
     *     platform?: string,
     *     notification?: string,
     *     date_from?: string,
     *     date_to?: string,
     * } $filters
     */
    public function search(array $filters, int $offset, int $limit): array
    {
        $query      = $this->query->table(self::TABLE);
        $countQuery = $this->query->table(self::TABLE);

        $this->applyFilters($query, $filters);
        $this->applyFilters($countQuery, $filters);

        $reports = $query
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalReports = $countQuery->count();

        return [
            'reports' => $reports->toArray(),
            'totalReports' => $totalReports,
        ];
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<string, string>              $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['client'])) {
            $clientTerm = trim($filters['client']);

            if (ctype_digit($clientTerm)) {
                $query->where('client_id', intval($clientTerm));
            } else {
                $matchingClientIds = Capsule::table('tblclients')
                    ->where('firstname', 'like', "%{$clientTerm}%")
                    ->orWhere('lastname', 'like', "%{$clientTerm}%")
                    ->orWhere('companyname', 'like', "%{$clientTerm}%")
                    ->orWhere('email', 'like', "%{$clientTerm}%")
                    ->pluck('id')
                    ->toArray();

                $query->where(function ($q) use ($clientTerm, $matchingClientIds) {
                    $q->whereIn('client_id', empty($matchingClientIds) ? [-1] : $matchingClientIds)
                        ->orWhere('target', 'like', "%{$clientTerm}%");
                });
            }
        }

        if (!empty($filters['invoice'])) {
            $invoiceTerm = trim($filters['invoice']);

            $query->where('category', NotificationReportCategory::INVOICE->value);

            if (ctype_digit($invoiceTerm)) {
                $query->where('category_id', intval($invoiceTerm));
            }
        }

        if (!empty($filters['domain'])) {
            $domainTerm = trim($filters['domain']);

            $matchingDomainIds = Capsule::table('tbldomains')
                ->where('domain', 'like', "%{$domainTerm}%")
                ->pluck('id')
                ->toArray();

            $query->where('category', NotificationReportCategory::DOMAIN->value)
                ->whereIn('category_id', empty($matchingDomainIds) ? [-1] : $matchingDomainIds);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['delivery_status'])) {
            $query->where('delivery_status', $filters['delivery_status']);
        }

        if (isset($filters['billable']) && $filters['billable'] !== '') {
            if ($filters['billable'] === 'unknown') {
                $query->whereNull('billable');
            } else {
                $query->where('billable', $filters['billable'] === '1' ? 1 : 0);
            }
        }

        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (!empty($filters['notification'])) {
            $query->where('notification', $filters['notification']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', (new DateTime($filters['date_from']))->format('Y-m-d 00:00:00'));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', (new DateTime($filters['date_to']))->format('Y-m-d 23:59:59'));
        }
    }

    public function findById(int $id): ?object
    {
        return $this->query->table(self::TABLE)->where('id', $id)->first();
    }

    /**
     * Deletes a single report row. This only removes the log entry itself -
     * it has no effect on the message that was already sent to the client.
     *
     * @return bool true if a row was deleted.
     */
    public function deleteReport(int $id): bool
    {
        return $this->query->table(self::TABLE)->where('id', $id)->delete() > 0;
    }

    public function insertReport(
        int $clientId,
        ?int $categoryId,
        ?NotificationReportCategory $reportCategory,
        NotificationReportStatus $reportStatus,
        ?string $reportMsg,
        ?Platforms $platform,
        string $notificationCode,
        ?Hooks $hook,
        ?int $queueId,
        ?string $target,
        ?string $waMessageId = null,
        ?array $whmcsHookParams = null,
        ?int $resentFromReportId = null,
        ?string $messagePreview = null,
    ): int {
        return $this->query->table(self::TABLE)
            ->insert([
                'client_id' => $clientId,
                'category_id' => $categoryId,
                'category' => $reportCategory?->value,
                'status' => $reportStatus->value,
                'msg' => $reportMsg,
                'message_preview' => $messagePreview,
                'whmcs_hook_params' => $whmcsHookParams !== null
                    ? (lkn_hn_safe_json_encode($whmcsHookParams) ?: null)
                    : null,
                'platform' => $platform?->value,
                'channel' => null,
                'notification' => $notificationCode,
                'hook' => $hook?->value,
                'queue_id' => $queueId,
                'resent_from_report_id' => $resentFromReportId,
                'target' => $target,
                'wa_message_id' => $waMessageId,
                'delivery_status' => $reportStatus === NotificationReportStatus::SENT
                    ? NotificationReportStatus::SENT->value
                    : null,
                'delivery_updated_at' => $reportStatus === NotificationReportStatus::SENT
                    ? (new DateTime())->format('Y-m-d H:i:s')
                    : null,
            ]);
    }

    /**
     * Updates the delivery status of a previously sent message, matched by the
     * WhatsApp message id received from the Meta status webhook. Also records
     * the message-level billable flag/category when the webhook included
     * pricing info (only some status events do - see MetaWebhookController).
     *
     * @return int number of rows updated (0 or 1, since wa_message_id should be unique per send).
     */
    public function updateDeliveryStatusByMessageId(
        string $waMessageId,
        string $deliveryStatus,
        ?DateTime $updatedAt = null,
        ?bool $billable = null,
        ?string $waCategory = null,
    ): int {
        $update = [
            'delivery_status' => $deliveryStatus,
            'delivery_updated_at' => ($updatedAt ?? new DateTime())->format('Y-m-d H:i:s'),
        ];

        if ($billable !== null) {
            $update['billable'] = $billable ? 1 : 0;
        }

        if ($waCategory !== null) {
            $update['wa_category'] = $waCategory;
        }

        return $this->query->table(self::TABLE)
            ->where('wa_message_id', $waMessageId)
            ->update($update);
    }

    public function getReportsForCategory(
        NotificationReportCategory $category,
        int $categoryId
    ): array {
        return $this->query
            ->table(self::TABLE)
            ->orderBy('created_at', 'desc')
            ->where('category', $category->value)
            ->where('category_id', $categoryId)
            ->get()
            ->toArray();
    }

    public function getReportsForLastHour()
    {
        $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        return $this->query
            ->table(self::TABLE)
            ->where('created_at', '>=', $oneHourAgo)
            ->count();
    }

    public function getFailedReports()
    {
        $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        return $this->query
            ->table(self::TABLE)
            ->where('created_at', '>=', $oneHourAgo)
            ->where('status', '!=', NotificationReportStatus::SENT->value)
            ->count();
    }

    public function getTopNotificationsForLastHour()
    {
        $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s');

        return $this->query
            ->table(self::TABLE)
            ->select(
                'notification',
                $this->query::table(self::TABLE)->raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $oneHourAgo)
            ->groupBy('notification')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * @param DateTime|null $from
     * @param DateTime|null $to
     *
     * @return array<string, int> counts keyed by delivery status value (sent, delivered, read, failed),
     *                             plus 'not_delivered_yet' for messages sent but with no webhook update.
     */
    public function getDeliveryStatusCounts(?DateTime $from = null, ?DateTime $to = null): array
    {
        $query = $this->query->table(self::TABLE)
            ->where('status', NotificationReportStatus::SENT->value);

        if ($from) {
            $query->where('created_at', '>=', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->where('created_at', '<=', $to->format('Y-m-d H:i:s'));
        }

        $rows = (clone $query)
            ->select(
                'delivery_status',
                $this->query::table(self::TABLE)->raw('COUNT(*) as total')
            )
            ->groupBy('delivery_status')
            ->get()
            ->toArray();

        $counts = [
            'sent' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0,
        ];

        foreach ($rows as $row) {
            $key = $row->delivery_status ?? 'sent';

            if (!isset($counts[$key])) {
                $counts[$key] = 0;
            }

            $counts[$key] += (int) $row->total;
        }

        return $counts;
    }

    /**
     * Message-level (not conversation-level) billable breakdown, matching
     * the "Billable" / "Free Messages Delivered" / "Paid Messages Delivered"
     * stats on the Message Analytics page.
     *
     * @return array{billable_total: int, free_delivered: int, paid_delivered: int}
     */
    public function getMessageBillableCounts(?DateTime $from = null, ?DateTime $to = null): array
    {
        $query = $this->query->table(self::TABLE)
            ->where('status', NotificationReportStatus::SENT->value);

        if ($from) {
            $query->where('created_at', '>=', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->where('created_at', '<=', $to->format('Y-m-d H:i:s'));
        }

        return [
            'billable_total' => (clone $query)->where('billable', 1)->count(),
            'free_delivered' => (clone $query)->where('billable', 0)->where('delivery_status', 'delivered')->count(),
            'paid_delivered' => (clone $query)->where('billable', 1)->where('delivery_status', 'delivered')->count(),
        ];
    }

    /**
     * Billable *conversation* counts by category (Meta bills per conversation,
     * not per message), used to estimate approximate charges.
     *
     * @return array<string, int> category => billable conversation count
     */
    public function getBillableConversationCountsByCategory(?DateTime $from = null, ?DateTime $to = null): array
    {
        $query = $this->query->table('mod_lkn_hook_notification_conversations')
            ->where('billable', 1);

        if ($from) {
            $query->where('first_message_at', '>=', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->where('first_message_at', '<=', $to->format('Y-m-d H:i:s'));
        }

        $rows = $query
            ->select('category', $this->query::table('mod_lkn_hook_notification_conversations')->raw('COUNT(*) as total'))
            ->groupBy('category')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[$row->category ?? 'unknown'] = (int) $row->total;
        }

        return $counts;
    }

    public function getFailedReportsForResend(int $limit = 200): array
    {
        return $this->query->table(self::TABLE)
            ->whereIn('status', [
                NotificationReportStatus::NOT_SENT->value,
                NotificationReportStatus::ERROR->value,
            ])
            ->orWhere('delivery_status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Looks up the client id and target phone number of a previously sent message,
     * used to enrich conversation analytics rows coming from the status webhook
     * (Meta's status payload does not carry the WHMCS client id).
     *
     * @return array{client_id: int|null, target: string|null}|null
     */
    public function getReportContextByMessageId(string $waMessageId): ?array
    {
        $row = $this->query->table(self::TABLE)
            ->where('wa_message_id', $waMessageId)
            ->first(['client_id', 'target']);

        if (!$row) {
            return null;
        }

        return [
            'client_id' => $row->client_id ?? null,
            'target' => lkn_hn_normalize_phone_digits($row->target ?? null),
        ];
    }

    /**
     * Best-effort lookup of the WHMCS client id owning a given phone number,
     * based on the target phone number of previously sent WhatsApp messages
     * (more reliable than matching tblclients.phonenumber directly, since it
     * reuses the exact number the module already used to reach that client).
     *
     * @since 4.5.5
     */
    public function guessClientIdByPhone(string $phone): ?int
    {
        $digits = lkn_hn_normalize_phone_digits($phone);
        $suffix = $digits !== null ? substr($digits, -8) : null;

        if (!$suffix || strlen($suffix) < 8) {
            return null;
        }

        $row = $this->query->table(self::TABLE)
            ->whereNotNull('client_id')
            ->where('target', 'like', "%{$suffix}")
            ->orderBy('created_at', 'desc')
            ->first(['client_id']);

        return $row->client_id ?? null;
    }

    /**
     * Records an inbound (customer-initiated) message for conversation tracking.
     *
     * Meta's incoming-message webhook payload does not carry a conversation id
     * or pricing/category info (those only appear on the status webhook of a
     * business reply). So an inbound message either bumps the currently open
     * conversation for that phone number, or opens a new placeholder
     * conversation (synthetic id `phone:{number}`) that gets "adopted" by the
     * real Meta conversation id once the business sends a reply and Meta's
     * status webhook reports it (see upsertConversation()).
     *
     * @since 4.5.5
     */
    public function recordInboundMessage(
        string $phoneNumber,
        DateTime $eventAt,
        ?int $clientId,
        ?string $messagePreview = null,
    ): void {
        $phoneNumber = lkn_hn_normalize_phone_digits($phoneNumber);

        if (!$phoneNumber) {
            return;
        }

        $existing = $this->query->table('mod_lkn_hook_notification_conversations')
            ->where('phone_number', $phoneNumber)
            ->where(function ($q) use ($eventAt) {
                $q->whereNull('expiration_at')
                    ->orWhere('expiration_at', '>=', $eventAt->format('Y-m-d H:i:s'));
            })
            ->orderBy('last_message_at', 'desc')
            ->first();

        if ($existing) {
            $this->query->table('mod_lkn_hook_notification_conversations')
                ->where('id', $existing->id)
                ->update([
                    'message_count' => $existing->message_count + 1,
                    'last_message_at' => $eventAt->format('Y-m-d H:i:s'),
                    'last_message_preview' => $messagePreview ?? $existing->last_message_preview ?? null,
                    'last_message_direction' => 'inbound',
                    'client_id' => $existing->client_id ?? $clientId,
                ]);

            return;
        }

        $this->query->table('mod_lkn_hook_notification_conversations')
            ->insert([
                'conversation_id' => 'phone:' . $phoneNumber,
                'category' => null,
                'pricing_model' => null,
                'billable' => null,
                'origin_type' => 'customer_initiated',
                'client_id' => $clientId,
                'phone_number' => $phoneNumber,
                'message_count' => 1,
                'first_message_at' => $eventAt->format('Y-m-d H:i:s'),
                'last_message_at' => $eventAt->format('Y-m-d H:i:s'),
                'last_message_preview' => $messagePreview,
                'last_message_direction' => 'inbound',
                'expiration_at' => null,
            ]);
    }

    /**
     * Bumps (or opens) the conversation for a free-form outbound chat reply,
     * mirroring recordInboundMessage() but tagged as an outbound message.
     *
     * @since 4.5.7
     */
    public function touchConversationForOutboundChat(
        string $phoneNumber,
        DateTime $eventAt,
        ?int $clientId,
        ?string $messagePreview = null,
    ): void {
        $phoneNumber = lkn_hn_normalize_phone_digits($phoneNumber);

        if (!$phoneNumber) {
            return;
        }

        $existing = $this->query->table('mod_lkn_hook_notification_conversations')
            ->where('phone_number', $phoneNumber)
            ->where(function ($q) use ($eventAt) {
                $q->whereNull('expiration_at')
                    ->orWhere('expiration_at', '>=', $eventAt->format('Y-m-d H:i:s'));
            })
            ->orderBy('last_message_at', 'desc')
            ->first();

        if ($existing) {
            $this->query->table('mod_lkn_hook_notification_conversations')
                ->where('id', $existing->id)
                ->update([
                    'message_count' => $existing->message_count + 1,
                    'last_message_at' => $eventAt->format('Y-m-d H:i:s'),
                    'last_message_preview' => $messagePreview ?? $existing->last_message_preview ?? null,
                    'last_message_direction' => 'outbound',
                    'client_id' => $existing->client_id ?? $clientId,
                ]);

            return;
        }

        $this->query->table('mod_lkn_hook_notification_conversations')
            ->insert([
                'conversation_id' => 'phone:' . $phoneNumber,
                'category' => null,
                'pricing_model' => null,
                'billable' => null,
                'origin_type' => 'business_initiated',
                'client_id' => $clientId,
                'phone_number' => $phoneNumber,
                'message_count' => 1,
                'first_message_at' => $eventAt->format('Y-m-d H:i:s'),
                'last_message_at' => $eventAt->format('Y-m-d H:i:s'),
                'last_message_preview' => $messagePreview,
                'last_message_direction' => 'outbound',
                'expiration_at' => null,
            ]);
    }

    /**
     * Stores a single message (inbound or outbound) in the full chat history.
     * Deduplicates by wa_message_id when one is provided (webhook retries).
     *
     * @since 4.5.7
     */
    public function insertMessage(
        string $phoneNumber,
        string $direction,
        ?string $messageType,
        ?string $body,
        ?string $waMessageId,
        DateTime $sentAt,
        ?int $clientId,
        ?string $status = null,
    ): void {
        $phoneNumber = lkn_hn_normalize_phone_digits($phoneNumber);

        if (!$phoneNumber) {
            return;
        }

        if ($waMessageId) {
            $exists = $this->query->table('mod_lkn_hook_notification_messages')
                ->where('wa_message_id', $waMessageId)
                ->exists();

            if ($exists) {
                return;
            }
        }

        $this->query->table('mod_lkn_hook_notification_messages')
            ->insert([
                'client_id' => $clientId,
                'phone_number' => $phoneNumber,
                'wa_message_id' => $waMessageId,
                'direction' => $direction,
                'message_type' => $messageType,
                'body' => $body,
                'status' => $status,
                'sent_at' => $sentAt->format('Y-m-d H:i:s'),
            ]);
    }

    public function updateMessageStatusByWaId(string $waMessageId, string $status): void
    {
        $this->query->table('mod_lkn_hook_notification_messages')
            ->where('wa_message_id', $waMessageId)
            ->update(['status' => $status]);
    }

    /**
     * @return object[] Ordered oldest -> newest.
     */
    public function getMessagesForPhone(string $phoneNumber, int $limit = 200): array
    {
        $phoneNumber = lkn_hn_normalize_phone_digits($phoneNumber);

        if (!$phoneNumber) {
            return [];
        }

        return $this->query->table('mod_lkn_hook_notification_messages')
            ->where('phone_number', $phoneNumber)
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    /**
     * @return object[] Ordered oldest -> newest.
     */
    public function getNewMessagesForPhone(string $phoneNumber, DateTime $since): array
    {
        $phoneNumber = lkn_hn_normalize_phone_digits($phoneNumber);

        if (!$phoneNumber) {
            return [];
        }

        return $this->query->table('mod_lkn_hook_notification_messages')
            ->where('phone_number', $phoneNumber)
            ->where('sent_at', '>', $since->format('Y-m-d H:i:s'))
            ->orderBy('sent_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Contact list for the live chat view's left panel: one row per phone
     * number/conversation, most recently active first, with the WHMCS
     * client's name resolved when known.
     *
     * @return object[]
     */
    public function getChatConversationsList(int $limit = 100): array
    {
        $conversations = $this->query->table('mod_lkn_hook_notification_conversations')
            ->whereNotNull('phone_number')
            ->orderBy('last_message_at', 'desc')
            ->limit($limit)
            ->get();

        $clientIds = $conversations->pluck('client_id')->filter()->unique()->values()->toArray();

        $clientNames = [];

        if (!empty($clientIds)) {
            $clientNames = Capsule::table('tblclients')
                ->whereIn('id', $clientIds)
                ->get(['id', 'firstname', 'lastname'])
                ->keyBy('id')
                ->map(fn ($c) => trim("{$c->firstname} {$c->lastname}"))
                ->toArray();
        }

        return $conversations->map(function ($row) use ($clientNames) {
            $row->client_name = $clientNames[$row->client_id] ?? null;

            return $row;
        })->values()->toArray();
    }

    /**
     * Upserts a WhatsApp conversation (billing window) coming from the Meta status webhook.
     *
     * @param bool|null $billable Whether Meta reported this conversation as billable.
     *                            Null when Meta did not include a `pricing.billable`
     *                            flag on the payload (kept as "unknown" rather than
     *                            assumed free, since Meta phased this field out on
     *                            newer API versions in favor of per-category pricing).
     */
    public function upsertConversation(
        string $conversationId,
        ?string $category,
        ?string $pricingModel,
        ?bool $billable,
        ?string $originType,
        DateTime $eventAt,
        ?DateTime $expirationAt,
        ?int $clientId = null,
        ?string $phoneNumber = null,
    ): void {
        $phoneNumber = lkn_hn_normalize_phone_digits($phoneNumber);

        $existing = $this->query->table('mod_lkn_hook_notification_conversations')
            ->where('conversation_id', $conversationId)
            ->first();

        if (!$existing && $phoneNumber) {
            // Adopt a placeholder row opened by an earlier inbound message from
            // this same phone number, now that Meta gave us the real conversation
            // id, instead of creating a duplicate conversation.
            $synthetic = $this->query->table('mod_lkn_hook_notification_conversations')
                ->where('conversation_id', 'phone:' . $phoneNumber)
                ->first();

            if ($synthetic) {
                $this->query->table('mod_lkn_hook_notification_conversations')
                    ->where('id', $synthetic->id)
                    ->update(['conversation_id' => $conversationId]);

                $existing = $synthetic;
            }
        }

        if ($existing) {
            $this->query->table('mod_lkn_hook_notification_conversations')
                ->where('id', $existing->id)
                ->update([
                    'category' => $category ?? $existing->category,
                    'pricing_model' => $pricingModel ?? $existing->pricing_model,
                    'billable' => $billable !== null ? ($billable ? 1 : 0) : $existing->billable,
                    'origin_type' => $originType ?? $existing->origin_type,
                    'client_id' => $clientId ?? $existing->client_id,
                    'phone_number' => $phoneNumber ?? $existing->phone_number,
                    'message_count' => $existing->message_count + 1,
                    'last_message_at' => $eventAt->format('Y-m-d H:i:s'),
                    'last_message_direction' => 'outbound',
                    'expiration_at' => $expirationAt ? $expirationAt->format('Y-m-d H:i:s') : $existing->expiration_at,
                ]);

            return;
        }

        $this->query->table('mod_lkn_hook_notification_conversations')
            ->insert([
                'conversation_id' => $conversationId,
                'category' => $category,
                'pricing_model' => $pricingModel,
                'billable' => $billable !== null ? ($billable ? 1 : 0) : null,
                'origin_type' => $originType,
                'client_id' => $clientId,
                'phone_number' => $phoneNumber,
                'message_count' => 1,
                'first_message_at' => $eventAt->format('Y-m-d H:i:s'),
                'last_message_at' => $eventAt->format('Y-m-d H:i:s'),
                'last_message_direction' => 'outbound',
                'expiration_at' => $expirationAt ? $expirationAt->format('Y-m-d H:i:s') : null,
            ]);
    }

    /**
     * Searches/paginates WhatsApp conversations for the "WhatsApp Conversations" page.
     *
     * @param array{
     *     client?: string,
     *     category?: string,
     *     billable?: string,
     *     date_from?: string,
     *     date_to?: string,
     * } $filters
     */
    public function searchConversations(array $filters, int $offset, int $limit): array
    {
        $query      = $this->query->table('mod_lkn_hook_notification_conversations');
        $countQuery = $this->query->table('mod_lkn_hook_notification_conversations');

        $this->applyConversationFilters($query, $filters);
        $this->applyConversationFilters($countQuery, $filters);

        $conversations = $query
            ->orderBy('last_message_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = $countQuery->count();

        return [
            'conversations' => $conversations->toArray(),
            'totalConversations' => $total,
        ];
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<string, string>              $filters
     */
    private function applyConversationFilters($query, array $filters): void
    {
        if (!empty($filters['client'])) {
            $clientTerm = trim($filters['client']);

            if (ctype_digit($clientTerm)) {
                $query->where('client_id', intval($clientTerm));
            } else {
                $matchingClientIds = Capsule::table('tblclients')
                    ->where('firstname', 'like', "%{$clientTerm}%")
                    ->orWhere('lastname', 'like', "%{$clientTerm}%")
                    ->orWhere('companyname', 'like', "%{$clientTerm}%")
                    ->orWhere('email', 'like', "%{$clientTerm}%")
                    ->pluck('id')
                    ->toArray();

                $query->where(function ($q) use ($clientTerm, $matchingClientIds) {
                    $q->whereIn('client_id', empty($matchingClientIds) ? [-1] : $matchingClientIds)
                        ->orWhere('phone_number', 'like', "%{$clientTerm}%");
                });
            }
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['billable']) && $filters['billable'] !== '') {
            if ($filters['billable'] === 'unknown') {
                $query->whereNull('billable');
            } else {
                $query->where('billable', $filters['billable'] === '1' ? 1 : 0);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('last_message_at', '>=', (new DateTime($filters['date_from']))->format('Y-m-d 00:00:00'));
        }

        if (!empty($filters['date_to'])) {
            $query->where('last_message_at', '<=', (new DateTime($filters['date_to']))->format('Y-m-d 23:59:59'));
        }
    }

    /**
     * @return array{total_conversations: int, by_category: array<string, int>, billable: int, free_or_unbilled: int, unknown: int}
     */
    public function getConversationAnalytics(?DateTime $from = null, ?DateTime $to = null): array
    {
        $query = $this->query->table('mod_lkn_hook_notification_conversations');

        if ($from) {
            $query->where('first_message_at', '>=', $from->format('Y-m-d H:i:s'));
        }

        if ($to) {
            $query->where('first_message_at', '<=', $to->format('Y-m-d H:i:s'));
        }

        $total = (clone $query)->count();

        $byCategory = (clone $query)
            ->select(
                'category',
                $this->query::table('mod_lkn_hook_notification_conversations')->raw('COUNT(*) as total')
            )
            ->groupBy('category')
            ->get()
            ->toArray();

        $categoryCounts = [];

        foreach ($byCategory as $row) {
            $categoryCounts[$row->category ?? 'unknown'] = (int) $row->total;
        }

        $billableCount = (clone $query)->where('billable', 1)->count();
        $freeCount     = (clone $query)->where('billable', 0)->count();
        $unknownCount  = (clone $query)->whereNull('billable')->count();

        return [
            'total_conversations' => $total,
            'by_category' => $categoryCounts,
            'billable' => $billableCount,
            'free_or_unbilled' => $freeCount,
            'unknown' => $unknownCount,
        ];
    }
}
