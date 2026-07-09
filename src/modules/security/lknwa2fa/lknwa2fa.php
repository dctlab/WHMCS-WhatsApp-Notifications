<?php

/**
 * WhatsApp Verification - a WHMCS Two-Factor Authentication module
 * (Setup > Security > Two-Factor Authentication).
 *
 * Sends a numeric verification code over WhatsApp on every login, for both
 * client users and administrative users, reusing the WhatsApp sending
 * infrastructure already configured in the "lknhooknotification" addon
 * module. Supports Botms.in and Baileys directly (they can send free-form
 * text at any time), and Meta via a separately configured "Authentication"
 * category template (Meta requires a pre-approved template for an
 * unsolicited message like a login code - see Settings -> WhatsApp Meta ->
 * "2FA Authentication Template Name" in that addon).
 *
 * IMPORTANT: this module relies on the modules/security/ interface WHMCS
 * itself uses for its built-in Time-Based Tokens/Duo/YubiKey methods, which
 * is NOT published in WHMCS's official developer documentation - it was
 * reconstructed from a working third-party reference implementation. Test
 * this thoroughly end-to-end (activation AND login, for both an admin and a
 * client account) in a staging copy of your WHMCS install before enabling
 * it for real users, and before making it "Required" for anyone. A mistake
 * in authentication code can lock people out or, worse, create a bypass -
 * this has not been tested against a live WHMCS instance.
 *
 * Installation: copy this whole `lknwa2fa` folder into modules/security/
 * (i.e. alongside modules/addons/lknhooknotification, not inside it), then
 * go to Setup > Security > Two-Factor Authentication and Activate
 * "WhatsApp Verification".
 *
 * @since 4.6.0
 */

use Illuminate\Database\Capsule\Manager as Capsule;

const LKN_WA2FA_SENT_TABLE = 'mod_lkn_wa2fa_users';
const LKN_WA2FA_CODES_TABLE = 'mod_lkn_wa2fa_codes';
const LKN_WA2FA_CODE_TTL_SECONDS = 300; // 5 minutes
const LKN_WA2FA_RESEND_COOLDOWN_SECONDS = 60;
const LKN_WA2FA_MAX_ATTEMPTS = 5;

function lknwa2fa_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'WhatsApp Verification',
        ],
        'ShortDescription' => [
            'Type' => 'System',
            'Value' => 'Two-Factor Authentication via WhatsApp',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Sends a numeric verification code to your WhatsApp number every time you log in. '
                . 'Requires the "lknhooknotification" addon module to be installed, with at least one of: '
                . 'Botms.in enabled, Baileys enabled, or Meta enabled with a "2FA Authentication Template Name" '
                . 'configured (Settings -> WhatsApp Meta in that addon - Meta requires an approved Authentication '
                . 'template to send an unsolicited code like this).',
        ],
    ];
}

/**
 * Loads the lknhooknotification addon's autoloader/helpers so this module
 * can reuse its WhatsApp sending infrastructure, and makes sure this
 * module's own small tracking tables exist.
 */
function lknwa2fa_bootstrap(): void
{
    static $booted = false;

    if ($booted) {
        return;
    }

    $booted = true;

    $addonDir = __DIR__ . '/../../addons/lknhooknotification';

    require_once $addonDir . '/vendor/autoload.php';
    require_once $addonDir . '/src/Core/Shared/Infrastructure/helpers.php';

    if (!Capsule::schema()->hasTable(LKN_WA2FA_SENT_TABLE)) {
        Capsule::schema()->create(LKN_WA2FA_SENT_TABLE, function ($table) {
            $table->increments('id');
            $table->string('user_type', 10); // 'admin' or 'client'
            $table->unsignedInteger('user_id');
            $table->string('phone_number', 32);
            $table->boolean('enabled')->default(true);
            $table->dateTime('created_at');
            $table->unique(['user_type', 'user_id']);
        });
    }

    if (!Capsule::schema()->hasTable(LKN_WA2FA_CODES_TABLE)) {
        Capsule::schema()->create(LKN_WA2FA_CODES_TABLE, function ($table) {
            $table->increments('id');
            $table->string('user_type', 10);
            $table->unsignedInteger('user_id');
            $table->string('code_hash', 64);
            $table->dateTime('expires_at');
            $table->boolean('used')->default(false);
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('created_at');
            $table->index(['user_type', 'user_id']);
        });
    }
}

