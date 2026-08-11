{if $error}
    <div class="alert alert-danger">{$error}</div>
{else}
    {if $saved}
        <div class="alert alert-success">
            {$LANG.lknhnprefssaved|default:"Your WhatsApp notification preferences have been saved."}
        </div>
    {/if}

    <form method="post" action="index.php?m=dct_whatsapp_notifications">
        <input type="hidden" name="lkn_hn_save_prefs" value="1">

        <div class="panel panel-default">
            <div class="panel-heading">
                <strong>{$LANG.lknhnenablewhatsapp|default:"Enable WhatsApp Alerts?"}</strong>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    {$LANG.lknhnenablewhatsappdesc|default:"We'll send you alerts via WhatsApp."}
                </p>

                <input
                    type="checkbox"
                    name="whatsapp_enabled"
                    id="lkn_hn_whatsapp_enabled"
                    value="1"
                    {if $whatsapp_enabled}checked{/if}
                >
            </div>
        </div>

        <div class="panel panel-default" id="lkn_hn_prefs_panel" {if !$whatsapp_enabled}style="opacity: 0.5;"{/if}>
            <div class="panel-heading">
                <strong>{$LANG.lknhnpreferences|default:"WhatsApp Preferences"}</strong>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    {$LANG.lknhnpreferencesdesc|default:"Choose which types of WhatsApp messages you'd like to receive. Unchecking a box turns that specific type off, even if the master toggle above is on."}
                </p>

                <div class="row">
                    {foreach from=$notification_types item=$notifType name=notifLoop}
                        <input type="hidden" name="all_notification_codes[]" value="{$notifType.code}">
                        {if $smarty.foreach.notifLoop.iteration % 2 == 1}<div class="col-sm-6">{/if}
                            <div class="checkbox">
                                <label>
                                    <input
                                        type="checkbox"
                                        name="enabled_notifications[]"
                                        value="{$notifType.code}"
                                        {if !in_array($notifType.code, $disabled_notifications)}checked{/if}
                                    >
                                    {$notifType.label}
                                </label>
                            </div>
                        {if $smarty.foreach.notifLoop.iteration % 2 == 0 || $smarty.foreach.notifLoop.last}</div>{/if}
                    {foreachelse}
                        <div class="col-sm-12 text-muted">
                            {$LANG.lknhnnonotiftypes|default:"No notification types are currently available."}
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            {$LANG.lknhnsavechanges|default:"Save Changes"}
        </button>
    </form>

    <script>
        (function () {
            function initSwitch() {
                var $toggle = jQuery('#lkn_hn_whatsapp_enabled');
                var prefsPanel = document.getElementById('lkn_hn_prefs_panel');

                // bootstrapSwitch() generates its own wrapper markup around the
                // input (bootstrap-switch / bootstrap-switch-wrapper /
                // bootstrap-switch-small / bootstrap-switch-animate /
                // bootstrap-switch-on|off) - don't hand-write that structure,
                // the plugin builds and maintains it.
                $toggle.bootstrapSwitch({
                    size: 'small',
                    onText: '{$LANG.lknhnyes|default:"YES"}',
                    offText: '{$LANG.lknhnno|default:"NO"}',
                    onColor: 'primary'
                });

                $toggle.on('switchChange.bootstrapSwitch', function (event, state) {
                    if (prefsPanel) {
                        prefsPanel.style.opacity = state ? '1' : '0.5';
                    }
                });
            }

            // Use the theme's own jQuery/bootstrap-switch if already loaded
            // (most WHMCS admin themes, and many client themes, already bundle
            // this exact plugin) - only fetch what's missing from a CDN.
            function loadScript(src, onload) {
                var script = document.createElement('script');
                script.src = src;
                script.onload = onload;
                document.body.appendChild(script);
            }

            function loadSwitchPlugin() {
                var cssLink = document.createElement('link');
                cssLink.rel = 'stylesheet';
                cssLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/css/bootstrap3/bootstrap-switch.min.css';
                document.head.appendChild(cssLink);

                loadScript('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/js/bootstrap-switch.min.js', initSwitch);
            }

            if (window.jQuery && jQuery.fn.bootstrapSwitch) {
                initSwitch();
            } else if (window.jQuery) {
                loadSwitchPlugin();
            } else {
                loadScript('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', loadSwitchPlugin);
            }
        })();
    </script>
{/if}
