<?php

namespace Dct\HookNotification\Core\TestSend\Http\Controllers;

use Dct\HookNotification\Core\Notification\Domain\Client;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;

/**
 * Lets an admin send a one-off test WhatsApp message to a specific client
 * from the Admin Area Client Summary page - useful for verifying a client's
 * number is reachable, or trying out a newly-approved Meta template before
 * wiring it into a real notification.
 *
 * This is a standalone test utility, deliberately NOT routed through
 * NotificationSender - it doesn't create a Notification Report, doesn't
 * respect a client's own WhatsApp opt-out preference (since an admin
 * explicitly testing a number is a different action than an automated
 * notification), and isn't tied to any notification code/template mapping.
 *
 * @since 4.7.0
 */
final class TestSendController
{
    /**
     * Hooked to AdminAreaClientSummaryActionLinks: the "Send Test WhatsApp
     * Message" link shown in the client's "Other Actions" panel, which
     * opens the modal rendered by renderModal() below.
     *
     * WHMCS expects an array of link strings here (each becomes its own
     * item in the panel), not a single HTML string - confirmed against
     * WHMCS's own documented example for this hook.
     *
     * @param array $vars
     *
     * @return array<int, string>
     */
    public function renderActionLink(array $vars): array
    {
        return [
            '<a href="#" data-toggle="modal" data-target="#lknTestSendModal">'
            . '<i class="fa fa-paper-plane"></i> ' . lkn_hn_lang('Send Test WhatsApp Message')
            . '</a>',
        ];
    }