/**
 * Determines whether the current login is an admin or a client, and their
 * id - WHMCS does not pass this explicitly to these module functions, so
 * this relies on which session variable is present. This is the same
 * signal WHMCS's own login flow relies on internally.
 *
 * @return array{type: string, id: int}
 */
function lknwa2fa_context(?int $fallbackUserId = null): array
{
    if (!empty($_SESSION['adminid'])) {
        return ['type' => 'admin', 'id' => (int) $_SESSION['adminid']];
    }

    if (!empty($_SESSION['uid'])) {
        return ['type' => 'client', 'id' => (int) $_SESSION['uid']];
    }

    // Fallback for the challenge/verify steps, if the session marker isn't
    // set at that point in a given WHMCS version: infer from which table
    // actually has this id. Not perfectly collision-proof if both an admin
    // and a client happen to share the same numeric id, but is the best
    // available signal without an explicit type from WHMCS.
    if ($fallbackUserId !== null) {
        $isAdmin = Capsule::table('tbladmins')->where('id', $fallbackUserId)->exists();

        return ['type' => $isAdmin ? 'admin' : 'client', 'id' => $fallbackUserId];
    }

    return ['type' => 'client', 'id' => (int) ($fallbackUserId ?? 0)];
}

/**
 * Shown when a user clicks "Get Started" to enable this method on their
 * own account (Client Area > Security Settings, or Admin > My Account).
 */
function lknwa2fa_activate($params)
{
    lknwa2fa_bootstrap();

    $context = lknwa2fa_context();
    $prefillPhone = lknwa2fa_resolve_default_phone($context);

    $prefillPhone = htmlspecialchars((string) $prefillPhone, ENT_QUOTES);

    return <<<HTML
        <p>Enter the WhatsApp number that should receive your login verification codes. Include the country code, no spaces or symbols (e.g. 15551234567).</p>
        <div class="form-group">
            <label for="lknwa2fa_phone">WhatsApp Number</label>
            <input type="text" class="form-control" name="phone_number" id="lknwa2fa_phone" value="{$prefillPhone}" required>
        </div>
        <hr>
        <input type="submit" value="Enable WhatsApp Verification" class="btn btn-primary">
        HTML;
}

/**
 * Handles the submission of the activation form above.
 */
function lknwa2fa_activateverify($params)
{
    lknwa2fa_bootstrap();

    $context = lknwa2fa_context();
    $phoneNumber = preg_replace('/\D+/', '', (string) ($params['post_vars']['phone_number'] ?? ''));

    if ($phoneNumber === '') {
        return ['msg' => 'Please enter a valid WhatsApp number.'];
    }

    Capsule::table(LKN_WA2FA_SENT_TABLE)->updateOrInsert(
        ['user_type' => $context['type'], 'user_id' => $context['id']],
        [
            'phone_number' => $phoneNumber,
            'enabled' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]
    );

    return [
        'msg' => 'WhatsApp Verification enabled. You will receive a code by WhatsApp the next time you log in.',
        'settings' => ['phone_number' => $phoneNumber],
    ];
}

/**
 * Shown after a correct username/password, before granting access: sends
 * (or re-sends, respecting a cooldown) the code, and shows the entry form.
 */
