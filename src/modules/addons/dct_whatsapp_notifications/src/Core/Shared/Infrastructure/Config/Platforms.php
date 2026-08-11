<?php

namespace Dct\HookNotification\Core\Shared\Infrastructure\Config;

/**
 * @since 1.0.0
 */
enum Platforms: string
{
    case WHATSAPP       = 'wp';
    case WP_EVO         = 'wp-evo';
    case BAILEYS        = 'baileys';
    case BOTMS          = 'botms';
    case CHATWOOT       = 'cw';
    case MODULE         = 'mod';
    case BULK_MESSAGING = 'bulk';

    public function label(): string
    {
        return match ($this) {
            self::WHATSAPP => lkn_hn_lang('WhatsApp Meta'),
            self::CHATWOOT => lkn_hn_lang('Chatwoot'),
            self::WP_EVO => lkn_hn_lang('WhatsApp Evolution'),
            self::BAILEYS => lkn_hn_lang('WhatsApp Baileys'),
            self::BOTMS => lkn_hn_lang('WhatsApp Botms.in'),
            self::MODULE => lkn_hn_lang('Module'),
            self::BULK_MESSAGING => lkn_hn_lang('Bulk Messaging'),
        };
    }
}
