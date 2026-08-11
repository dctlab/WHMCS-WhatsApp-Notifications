<?php

namespace Dct\HookNotification\Core\Notification\Application\Services;

use Dct\HookNotification\Core\Notification\Domain\AbstractNotification;
use Dct\HookNotification\Core\Notification\Domain\NotificationTemplate;
use Dct\HookNotification\Core\Notification\Infrastructure\NotificationTemplateRenderers\MetaWhatsAppTemplateRenderer;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\View\View;

final class NotificationViewService
{
    private MetaWhatsAppTemplateRenderer $metaWhatsAppTemplateRenderer;
    private View $view;

    public function __construct(View $view)
    {
        $this->view = new View();
        $this->view->setTemplateDir(__DIR__ . '/../../Http/Views');
        $this->metaWhatsAppTemplateRenderer =  new MetaWhatsAppTemplateRenderer($this->view);
    }

    public function findTemplateByLang(
        AbstractNotification $notification,
        string $lang
    ): ?NotificationTemplate {
        return current(
            array_filter(
                $notification->templates,
                fn(NotificationTemplate $template) => $template->lang === $lang
            )
        ) ?: null;
    }

    public function getTemplateEditorForPlatform(
        AbstractNotification $notification,
        ?NotificationTemplate $template,
        bool $disableTemplateEditorChanges = false,
    ): string {
        if ($template->platform === Platforms::WHATSAPP) {
            return $this->metaWhatsAppTemplateRenderer->render($notification, $template, $disableTemplateEditorChanges);
        }

        return $this->view->view('template_editors/standard_template_editor', [
            'editing_notification' => $notification,
            'editing_template' => $template,
        ])->render();
    }
}
