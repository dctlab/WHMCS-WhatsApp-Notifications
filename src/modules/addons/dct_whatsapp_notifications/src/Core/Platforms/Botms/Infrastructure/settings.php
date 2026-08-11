<?php

use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;

return [
    [
        'setting' => Settings::BOTMS_ENABLE,
        'label' => lkn_hn_lang('Enable Botms.in'),
        'description' => lkn_hn_lang('Enable the Botms.in WhatsApp integration (HTTP API for WhatsApp - https://botms.in).'),
        'type' => 'checkbox',
        'warning_on_unchecked' => lkn_hn_lang('This platform only will send messages when this is checked.'),
    ],
    [
        'setting' => Settings::BOTMS_INSTANCE_ID,
        'label' => lkn_hn_lang('Instance ID'),
        'description' => lkn_hn_lang('Your Botms.in instance ID, e.g. "609ACF283XXXX".'),
        'type' => 'text',
    ],
    [
        'setting' => Settings::BOTMS_ACCESS_TOKEN,
        'label' => lkn_hn_lang('Access Token'),
        'description' => lkn_hn_lang('Your Botms.in access token.'),
        'type' => 'password',
    ],
    [
        'setting' => Settings::BOTMS_WP_CUSTOM_FIELD_ID,
        'label' => lkn_hn_lang('WhatsApp Custom Field'),
        'description' => lkn_hn_lang('Client profile field that contains the WhatsApp number.'),
        'type' => 'select',
        'options' => 'lkn_hn_custom_fields',
        'default' => [
            'value' => null, 'label' => lkn_hn_lang('Use default WHMCS phone field'),
        ],
        'hide' => true,
    ],
];