function lknwa2fa_challenge($params)
{
    lknwa2fa_bootstrap();

    $userId  = (int) ($params['user_info']['id'] ?? 0);
    $context = lknwa2fa_context($userId);

    $userRow = Capsule::table(LKN_WA2FA_SENT_TABLE)
        ->where('user_type', $context['type'])
        ->where('user_id', $context['id'])
        ->first();

    if ($userRow === null || !$userRow->enabled) {
        return '<div class="alert alert-danger">WhatsApp Verification is not set up for this account.</div>';
    }

    $latestCode = Capsule::table(LKN_WA2FA_CODES_TABLE)
        ->where('user_type', $context['type'])
        ->where('user_id', $context['id'])
        ->where('used', false)
        ->orderBy('created_at', 'desc')
        ->first();

    $secondsSinceLastSend = $latestCode
        ? (time() - strtotime($latestCode->created_at))
        : LKN_WA2FA_RESEND_COOLDOWN_SECONDS + 1;

    if (!$latestCode || $secondsSinceLastSend >= LKN_WA2FA_RESEND_COOLDOWN_SECONDS || strtotime($latestCode->expires_at) < time()) {
        $code = (string) random_int(100000, 999999);

        Capsule::table(LKN_WA2FA_CODES_TABLE)->insert([
            'user_type' => $context['type'],
            'user_id' => $context['id'],
            'code_hash' => hash('sha256', $code),
            'expires_at' => date('Y-m-d H:i:s', time() + LKN_WA2FA_CODE_TTL_SECONDS),
            'used' => false,
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        lknwa2fa_send_code($userRow->phone_number, $code);

        $secondsSinceLastSend = 0;
    }

    $cooldownRemaining = max(0, LKN_WA2FA_RESEND_COOLDOWN_SECONDS - $secondsSinceLastSend);

    return <<<HTML
        <form action="dologin.php" method="post">
            <div class="form-group text-center">
                <p>We sent a 6-digit code to your WhatsApp number ending in {$userRow->phone_number}.</p>
                <input
                    type="text"
                    name="lknwa2fa_code"
                    class="form-control text-center"
                    style="font-size: 24px; letter-spacing: 4px;"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    autofocus
                    required
                >
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify</button>
        </form>
        <p class="text-muted text-center" style="margin-top: 10px;" id="lknwa2fa-resend-note">
            {$cooldownRemaining}
        </p>
        <script>
            (function () {
                var remaining = {$cooldownRemaining};
                var el = document.getElementById('lknwa2fa-resend-note');
                function tick() {
                    if (remaining <= 0) {
                        el.innerHTML = 'Didn\\'t get it? <a href="javascript:location.reload()">Send a new code</a>';
                        return;
                    }
                    el.textContent = 'You can request a new code in ' + remaining + 's';
                    remaining -= 1;
                    setTimeout(tick, 1000);
                }
                tick();
            })();
        </script>
        HTML;
}

/**
 * Verifies the code the user typed on the challenge screen above.
 */
function lknwa2fa_verify($params)
{
    lknwa2fa_bootstrap();

    $userId    = (int) ($params['user_info']['id'] ?? 0);
    $context   = lknwa2fa_context($userId);
    $submitted = preg_replace('/\D+/', '', (string) ($params['post_vars']['lknwa2fa_code'] ?? ''));

    if ($submitted === '') {
        return false;
    }

    $codeRow = Capsule::table(LKN_WA2FA_CODES_TABLE)
        ->where('user_type', $context['type'])
        ->where('user_id', $context['id'])
        ->where('used', false)
        ->orderBy('created_at', 'desc')
        ->first();

    if ($codeRow === null) {
        return false;
    }

    if (strtotime($codeRow->expires_at) < time()) {
        return false;
    }

    if ($codeRow->attempts >= LKN_WA2FA_MAX_ATTEMPTS) {
        return false;
    }

    Capsule::table(LKN_WA2FA_CODES_TABLE)
        ->where('id', $codeRow->id)
        ->increment('attempts');

    $isCorrect = hash_equals($codeRow->code_hash, hash('sha256', $submitted));

    if ($isCorrect) {
        Capsule::table(LKN_WA2FA_CODES_TABLE)
            ->where('id', $codeRow->id)
            ->update(['used' => true]);
    }

    return $isCorrect;
}

/**
 * Resolves a default phone number to pre-fill on the activation form: the
 * client's own WHMCS phone number, or blank for admins (WHMCS admin
 * accounts don't have a standard phone field).
 */
function lknwa2fa_resolve_default_phone(array $context): string
{
    if ($context['type'] !== 'client') {
        return '';
    }

    $phone = Capsule::table('tblclients')->where('id', $context['id'])->value('phonenumber');

    return $phone ? preg_replace('/\D+/', '', $phone) : '';
}

/**
 * Sends the code via whichever WhatsApp platform is enabled in the
 * lknhooknotification addon - tries Botms.in first, then Baileys, since
 * both can send free-form text at any time (unlike Meta, which needs a
 * pre-approved Authentication template for an unsolicited message like this).
 */
function lknwa2fa_send_code(string $phoneNumber, string $code): bool
{
    $message = "Your verification code is: {$code}\n\nThis code expires in 5 minutes. Do not share it with anyone.";

    try {
        if (function_exists('lkn_hn_config')) {
            $templateName = lkn_hn_config(\Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings::WP_2FA_TEMPLATE_NAME);

            if (lkn_hn_config(\Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings::WP_META_ENABLE) && !empty($templateName)) {
                if (lknwa2fa_send_via_meta($phoneNumber, $code, $templateName)) {
                    return true;
                }
            }
        }
    } catch (\Throwable $th) {
        lkn_hn_log('WA2FA: Meta send error', [], ['exception' => $th->__toString()]);
    }

    try {
        if (function_exists('lkn_hn_config') && lkn_hn_config(\Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings::BOTMS_ENABLE)) {
            $settings = \Lkn\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory::makeBotmsSettings([]);
            $client   = (new \Lkn\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory())->makeBotmsClient($settings);

            if ($client->areSettingsFilled()) {
                $response = $client->sendTextMessage($phoneNumber, $message);

                if ($response->httpStatusCode >= 200 && $response->httpStatusCode < 300) {
                    return true;
                }
            }
        }
    } catch (\Throwable $th) {
        lkn_hn_log('WA2FA: Botms send error', [], ['exception' => $th->__toString()]);
    }

    try {
        if (function_exists('lkn_hn_config') && lkn_hn_config(\Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings::BAILEYS_ENABLE)) {
            $settings = \Lkn\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory::makeBaileysSettings([]);
            $client   = (new \Lkn\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory())->makeBaileysClient($settings);

            $response = $client->sendTextMessage($phoneNumber, $message);

            if (isset($response->httpStatusCode) && $response->httpStatusCode >= 200 && $response->httpStatusCode < 300) {
                return true;
            }
        }
    } catch (\Throwable $th) {
        lkn_hn_log('WA2FA: Baileys send error', [], ['exception' => $th->__toString()]);
    }

    if (function_exists('lkn_hn_log')) {
        lkn_hn_log('WA2FA: no platform available to send code', ['phoneNumber' => $phoneNumber], []);
    }

    return false;
}

/**
 * Sends the code via Meta's WhatsApp Cloud API, using the admin-configured
 * "Authentication" category template (Settings::WP_2FA_TEMPLATE_NAME).
 *
 * Meta does not allow an unsolicited free-text message like a login code -
 * it must go through a pre-approved template. Authentication templates have
 * a mostly-fixed body Meta controls, with just the code as {{1}}, and
 * optionally a "Copy Code" button (also carrying the code as a parameter,
 * if Settings::WP_2FA_TEMPLATE_HAS_BUTTON is enabled - only turn that on if
 * your specific approved template actually has that button).
 */
function lknwa2fa_send_via_meta(string $phoneNumber, string $code, string $templateName): bool
{
    $settings = \Lkn\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory::makeMetaWhatsAppSettings();
    $client   = (new \Lkn\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory())->makeMetaWhatsAppClient($settings);

    $components = [
        ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
    ];

    if (lkn_hn_config(\Lkn\HookNotification\Core\Shared\Infrastructure\Config\Settings::WP_2FA_TEMPLATE_HAS_BUTTON)) {
        $components[] = [
            'type' => 'button',
            'sub_type' => 'copy_code',
            'index' => '0',
            'parameters' => [['type' => 'coupon_code', 'coupon_code' => $code]],
        ];
    }

    $langCode = $settings->defaultMsgTemplateLang ?: 'en';

    $response = $client->sendMessageTemplate($phoneNumber, $templateName, $components, $langCode);

    return isset($response->httpStatusCode) && $response->httpStatusCode >= 200 && $response->httpStatusCode < 300;
}