    /**
     * Hooked to AdminAreaClientSummaryPage: renders the modal itself
     * (Bootstrap modal, submitted via AJAX to src/Core/api.php).
     *
     * @param array $vars
     */
    public function renderModal(array $vars): string
    {
        $clientId = (int) ($vars['userid'] ?? 0);

        if ($clientId <= 0) {
            return '';
        }

        try {
            $client = new Client($clientId);
            $phoneNumber = $client->getWpPhoneNumberOrWhmcsPhoneNumber(
                lkn_hn_config(Settings::WP_CUSTOM_FIELD_ID)
            ) ?: '';
        } catch (\Throwable $th) {
            $phoneNumber = '';
        }

        $metaTemplates = [];

        try {
            if (lkn_hn_config(Settings::WP_META_ENABLE)) {
                $settings = PlatformSettingsFactory::makeMetaWhatsAppSettings();
                $client2  = (new PlatformApiClientFactory())->makeMetaWhatsAppClient($settings);

                $response = $client2->getMessageTemplates([
                    'fields' => 'name,language,status',
                    'limit' => 250,
                ]);

                $metaTemplates = array_values(array_filter(
                    $response->body['data'] ?? [],
                    fn (array $tpl) => ($tpl['status'] ?? '') === 'APPROVED'
                ));
            }
        } catch (\Throwable $th) {
            lkn_hn_log('TestSend: failed to fetch Meta templates', [], ['exception' => $th->__toString()]);
        }

        $platforms = [];

        if (lkn_hn_config(Settings::WP_META_ENABLE)) {
            $platforms[] = ['value' => 'meta', 'label' => 'Meta'];
        }

        if (lkn_hn_config(Settings::BOTMS_ENABLE)) {
            $platforms[] = ['value' => 'botms', 'label' => 'Botms.in'];
        }

        if (lkn_hn_config(Settings::BAILEYS_ENABLE)) {
            $platforms[] = ['value' => 'baileys', 'label' => 'Baileys'];
        }

        $phoneNumberEsc = htmlspecialchars($phoneNumber, ENT_QUOTES);

        $platformOptionsHtml = implode('', array_map(
            fn (array $p) => '<option value="' . $p['value'] . '">' . $p['label'] . '</option>',
            $platforms
        ));

        $templateOptionsHtml = '<option value="">' . lkn_hn_lang('Select a template') . '</option>' . implode('', array_map(
            fn (array $t) => '<option value="' . htmlspecialchars($t['name'] . '|' . ($t['language'] ?? 'en_US'), ENT_QUOTES) . '">'
                . htmlspecialchars($t['name'] . ' (' . ($t['language'] ?? '?') . ')', ENT_QUOTES) . '</option>',
            $metaTemplates
        ));

        $paramInputsHtml = '';

        for ($i = 1; $i <= 5; $i++) {
            $paramInputsHtml .= '<input type="text" class="form-control lkn-test-send-param" style="margin-bottom: 5px;" placeholder="' . lkn_hn_lang('Parameter') . " {$i} (" . lkn_hn_lang('leave blank if not needed') . ')">';
        }

        return <<<HTML
            <div class="modal fade" id="lknTestSendModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">{$this->lang('Send Test WhatsApp Message')}</h4>
                        </div>
                        <div class="modal-body">
                            <div id="lknTestSendAlert"></div>

                            <div class="form-group">
                                <label>{$this->lang('Phone Number')}</label>
                                <input type="text" class="form-control" id="lknTestSendPhone" value="{$phoneNumberEsc}">
                                <p class="text-muted small">{$this->lang('Auto-filled from this client profile - edit if you want to test a different number.')}</p>
                            </div>

                            <div class="form-group">
                                <label>{$this->lang('Platform')}</label>
                                <select class="form-control" id="lknTestSendPlatform">
                                    <option value="">{$this->lang('Select a platform')}</option>
                                    {$platformOptionsHtml}
                                </select>
                            </div>

                            <div class="form-group" id="lknTestSendMetaTemplateGroup" style="display: none;">
                                <label>{$this->lang('Meta Template')}</label>
                                <select class="form-control" id="lknTestSendTemplate">
                                    {$templateOptionsHtml}
                                </select>
                            </div>

                            <div class="form-group" id="lknTestSendMetaParamsGroup" style="display: none;">
                                <label>{$this->lang('Template Parameters (in order)')}</label>
                                {$paramInputsHtml}
                            </div>

                            <div class="form-group" id="lknTestSendPlainMessageGroup" style="display: none;">
                                <label>{$this->lang('Message')}</label>
                                <textarea class="form-control" id="lknTestSendMessage" rows="4">{$this->lang('This is a test message from')} {$this->lang('WHMCS')}.</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{$this->lang('Close')}</button>
                            <button type="button" class="btn btn-primary" id="lknTestSendSubmit">{$this->lang('Send Test')}</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    var platformSelect = document.getElementById('lknTestSendPlatform');
                    var metaTemplateGroup = document.getElementById('lknTestSendMetaTemplateGroup');
                    var metaParamsGroup = document.getElementById('lknTestSendMetaParamsGroup');
                    var plainMessageGroup = document.getElementById('lknTestSendPlainMessageGroup');

                    if (!platformSelect) { return; }

                    platformSelect.addEventListener('change', function () {
                        var isMeta = platformSelect.value === 'meta';
                        metaTemplateGroup.style.display = isMeta ? 'block' : 'none';
                        metaParamsGroup.style.display = isMeta ? 'block' : 'none';
                        plainMessageGroup.style.display = (!isMeta && platformSelect.value) ? 'block' : 'none';
                    });

                    document.getElementById('lknTestSendSubmit').addEventListener('click', function () {
                        var alertBox = document.getElementById('lknTestSendAlert');
                        var platform = platformSelect.value;
                        var phone = document.getElementById('lknTestSendPhone').value.trim();

                        alertBox.innerHTML = '';

                        if (!platform || !phone) {
                            alertBox.innerHTML = '<div class="alert alert-warning">{$this->lang('Please select a platform and enter a phone number.')}</div>';
                            return;
                        }

                        var payload = {
                            client_id: {$clientId},
                            platform: platform,
                            phone_number: phone
                        };

                        if (platform === 'meta') {
                            payload.template = document.getElementById('lknTestSendTemplate').value;
                            payload.params = Array.prototype.map.call(
                                document.querySelectorAll('.lkn-test-send-param'),
                                function (el) { return el.value; }
                            );

                            if (!payload.template) {
                                alertBox.innerHTML = '<div class="alert alert-warning">{$this->lang('Please select a template.')}</div>';
                                return;
                            }
                        } else {
                            payload.message = document.getElementById('lknTestSendMessage').value;
                        }

                        var apiBase = '{$this->apiBaseUrl()}';

                        fetch(apiBase + '?endpoint=test-send', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (data && data.success) {
                                    alertBox.innerHTML = '<div class="alert alert-success">{$this->lang('Test message sent.')}</div>';
                                } else {
                                    alertBox.innerHTML = '<div class="alert alert-danger">' + (data && data.message ? data.message : '{$this->lang('Send failed.')}') + '</div>';
                                }
                            })
                            .catch(function () {
                                alertBox.innerHTML = '<div class="alert alert-danger">{$this->lang('Send failed - request error.')}</div>';
                            });
                    });
                })();
            </script>
            HTML;
    }

    /**
     * Handles the AJAX send request (src/Core/api.php?endpoint=test-send).
     *
     * @return array{success: bool, message?: string}
     */
    public function send(): array
    {
        if (empty($_SESSION['adminid'])) {
            return ['success' => false, 'message' => lkn_hn_lang('Admin session required.')];
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $platform = $body['platform'] ?? '';
        $phoneNumber = preg_replace('/\D+/', '', (string) ($body['phone_number'] ?? ''));

        if (!$phoneNumber) {
            return ['success' => false, 'message' => lkn_hn_lang('A valid phone number is required.')];
        }

        try {
            if ($platform === 'meta') {
                return $this->sendViaMeta($phoneNumber, (string) ($body['template'] ?? ''), $body['params'] ?? []);
            }

            if ($platform === 'botms') {
                return $this->sendPlain('botms', $phoneNumber, (string) ($body['message'] ?? ''));
            }

            if ($platform === 'baileys') {
                return $this->sendPlain('baileys', $phoneNumber, (string) ($body['message'] ?? ''));
            }
        } catch (\Throwable $th) {
            lkn_hn_log('TestSend: send error', ['platform' => $platform], ['exception' => $th->__toString()]);

            return ['success' => false, 'message' => lkn_hn_lang('Unexpected error - check the module log.')];
        }

        return ['success' => false, 'message' => lkn_hn_lang('Unknown platform.')];
    }

    private function sendViaMeta(string $phoneNumber, string $storedTemplateValue, array $params): array
    {
        [$templateName, $langCode] = array_pad(explode('|', $storedTemplateValue, 2), 2, 'en_US');

        if (!$templateName) {
            return ['success' => false, 'message' => lkn_hn_lang('No template selected.')];
        }

        $settings = PlatformSettingsFactory::makeMetaWhatsAppSettings();
        $client   = (new PlatformApiClientFactory())->makeMetaWhatsAppClient($settings);

        $params = array_values(array_filter($params, fn ($v) => $v !== ''));

        $components = [];

        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], $params),
            ];
        }

        $response = $client->sendMessageTemplate($phoneNumber, $templateName, $components, $langCode ?: 'en_US');

        $succeeded = isset($response->httpStatusCode) && $response->httpStatusCode >= 200 && $response->httpStatusCode < 300;

        lkn_hn_log('TestSend: Meta', ['phoneNumber' => $phoneNumber, 'template' => $templateName], $response, [$phoneNumber]);

        return $succeeded
            ? ['success' => true]
            : ['success' => false, 'message' => $response->body['error']['message'] ?? lkn_hn_lang('Send failed.')];
    }

    private function sendPlain(string $platform, string $phoneNumber, string $message): array
    {
        if ($message === '') {
            return ['success' => false, 'message' => lkn_hn_lang('Message cannot be empty.')];
        }

        if ($platform === 'botms') {
            $settings = PlatformSettingsFactory::makeBotmsSettings([]);
            $client   = (new PlatformApiClientFactory())->makeBotmsClient($settings);
        } else {
            $settings = PlatformSettingsFactory::makeBaileysSettings([]);
            $client   = (new PlatformApiClientFactory())->makeBaileysClient($settings);
        }

        $response = $client->sendTextMessage($phoneNumber, $message);

        $succeeded = isset($response->httpStatusCode) && $response->httpStatusCode >= 200 && $response->httpStatusCode < 300
            && ($response->body['status'] ?? null) !== 'error';

        return $succeeded
            ? ['success' => true]
            : ['success' => false, 'message' => $response->body['message'] ?? lkn_hn_lang('Send failed.')];
    }

    private function lang(string $text): string
    {
        return htmlspecialchars(lkn_hn_lang($text), ENT_QUOTES);
    }

    private function apiBaseUrl(): string
    {
        return lkn_hn_get_module_root_url() . '/src/Core/api.php';
    }
}
