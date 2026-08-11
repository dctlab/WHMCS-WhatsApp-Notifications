<?php

namespace Dct\HookNotification\Core\Notification\Infrastructure\NotificationTemplateRenderers;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationParameter;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Application\MetaWhatsAppService;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;

final class MetaWhatsAppTemplateRenderer
{
    private MetaWhatsAppService $metaWhatsAppService;
    private View $view;

    public function __construct(View $view)
    {
        $this->metaWhatsAppService = new MetaWhatsAppService();
        $this->view                = $view;
    }

    /**
     * @param  AbstractNotification      $notificationTemplate
     * @param  NotificationTemplate|null $template                     This has the platform_payload field so this class knows which parameter to select.
     * @param boolean                   $disableTemplateEditorChanges
     *
     * @return string
     */
    public function render(
        AbstractNotification $notificationTemplate,
        ?NotificationTemplate $template,
        bool $disableTemplateEditorChanges = false,
    ): string {
        $messageTemplatesRes = $this->metaWhatsAppService->getMessageTemplatesForView();

        if (empty($messageTemplatesRes->body['data'])) {
            return $this->view->view(
                'error',
                [
                    'error' => lkn_hn_safe_json_encode($messageTemplatesRes->toArray()),
                    'link' => [
                        'label' => 'Go to Meta WhatsApp settings',
                        'url' => '?module=dct_whatsapp_notifications&page=platforms/wp/settings',
                    ],
                ]
            )->render();
        }

        /** @var array<mixed> $messageTemplates */
        $messageTemplates = $messageTemplatesRes->body['data'];

        $templateOptions = array_column($messageTemplates, 'name', 'name');
        ksort($templateOptions);
        $selectedName           = $template->template ?? null;
        $editingMessageTemplate = $this->findTemplateByName($messageTemplates, $selectedName);

        return $this->view->view(
            'template_editors/meta_wp_template_editor',
            [
                'message_templates' => $messageTemplates,
                'message_templates_options' => $templateOptions,
                'editing_message_template' => $editingMessageTemplate,
                'editing_message_template_name' => $selectedName,
                'editing_template_header_view' => $this->renderComponent(
                    $editingMessageTemplate,
                    'HEADER',
                    'header',
                    $template,
                    $notificationTemplate
                ),
                'editing_template_body_view' => $this->renderComponent(
                    $editingMessageTemplate,
                    'BODY',
                    'body',
                    $template,
                    $notificationTemplate
                ),
                'editing_template_buttons_view' => $this->renderComponent(
                    $editingMessageTemplate,
                    'BUTTONS',
                    'button',
                    $template,
                    $notificationTemplate
                ),
                'editing_notification' => $notificationTemplate,
                'editing_template' => $template,
                'disable_template_editor_changes' => $disableTemplateEditorChanges,
            ]
        )->render();
    }

    private function findTemplateByName(array $templates, ?string $name): array
    {
        return current(
            array_filter(
                $templates,
                fn($tpl) => $tpl['name'] === $name
            )
        ) ?: [];
    }

    private function findMessageTemplateComponent(
        ?array $template,
        string $type
    ): ?array {
        return current(
            array_filter(
                $template['components'] ?? [],
                fn($c) => $c['type'] === $type
            )
        ) ?: null;
    }

    private function renderComponent(
        ?array $template,
        string $componentType,
        string $name,
        ?NotificationTemplate $editingTemplate,
        AbstractNotification $notification
    ): ?string {
        $component = $this->findMessageTemplateComponent($template, $componentType);

        if (!$component) {
            return null;
        }

        return match ($componentType) {
            'HEADER' => $this->renderHeader(
                $component,
                $name,
                $editingTemplate,
                $notification
            ),
            'BODY' => $this->renderWithParams(
                $component['text'],
                "{$name}-parameters[]",
                $name,
                $editingTemplate,
                $notification,
                useExplicitIndex: true
            ),
            'BUTTONS' => $this->renderButtons(
                $component['buttons'],
                $name,
                $editingTemplate,
                $notification
            ),
            default => null,
        };
    }

