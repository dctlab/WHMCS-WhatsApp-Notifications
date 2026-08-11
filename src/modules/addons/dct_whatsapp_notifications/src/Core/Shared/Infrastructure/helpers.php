<?php

use Dct\HookNotification\Core\Shared\Infrastructure\Config\Platforms;
use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Dct\HookNotification\Core\Shared\Infrastructure\I18n\I18n;
use Dct\HookNotification\Core\Shared\Infrastructure\Result;
use WHMCS\Database\Capsule;
use WHMCS\Language\ClientLanguage;
use WHMCS\Utility\Country;

/**
 * @return array{
 *     label: string,
 *     locale: string,
 *     locale_expanded: string,
 *     country_code: string
 * }
 */
function lkn_hn_get_language_locales_for_view(): array
{
    $whmcsLocales = ClientLanguage::getLocales();

    $result = [];

    foreach ($whmcsLocales as $item) {
        $label = $item['localisedName'];

        if ($item['locale'] === 'pt_BR') {
            $label .= ' (BR)';
        }

        $result[] = [
            'label' => $label,
            'value' => $item['locale'],
            'locale_expanded' => $item['language'],
            'country_code' => $item['countryCode'],
        ];
    }

    return $result;
}

function lkn_hn_get_client_countries_for_view()
{
    $countries = (new Country())->getCountries();

    return array_map(
        function (string $countryCode, array $item): array {
            return [
                'value' => $countryCode,
                'label' => $item['name'],
            ];
        },
        array_keys($countries),
        $countries,
    );
}

function lkn_hn_get_products_for_view(): array
{
    $products = Capsule::table('tblproducts')
        ->get(['id as value', 'name as label']);

    return array_map(function ($item) {
        return (array) $item;
    }, $products->toArray());
}

/**
 * @return array<array{label: string, value: string}>
 */
function lkn_hn_get_client_custom_fields_for_view(): array
{
    $query  = Capsule::table('tblcustomfields')->where('adminonly', '');
    $result = $query->get(['id as value', 'fieldname as label']);

    if (is_array($result)) {
        throw new Exception('Unable to retrieve custom fields');
    }

    return array_map(
        fn ($item) => (array) $item,
        $result->toArray()
    );
}

function define_i18n_lang()
{
    $language = lkn_hn_config(Settings::LANGUAGE);

    if (!$language) {
        $language = $language = Capsule::table('tblconfiguration')
            ->where('setting', 'Language')
            ->first('value')->value;
    }

    if (!in_array($language, ['english', 'portugues-br', 'portugues-pt'], true)) {
        $language = 'english';
    }

    return $language;
}

I18n::getInstance()::load(define_i18n_lang());


/**
 * This should work for both PHP and Smarty templates.
 *
 * @param  array|string $text
 *
 * @return string returns $text if it is not found on the current language.
 */
function lkn_hn_lang(array|string $text, array|Smarty_Internal_Template $params = []): string
{
    $key = is_array($text) ? ($text['text'] ?? '') : $text;

    if (empty($key)) {
        return '[empty]';
    }

    $translated = I18n::getInstance()->get($key);

    $params = is_array($text) ? ($text['params'] ?? []) : $params;

    if (!is_iterable($params)) {
        $params = [];
    }

    foreach ($params as $key => $value) {
        $key_       = $key;
        $key_      += 1;
        $translated = str_replace("[{$key_}]", $value, $translated);
    }

    return $translated;
}

