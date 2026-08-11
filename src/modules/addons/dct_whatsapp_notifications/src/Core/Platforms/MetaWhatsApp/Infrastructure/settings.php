<?php

use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;

return [
    [
        'setting' => Settings::WP_META_ENABLE,
        'label' => lkn_hn_lang('Enable Meta WhatsApp'),
        'description' => lkn_hn_lang('Enable Meta WhatsApp integration.'),
        'type' => 'checkbox',
        'warning_on_unchecked' => lkn_hn_lang('This platform only will send messages when this is checked.'),
    ],
    [
        'setting' => Settings::WP_BUSINESS_ACCOUNT_ID,
        'label' => lkn_hn_lang('Business Account ID'),
        'description' => lkn_hn_lang('Unique identifier of a WhatsApp business account created in WhatsApp Business Manager.'),
        'type' => 'password',
        'popover-config' => [
            'popover-title' =>lkn_hn_lang('Business Account ID'),
            'popover-images' =>[
            ['popover-img' => 'popover-wp-accountID.png', 'popover-width' => '700']
            ]
        ]   
    ],
    [
        'setting' => Settings::WP_USER_ACCESS_TOKEN,
        'label' => lkn_hn_lang('User Access Token'),
        'description' => lkn_hn_lang('Unique token allowing third-party apps to access the WhatsApp API functionalities with prior authorization.'),
        'type' => 'password',
        'popover-config' => [
            'popover-title' =>lkn_hn_lang('User Access Token'),
            'popover-images' =>[
                ['popover-img' => 'popover-wp-userAcessToken.png', 'popover-width' => '700']
            ]
        ]
    ],
    [
        'setting' => Settings::WP_PHONE_NUMBER_ID,
        'label' => lkn_hn_lang('Phone Number ID'),
        'description' => lkn_hn_lang('Phone number associated with the WhatsApp business account for API interactions.'),
        'type' => 'password',
        'popover-config' => [
            'popover-title' =>lkn_hn_lang('Phone Number ID'),
            'popover-images' =>[
                ['popover-img' => 'popover-wp-phoneID.png', 'popover-width' => '700']
            ]
        ]
    ],
    [
        'setting' => Settings::WP_VERSION,
        'label' => lkn_hn_lang('WhatsApp API Version'),
        'description' => lkn_hn_lang('Defines the WhatsApp API version used for integration to ensure compatibility.'),
        'type' => 'select',
        'default' => 'v22.0',
        'options' => [
            ['label' => 'v22.0', 'value' => 'v22.0'],
        ],
    ],
    [
        'setting' => Settings::WP_CUSTOM_FIELD_ID,
        'label' => lkn_hn_lang('WhatsApp Custom Field ID'),
        'description' => lkn_hn_lang('Select the custom field for WhatsApp numbers; default is the WHMCS phone field if not set. Numbers must include country and area code.'),
        'type' => 'select',
        'options' => 'lkn_hn_custom_fields',
        'default'=> [
            'value'=> null,  'label' => lkn_hn_lang('Use default WHMCS phone field'),
        ],
        'hide' => true,
    ],
    [
        'setting' => Settings::WP_SHOW_INVOICE_REMINDER_BTN_WHEN_PAID,
        'label' => lkn_hn_lang('Display Button on Paid Invoices'),
        'description' => lkn_hn_lang('Enable to display a button that sends invoice reminder notifications on paid invoices.'),
        'type' => 'checkbox',
    ],
    [
        'setting' => Settings::WP_USE_TICKET_WHATSAPP_CF_WHEN_SET,
        'label' => lkn_hn_lang('Ticket Answered Notification must prefer the WhatsApp custom field for tickets'),
        'description' => lkn_hn_lang('Enable to send notifications to the custom WhatsApp field instead of default.'),
        'type' => 'select',
        'options' => 'lkn_hn_custom_fields',
        'hide' => true,
    ],
    [
        'setting' => Settings::WP_MSG_TEMPLATE_LANG,
        'label' => lkn_hn_lang('Default language for template messages'),
        'description' => lkn_hn_lang('Defines the default language for WhatsApp Cloud API template messages.'),
        'type' => 'select',
        'options' => 'lkn_hn_locales',
    ],
    [
        'setting' => Settings::WP_WEBHOOK_VERIFY_TOKEN,
        'label' => lkn_hn_lang('Webhook Verify Token'),
        'description' => lkn_hn_lang('Any string you choose. Set the same value as the "Verify Token" when configuring the WhatsApp webhook in the Meta App Dashboard. This webhook is what allows the module to track delivered/read/failed status and conversation analytics. Webhook callback URL: [1]', [
            lkn_hn_get_whatsapp_webhook_url(),
        ]),
        'type' => 'text',
    ],
    [
        'separator' => true,
        'title' => lkn_hn_lang('WhatsApp Two-Factor Authentication (login codes)'),
        'description' => lkn_hn_lang('Used only by the separate "WhatsApp Verification" 2FA module (modules/security/dct2fa), to send login codes via Meta. Meta requires an approved "Authentication" category template for this - it cannot send an unsolicited code as free text. Create one in Meta Business Manager (WhatsApp Manager > Message Templates > Create Template > Authentication) and get it approved - it\'ll then show up in the dropdown below automatically. Sends the code as the template\'s only variable, so use a simple single-placeholder Authentication template (e.g. "Your code is {{1}}").'),
    ],
    [
        'setting' => Settings::WP_2FA_TEMPLATE_NAME,
        'label' => lkn_hn_lang('2FA Authentication Template Name'),
        'description' => lkn_hn_lang('Auto-detected from your approved Meta templates. If empty, either none are approved yet, or Instance ID/Access Token above aren\'t saved yet - save those first, then reload this page. Leave unselected to disable Meta as a 2FA delivery option (Botms.in/Baileys still work independently of this).'),
        'type' => 'select',
        'options' => lkn_hn_fetch_meta_authentication_templates(),
        'default' => ['value' => '', 'label' => lkn_hn_lang('None')],
    ],
    [
        'setting' => Settings::WP_2FA_TEMPLATE_HAS_BUTTON,
        'label' => lkn_hn_lang('Template button type'),
        'description' => lkn_hn_lang('Meta has two different button types for Authentication templates, and sending the wrong one gets rejected ("Button at index 0 must be of type X"). If unsure, check your template in Meta Business Manager, or just try one - if it\'s wrong, Meta\'s error will name the correct type. Leave "No button" if your template has no button component at all (most basic ones don\'t).'),
        'type' => 'select',
        'options' => [
            ['value' => '', 'label' => lkn_hn_lang('No button')],
            ['value' => 'copy_code', 'label' => lkn_hn_lang('Copy Code button')],
            ['value' => 'url', 'label' => lkn_hn_lang('One-Tap Autofill (URL) button')],
        ],
        'default' => ['value' => '', 'label' => lkn_hn_lang('No button')],
    ],
    [
        'setting' => Settings::WP_CHARGE_CURRENCY,
        'label' => lkn_hn_lang('Currency symbol/code for charge estimates'),
        'description' => lkn_hn_lang('E.g. "$", "USD", "R$". Used only to label the approximate charges shown on the Message Analytics page.'),
        'type' => 'text',
    ],
    [
        'setting' => Settings::WP_RATE_MARKETING,
        'label' => lkn_hn_lang('Approximate rate per Marketing conversation'),
        'description' => lkn_hn_lang('Your approximate Meta conversation-based rate for the "marketing" category, in the currency above. Meta\'s actual rates vary by country and change periodically; check the current rate card in your Meta Business Manager and update this periodically. Leave blank to exclude this category from the charge estimate.'),
        'type' => 'text',
    ],
    [
        'setting' => Settings::WP_RATE_UTILITY,
        'label' => lkn_hn_lang('Approximate rate per Utility conversation'),
        'description' => lkn_hn_lang('Same as above, for the "utility" category.'),
        'type' => 'text',
    ],
    [
        'setting' => Settings::WP_RATE_AUTHENTICATION,
        'label' => lkn_hn_lang('Approximate rate per Authentication conversation'),
        'description' => lkn_hn_lang('Same as above, for the "authentication" category.'),
        'type' => 'text',
    ],
    [
        'setting' => Settings::WP_RATE_AUTHENTICATION_INTL,
        'label' => lkn_hn_lang('Approximate rate per Authentication (International) conversation'),
        'description' => lkn_hn_lang('Same as above, for the "authentication_international" category (a higher-priced variant Meta uses for some international routes).'),
        'type' => 'text',
    ],
    [
        'setting' => Settings::WP_RATE_SERVICE,
        'label' => lkn_hn_lang('Approximate rate per Service conversation'),
        'description' => lkn_hn_lang('Same as above, for the "service" category (customer-initiated support conversations). Often free/zero-rated by Meta - check your rate card.'),
        'type' => 'text',
    ],
];
