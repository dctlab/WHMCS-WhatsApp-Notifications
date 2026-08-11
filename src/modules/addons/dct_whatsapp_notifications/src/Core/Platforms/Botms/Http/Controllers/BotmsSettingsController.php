<?php

namespace Dct\HookNotification\Core\Platforms\Botms\Http\Controllers;

use Dct\HookNotification\Core\Platforms\Botms\Infrastructure\BotmsApiClient;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Dct\HookNotification\Core\Shared\Infrastructure\Interfaces\BaseController;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;

final class BotmsSettingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct(new View());
    }

    /**
     * Runs after the generic settings save (see SettingsController). If the
     * settings form was just submitted (or the "register_webhook" button
     * inside this box was clicked) and credentials are present, registers
     * this module's webhook URL with botms.in so it starts sending events
     * (incoming messages, connection status, etc) here.
     *
     * @param array<mixed> $request
     */
    public function handle(array $request): string
    {
        $instanceId   = lkn_hn_config(Settings::BOTMS_INSTANCE_ID);
        $accessToken  = lkn_hn_config(Settings::BOTMS_ACCESS_TOKEN);
        $webhookUrl   = lkn_hn_get_botms_webhook_url();
        $registeredAt = lkn_hn_config(Settings::BOTMS_WEBHOOK_REGISTERED_AT);

        $justSubmitted = !empty($request);
        $result        = null;

        if ($justSubmitted && !empty($instanceId) && !empty($accessToken)) {
            $client = new BotmsApiClient($instanceId, $accessToken);

            $apiResponse = $client->setWebhook($webhookUrl, true);

            $succeeded = $apiResponse->httpStatusCode >= 200
                && $apiResponse->httpStatusCode < 300
                && ($apiResponse->body['status'] ?? null) !== 'error';

            if ($succeeded) {
                lkn_hn_config_set(Platforms::BOTMS, Settings::BOTMS_WEBHOOK_REGISTERED_AT, date('Y-m-d H:i:s'));
                $registeredAt = date('Y-m-d H:i:s');
            }

            $result = [
                'succeeded' => $succeeded,
                'message' => $apiResponse->body['message'] ?? $apiResponse->body['error'] ?? null,
            ];
        }

        return $this->view->view('webhook_status', [
            'webhook_url' => $webhookUrl,
            'registered_at' => $registeredAt,
            'has_credentials' => !empty($instanceId) && !empty($accessToken),
            'result' => $result,
        ])->render();
    }
}