function lkn_hn_log(
    string $action,
    array|object|string|null $request,
    array|object|string|null $response = '',
    array $masks = []
) {
    if (!lkn_hn_config(Settings::ENABLE_LOG)) {
        return;
    }

    $request = (array) $request;
    $request = empty($request) ? '' : json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $response = is_string($response) ? $response : json_encode((array) ($response), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    logModuleCall(
        'dct_whatsapp_notifications',
        $action,
        $request,
        $response,
        '',
        $masks
    );
}

function lkn_hn_config(Settings $setting)
{
    if (!Capsule::schema()->hasTable('mod_dct_hook_notification_configs')) {
        return null;
    }

    $value = Capsule::table('mod_dct_hook_notification_configs')
        ->where('setting', $setting->value)
        ->first('value')
        ->value;

    $parsed_value = match ($setting) {
        Settings::CW_ENABLED => boolval($value),
        Settings::BAILEYS_ENABLE => boolval($value),
        Settings::WP_CUSTOM_FIELD_ID => is_integer($value) ? (int) $value : $value,
        Settings::WP_BUSINESS_ACCOUNT_ID => (int) $value,
        Settings::WP_MSG_TEMPLATE_ASSOCS => json_decode($value, true),
        Settings::WP_MESSAGE_TEMPLATES => is_string($value) ? json_decode($value, true) : null,
        Settings::DEFAULT_CLIENT_NAME => ucwords(strtolower($value)),
        Settings::CW_ACCOUNT_ID => (int) $value,
        Settings::CW_WHATSAPP_INBOX_ID => (int) $value,
        Settings::CW_FACEBOOK_INBOX_ID => (int) $value,
        Settings::CW_LISTEN_WHATSAPP => (bool) $value,
        Settings::CW_ACTIVE_NOTIFS => json_decode($value, true),
        Settings::CW_CLIENT_STATS_TO_SEND => $value ? json_decode($value, true) : [],
        Settings::CW_CUSTOM_FIELDS_TO_SEND => $value ? json_decode($value, true) : [],
        Settings::CW_ENABLE_LIVE_CHAT => (bool) $value,
        Settings::CW_LIVE_CHAT_MODULE_ATTRS_TO_SEND => $value ? json_decode($value, true) : [],
        Settings::ENABLE_LOG => (bool) $value,
        Settings::OBJECT_PAGES_TO_SHOW_REPORTS => $value,
        Settings::WP_EVO_ENABLE => (bool) $value,
        Settings::CW_LIVE_CHAT_SCRIPT => htmlspecialchars_decode($value),
        Settings::WP_USE_TICKET_WHATSAPP_CF_WHEN_SET => $value ?? null,
        Settings::CW_WP_CUSTOM_FIELD_ID => $value ?? null,
        Settings::TICKET_WP_CUSTOM_FIELD_ID => $value ? intval($value) : null,
        Settings::BULK_ENABLE => (bool) $value,
        default => $value
    };

    if (
        in_array($setting, [
            Settings::WP_EVO_WP_NUMBER_CUSTOM_FIELD_ID,
            Settings::WP_CUSTOM_FIELD_ID,
            Settings::BAILEYS_WP_CUSTOM_FIELD_ID,
            Settings::BOTMS_WP_CUSTOM_FIELD_ID,
            Settings::CW_WP_CUSTOM_FIELD_ID,
            Settings::WP_USE_TICKET_WHATSAPP_CF_WHEN_SET,
        ])
    ) {
        $parsed_value = is_numeric($parsed_value) ? (int) $parsed_value : null;
    }

    return $parsed_value;
}

function lkn_hn_config_set(Platforms $platform, Settings $setting, $value)
{
    $result = Capsule::table('mod_dct_hook_notification_configs')
        ->updateOrInsert(
            ['platform' => $platform->value, 'setting' => $setting->value],
            ['value' => $value]
        );

    lkn_hn_log(
        'Upsert setting',
        ['setting' => $setting->name, 'value' => $value],
        ['result' => $result]
    );
}

function lkn_hn_result(
    string $code,
    mixed $data = null,
    ?string $msg = null,
    array $errors = []
): Result {
    return new Result($code, $data, $msg, $errors);
}

function lkn_hn_get_system_locale(): string
{
    $systemLang = Capsule::table('tblconfiguration')
        ->where('setting', 'Language')
        ->first('value')
        ->value;

    /**
     * @var array (
     *     [locale] => en_GB
     *     [language] => english
     *     [languageCode] => en
     *     [countryCode] => GB
     *     [localisedName] => English
     * )[] $clientLocalesList
     */
    $clientLocalesList = ClientLanguage::getLocales();

    $parsedClientLang = current(
        array_filter(
            $clientLocalesList,
            fn(array $item): bool =>
            $item['language'] === $systemLang
        )
    );

    return $parsedClientLang['locale'];
}

/**
 * Human-readable label for a Meta WhatsApp conversation category.
 *
 * Meta categories: marketing, utility, authentication,
 * authentication_international (a higher-priced variant of authentication
 * used for some international routes), service, and referral_conversion.
 *
 * @since 4.5.8
 */
function lkn_hn_wa_category_label(?string $category): string
{
    return match ($category) {
        'marketing' => lkn_hn_lang('Marketing'),
        'utility' => lkn_hn_lang('Utility'),
        'authentication' => lkn_hn_lang('Authentication'),
        'authentication_international' => lkn_hn_lang('Authentication (International)'),
        'service' => lkn_hn_lang('Service'),
        'referral_conversion' => lkn_hn_lang('Referral Conversion'),
        null => lkn_hn_lang('Unknown'),
        default => ucfirst(str_replace('_', ' ', $category)),
    };
}

/**
 * @return array<int, array{label: string, value: string}>
 */
function lkn_hn_wa_category_options(): array
{
    return [
        ['label' => lkn_hn_lang('Marketing'), 'value' => 'marketing'],
        ['label' => lkn_hn_lang('Utility'), 'value' => 'utility'],
        ['label' => lkn_hn_lang('Authentication'), 'value' => 'authentication'],
        ['label' => lkn_hn_lang('Authentication (International)'), 'value' => 'authentication_international'],
        ['label' => lkn_hn_lang('Service'), 'value' => 'service'],
        ['label' => lkn_hn_lang('Referral Conversion'), 'value' => 'referral_conversion'],
    ];
}


{
    /**  @var \WHMCS\Config\Application $whmcsConfig */
    $whmcsConfig = $GLOBALS['whmcsAppConfig'];
    $siteRootUrl = rtrim($GLOBALS['CONFIG']['Domain'], '/');

    return rtrim("$siteRootUrl/" . $whmcsConfig->OffsetGet('customadminpath') . "/$resource", '/');
}

/**
 * Returns the absolute base URL for this module's folder
 * (`{siteRoot}/modules/addons/dct_whatsapp_notifications`).
 *
 * Unlike lkn_hn_get_admin_root_url(), this does NOT use the custom admin
 * path: `modules/addons` is served from the WHMCS site root regardless of
 * where the admin area lives (and regardless of which admin script/page
 * this helper is called from), so it must never be derived from
 * $_SERVER['SCRIPT_NAME'].
 *
 * Uses tblconfiguration.SystemURL (the same source WHMCS itself uses for
 * absolute links, e.g. cron/notification URLs), rather than
 * $GLOBALS['CONFIG']['Domain'], which is not reliably populated with the
 * site's base URL in every WHMCS install/context.
 *
 * @since 4.5.2
 */
function lkn_hn_get_module_root_url(): string
{
    $systemUrl = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');

    if (!$systemUrl) {
        // Last-resort fallback; SystemURL should always be set on a working WHMCS install.
        $systemUrl = $GLOBALS['CONFIG']['Domain'] ?? '';
    }

    return rtrim((string) $systemUrl, '/') . '/modules/addons/dct_whatsapp_notifications';
}

/**
 * Returns the public callback URL Meta must call for the WhatsApp Cloud API
 * status webhook (delivery status + conversation events).
 *
 * @since 4.5.2
 */
function lkn_hn_get_whatsapp_webhook_url(): string
{
    return lkn_hn_get_module_root_url() . '/src/Core/api.php?endpoint=whatsapp/webhook';
}

/**
 * Returns the public callback URL botms.in must call with events (incoming
 * messages, connection status, disconnects, battery, etc).
 *
 * @since 4.5.14
 */
function lkn_hn_get_botms_webhook_url(): string
{
    return lkn_hn_get_module_root_url() . '/src/Core/api.php?endpoint=botms/webhook';
}

function lkn_hn_normalize_person_name(string $name): string
{
    $normalizedName = preg_replace_callback(
        '/\b(\w)(\w*)\b/',
        function ($matches) {
            return ucfirst(strtolower($matches[1])) . strtolower($matches[2]);
        },
        $name
    );

    return trim($normalizedName);
}

function lkn_hn_remove_phone_number(string $value): string
{
    return preg_replace('/[^0-9]/', '', $value);
}

function lkn_hn_safe_json_encode(array $json, int $additionlFlags = 0)
{
    return json_encode(
        $json,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES | $additionlFlags
    );
}

function lkn_hn_redirect_to_404(): void
{
    header(
        'Location: addonmodules.php?module=dct_whatsapp_notifications&page=404'
    );
}

function lkn_hn_mask_value(string $contact): string
{
    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        [$local, $domain] = explode('@', $contact);
        $localLength      = strlen($local);
        /** @var int $maskLength */
        $maskLength  = max(1, floor($localLength / 2));
        $maskedLocal = substr($local, 0, $localLength - $maskLength) . str_repeat('*', $maskLength);

        return $maskedLocal . '@' . $domain;
    } elseif (preg_match('/^\+?[0-9]{10,}$/', $contact)) {
        $visibleDigits = 4;
        $maskedLength  = strlen($contact) - $visibleDigits;
        return str_repeat('*', $maskedLength) . substr($contact, -$visibleDigits);
    } else {
        return str_repeat('*', strlen($contact) - 4) . substr($contact, -4);
    }
};

