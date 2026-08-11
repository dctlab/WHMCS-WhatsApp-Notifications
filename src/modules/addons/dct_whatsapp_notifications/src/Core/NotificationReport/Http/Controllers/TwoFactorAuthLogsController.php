<?php

namespace Dct\HookNotification\Core\NotificationReport\Http\Controllers;

use Dct\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;
use WHMCS\Database\Capsule;

/**
 * Reads the audit log written by the separate "WhatsApp Verification" 2FA
 * module (modules/security/dct2fa) - shown here instead of over there
 * since that module doesn't have this addon's admin page/reporting
 * infrastructure (filters, pagination, etc).
 *
 * @since 4.6.0
 */
final class TwoFactorAuthLogsController extends BaseController
{
    private const TABLE = 'mod_lkn_wa2fa_logs';
    private const PER_PAGE = 30;

    public function __construct(View $view)
    {
        parent::__construct($view);
    }

    public function viewClientLogs(array $request): void
    {
        $this->render($request, 'client', lkn_hn_lang('WhatsApp 2FA - Client Logs'));
    }

    public function viewAdminLogs(array $request): void
    {
        $this->render($request, 'admin', lkn_hn_lang('WhatsApp 2FA - Admin Logs'));
    }

    private function render(array $request, string $userType, string $title): void
    {
        if (!Capsule::schema()->hasTable(self::TABLE)) {
            $this->view->view('pages/2fa_logs', [
                'title' => $title,
                'user_type' => $userType,
                'table_missing' => true,
                'logs' => [],
                'current_page' => 1,
                'total_logs' => 0,
                'per_page' => self::PER_PAGE,
                'filters' => $request,
            ]);

            return;
        }

        $currentPage = max(1, (int) ($request['pageN'] ?? 1));

        $query = Capsule::table(self::TABLE)->where('user_type', $userType);

        if (!empty($request['f_user_id'])) {
            $query->where('user_id', (int) $request['f_user_id']);
        }

        if (!empty($request['f_event'])) {
            $query->where('event', $request['f_event']);
        }

        if (!empty($request['f_date_from'])) {
            $query->where('created_at', '>=', $request['f_date_from'] . ' 00:00:00');
        }

        if (!empty($request['f_date_to'])) {
            $query->where('created_at', '<=', $request['f_date_to'] . ' 23:59:59');
        }

        $total = (clone $query)->count();

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->offset(($currentPage - 1) * self::PER_PAGE)
            ->limit(self::PER_PAGE)
            ->get();

        $clientNames = [];

        if ($userType === 'client') {
            $ids = $logs->pluck('user_id')->unique()->values()->toArray();

            if (!empty($ids)) {
                $clientNames = Capsule::table('tblclients')
                    ->whereIn('id', $ids)
                    ->get(['id', 'firstname', 'lastname'])
                    ->keyBy('id')
                    ->map(fn ($c) => trim("{$c->firstname} {$c->lastname}"))
                    ->toArray();
            }
        } else {
            $ids = $logs->pluck('user_id')->unique()->values()->toArray();

            if (!empty($ids)) {
                $clientNames = Capsule::table('tbladmins')
                    ->whereIn('id', $ids)
                    ->get(['id', 'firstname', 'lastname'])
                    ->keyBy('id')
                    ->map(fn ($a) => trim("{$a->firstname} {$a->lastname}"))
                    ->toArray();
            }
        }

        $logs = $logs->map(function ($log) use ($clientNames) {
            $log->user_name = $clientNames[$log->user_id] ?? null;

            return $log;
        });

        $this->view->view('pages/2fa_logs', [
            'title' => $title,
            'user_type' => $userType,
            'table_missing' => false,
            'logs' => $logs,
            'current_page' => $currentPage,
            'total_logs' => $total,
            'per_page' => self::PER_PAGE,
            'filters' => $request,
        ]);
    }
}