    private function renderHeader(
        array $component,
        string $name,
        ?NotificationTemplate $notificationTemplate,
        AbstractNotification $notification
    ): ?string {
        if (strtoupper($component['format']) === 'TEXT' && strpos($component['text'], '{{') === false) {
            /** @var string */
            return $component['text'];
        }

        if (!in_array($component['format'], ['TEXT', 'DOCUMENT', 'IMAGE', 'VIDEO'])) {
            return null;
        }

        return $this->renderWithParams(
            '{{1}}',
            'header-parameter',
            $name,
            $notificationTemplate,
            $notification
        );
    }

    /**
     * @param bool $useExplicitIndex When true, each rendered <select>'s name
     *                               carries its actual {{N}} value explicitly
     *                               (e.g. "body-parameters[7]"), instead of
     *                               relying on a plain "name[]" array, which
     *                               PHP numbers by the order fields appear in
     *                               the HTML - not by the {{N}} they actually
     *                               represent. Those two only match when a
     *                               template's placeholders happen to appear
     *                               in strict ascending order in its text;
     *                               Meta doesn't require that (e.g. a URL
     *                               built as ".../{{7}}/path?id={{6}}" is
     *                               valid), so without this, saving silently
     *                               swaps values between out-of-order
     *                               positions. Only used for BODY - HEADER
     *                               always has exactly one parameter, and
     *                               BUTTONS number each button's single
     *                               dynamic segment independently (always
     *                               "{{1}}" relative to that button, not
     *                               globally), so applying this there would
     *                               risk collisions instead of fixing anything.
     */
    private function renderWithParams(
        string $templateText,
        string $selectName,
        string $component,
        ?NotificationTemplate $notificationTemplate,
        AbstractNotification $notification,
        bool $useExplicitIndex = false
    ): string {
        return preg_replace_callback(
            '/{{(\d+)}}/',
            fn($matches) => $this->renderSelect(
                (int) $matches[1],
                $selectName,
                $component,
                $notificationTemplate,
                $notification,
                $useExplicitIndex
            ),
            $templateText
        );
    }

    private function renderSelect(
        int $index,
        string $selectName,
        string $component,
        ?NotificationTemplate $notificationTemplate,
        AbstractNotification $notification,
        bool $useExplicitIndex = false
    ): string {
        $options = $this->renderParameterOptions(
            $component,
            $index,
            $notificationTemplate,
            $notification
        );

        $fieldName = $useExplicitIndex
            ? rtrim($selectName, '[]') . "[{$index}]"
            : $selectName;

        return "<select name='{$fieldName}' required>
            <option value=''>" . lkn_hn_lang('Select a parameter') . "</option>
            {$options}
        </select>";
    }

    private function renderButtons(
        array $buttons,
        string $component,
        ?NotificationTemplate $notificationTemplate,
        AbstractNotification $notification
    ): string {
        return implode(
            '',
            array_map(
                function ($btn) use (
                    $component,
                    $notificationTemplate,
                    $notification,
                ) {
                    return $btn['type'] === 'URL' && str_contains($btn['url'], '{{')
                        ? $this->renderWithParams(
                            $btn['url'],
                            'button-parameters[]',
                            $component,
                            $notificationTemplate,
                            $notification
                        )
                        : '<button type="button" class="btn btn-primary btn-sm" style="min-width: 120px;">' . $btn['text'] . '</button>';
                },
                $buttons
            )
        );
    }

    private function renderParameterOptions(
        string $component,
        int $position,
        ?NotificationTemplate $notificationTemplate,
        AbstractNotification $notification
    ): string {
        return join(
            array_map(
                function (NotificationParameter $param) use (
                    $component,
                    $position,
                    $notificationTemplate,
                ) {
                    $value = $param->code;

                    $selected = $notificationTemplate?->getParamCodeForPos(
                        $component,
                        $position
                    ) === $value ? 'selected' : '';

                    return "<option {$selected} value='{$value}'>{$param->label}</option>";
                },
                $notification->parameters->params
            )
        );
    }
}