/**
 * Strips everything but digits from a phone number, so numbers coming from
 * different sources (Meta's `from`/`recipient_id`, a stored `target`, a
 * WHMCS client's phone field) can be reliably compared regardless of `+`,
 * spaces, dashes or parentheses.
 *
 * @since 4.5.5
 */
function lkn_hn_normalize_phone_digits(?string $phone): ?string
{
    if ($phone === null || $phone === '') {
        return null;
    }

    $digits = preg_replace('/\D+/', '', $phone);

    return $digits === '' ? null : $digits;
}



/**
 * Fetches Meta's approved WhatsApp templates in the "AUTHENTICATION"
 * category, formatted as select options for the "2FA Authentication
 * Template Name" setting - lets the admin pick from a dropdown instead of
 * typing the exact template name by hand. Returns an empty array (with a
 * placeholder option) if Meta isn't configured yet or the call fails, so
 * the settings page never breaks even without credentials in place.
 *
 * @since 4.6.1
 *
 * @return array<int, array{value: string, label: string}>
 */
function lkn_hn_fetch_meta_authentication_templates(): array
{
    try {
        $settings = \Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory::makeMetaWhatsAppSettings();

        if (empty($settings->userAccessToken) || empty($settings->businessAccountId)) {
            return [];
        }

        $client = (new \Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory())
            ->makeMetaWhatsAppClient($settings);

        $response = $client->getMessageTemplates([
            'fields' => 'name,language,category,status',
            'limit' => 250,
        ]);

        $templates = $response->body['data'] ?? [];

        $authTemplates = array_values(array_filter(
            $templates,
            fn (array $tpl) => ($tpl['category'] ?? '') === 'AUTHENTICATION' && ($tpl['status'] ?? '') === 'APPROVED'
        ));

        return array_map(
            fn (array $tpl) => [
                // Encodes both name AND language into the value (pipe-delimited):
                // Meta requires the language code sent with a template to exactly
                // match what it was approved under (e.g. "en_US"), which is NOT
                // necessarily the same as this addon's own default notification
                // language setting - storing just the name and falling back to
                // that default elsewhere caused sends to fail with "template
                // name does not exist in <wrong language>".
                'value' => $tpl['name'] . '|' . ($tpl['language'] ?? 'en_US'),
                'label' => $tpl['name'] . ' (' . ($tpl['language'] ?? '?') . ')',
            ],
            $authTemplates
        );
    } catch (\Throwable $th) {
        lkn_hn_log('2FA: failed to fetch Meta Authentication templates', [], ['exception' => $th->__toString()]);

        return [];
    }
}

/**
 * Aligns PHP's default timezone with WHMCS's own configured timezone
 * (Setup > General Settings > Localisation > Timezone), so every
 * date()/DateTime call this module makes afterwards matches the times
 * WHMCS itself displays (emails, admin logs, etc), instead of silently
 * using the server's own default timezone (often UTC).
 *
 * Called once per request, as early as possible - see entrypoint.php.
 *
 * @since 4.6.3
 */
function lkn_hn_apply_whmcs_timezone(): void
{
    static $applied = false;

    if ($applied) {
        return;
    }

    $applied = true;

    try {
        $whmcsTimezone = Capsule::table('tblconfiguration')
            ->where('setting', 'Timezone')
            ->value('value');

        if ($whmcsTimezone && in_array($whmcsTimezone, timezone_identifiers_list(), true)) {
            date_default_timezone_set($whmcsTimezone);
        }
    } catch (\Throwable $th) {
        // If this fails for any reason (e.g. DB not reachable yet at this
        // point in the request), leave PHP's timezone as whatever it
        // already was rather than risk breaking the whole request over a
        // display-formatting concern.
        if (function_exists('lkn_hn_log')) {
            lkn_hn_log('Failed to apply WHMCS timezone', [], ['exception' => $th->__toString()]);
        }
    }
}

/**
 * Forces the shared WHMCS database connection to use utf8mb4 for the
 * current request, regardless of whatever charset it negotiated by
 * default. Most emoji need 4-byte UTF-8 storage - if the connection itself
 * (not just the destination table) isn't using utf8mb4, MySQL silently
 * substitutes "?" for each byte it can't transmit, before the data ever
 * reaches the table - so even a correctly-utf8mb4 table doesn't help if
 * the connection carrying the data to it isn't.
 *
 * utf8mb4 is a superset of utf8/latin1 (can represent everything they can,
 * plus more), so raising the connection's charset here is safe for
 * WHMCS's own queries running later in the same request too - this
 * shouldn't be able to break anything that worked before.
 *
 * Called once per request, as early as possible - see entrypoint.php.
 *
 * @since 5.1.3
 */
function lkn_hn_apply_utf8mb4_connection(): void
{
    static $applied = false;

    if ($applied) {
        return;
    }

    $applied = true;

    try {
        Capsule::connection()->statement('SET NAMES utf8mb4');
    } catch (\Throwable $th) {
        if (function_exists('lkn_hn_log')) {
            lkn_hn_log('Failed to set utf8mb4 connection charset', [], ['exception' => $th->__toString()]);
        }
    }
}
