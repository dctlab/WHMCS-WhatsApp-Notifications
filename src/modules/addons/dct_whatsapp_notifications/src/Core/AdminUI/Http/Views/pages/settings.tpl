{* https://getbootstrap.com/docs/3.4/css/#forms-example *}
{extends "layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="[1] Settings" params=[$page_params.platform_title]}
{/block}

{block "page_content"}
    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{$page_params.platform_title}</h1>
            <div class="dct-page-header-description">{lkn_hn_lang text="Settings"}</div>
        </div>
    </div>

    <form
        id="lkn-hn-settings-form"
        class="dct-form"
        method="post"
        target="_self"
    >
        <input
            type="hidden"
            name="placeholder"
            value="placeholder"
        >
        <div class="row">
            <div
                {if $platform_settings_controller_output}
                    class="col-md-6"
                {else}
                    class="col-md-12"
                {/if}
            >
                {foreach from=$page_params.settings_df item=$setting}
                    {if isset($setting['hide'])}

                    {elseif isset($setting['separator'])}
                        <div class="dct-section-title" style="margin-top: 24px;">{$setting['title']}</div>
                        <div class="dct-form-help" style="margin-bottom: 12px;">
                            {$setting['description']}
                        </div>
                    {elseif in_array($setting['type'], ['text', 'password', 'url', 'number'])}
                        <div class="dct-card">
                            <div class="dct-card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label
                                            for="{$setting['id']}"
                                            class="dct-form-label"
                                            style="font-size: 14px;"
                                        >
                                            {$setting['label']}
                                            {if isset($setting['popover-config'])}
                                                <span
                                                    tabindex="0"
                                                    role="button"
                                                    data-toggle="popover"
                                                    data-trigger="hover click"
                                                    title="{$setting['popover-config']['popover-title']}"
                                                    data-content="
                                                            {foreach $setting['popover-config']['popover-images'] item=$images}
                                                                <img ' src='{$lkn_hn.system_url}modules/addons/dct_whatsapp_notifications/src/Core/assets/{$images['popover-img']}' width='{$images['popover-width']} style='text-aling:center; margin-bottom:10px;' alt='Imagem'>
                                                            {/foreach}

                                                        {if isset($setting['popover-config']['popover-description'])}
                                                            <p> {$setting['popover-config']['popover-description']}</p>
                                                        {/if}"
                                                    data-html="true"
                                                ><i class="fas fa-question-circle"></i></span>
                                            {/if}
                                        </label>

                                        <div class="dct-form-help">
                                            {$setting['description']}
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <input
                                            type="{$setting['type']}"
                                            class="dct-input"
                                            id="{$setting['id']}"
                                            name="{$setting['id']}"
                                            {if $setting['type'] === 'password'}
                                                value=""
                                                autocomplete="new-password"
                                            {else}
                                                value="{$setting['current']}"
                                            {/if}
                                        >
                                        {if $setting['type'] === 'password' && $setting['current']}
                                            {* Never render the actual stored value here - only a
                                               boolean check (is something saved at all) decides
                                               whether this help text shows, the value itself is
                                               never printed anywhere in this branch. *}
                                            <div class="dct-form-help">
                                                {lkn_hn_lang text="A value is already saved. Leave blank to keep the current value."}
                                            </div>
                                        {elseif $setting['type'] === 'password'}
                                            <div class="dct-form-help">
                                                {lkn_hn_lang text="No value saved yet."}
                                            </div>
                                        {/if}
                                        {if $setting['below_field']}
                                            <div class="dct-form-help">{$setting['below_field']['title']}</div>
                                            <pre style='max-width: 400px;'>{$setting['below_field']['code']}</pre>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {elseif $setting['type'] === 'textarea'}
                        <div class="dct-card">
                            <div class="dct-card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label
                                            for="{$setting['id']}"
                                            class="dct-form-label"
                                            style="font-size: 14px;"
                                        >
                                            {$setting['label']}
                                            {if isset($setting['popover-config'])}
                                                <span
                                                    tabindex="0"
                                                    role="button"
                                                    data-toggle="popover"
                                                    data-trigger="hover click"
                                                    title="{$setting['popover-config']['popover-title']}"
                                                    data-content="
                                                            {foreach $setting['popover-config']['popover-images'] item=$images}
                                                                <img ' src='{$lkn_hn.system_url}modules/addons/dct_whatsapp_notifications/src/Core/assets/{$images['popover-img']}' width='{$images['popover-width']} style='text-aling:center; margin-bottom:10px;' alt='Imagem'>
                                                            {/foreach}

                                                        {if isset($setting['popover-config']['popover-description'])}
                                                            <p> {$setting['popover-config']['popover-description']}</p>
                                                        {/if}"
                                                    data-html="true"
                                                ><i class="fas fa-question-circle"></i></span>
                                            {/if}
                                        </label>

                                        <div class="dct-form-help">
                                            {$setting['description']}
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <textarea
                                            class="dct-textarea"
                                            id="{$setting['id']}"
                                            name="{$setting['id']}"
                                            rows="3"
                                            style="font-family: monospace; resize: none; min-height: 350px; max-height: 350px;"
                                        >{$setting['current']}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {elseif in_array($setting['type'], ['select', 'multiple'])}
                        <div class="dct-card">
                            <div class="dct-card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label
                                            for="{$setting['id']}"
                                            class="dct-form-label"
                                            style="font-size: 14px;"
                                        >
                                            {$setting['label']}
                                            {if isset($setting['popover-config'])}
                                                <span
                                                    tabindex="0"
                                                    role="button"
                                                    data-toggle="popover"
                                                    data-trigger="hover click"
                                                    title="{$setting['popover-config']['popover-title']}"
                                                    data-content="
                                                            {foreach $setting['popover-config']['popover-images'] item=$images}
                                                                <img ' src='{$lkn_hn.system_url}modules/addons/dct_whatsapp_notifications/src/Core/assets/{$images['popover-img']}' width='{$images['popover-width']} style='text-aling:center; margin-bottom:10px;' alt='Imagem'>
                                                            {/foreach}

                                                        {if isset($setting['popover-config']['popover-description'])}
                                                            <p> {$setting['popover-config']['popover-description']}</p>
                                                        {/if}"
                                                    data-html="true"
                                                ><i class="fas fa-question-circle"></i></span>
                                            {/if}
                                        </label>

                                        <div class="dct-form-help">
                                            {$setting['description']}

                                            {if isset($setting['description_link'])}
                                                <a href="{$setting['description_link']['link']}">
                                                    {$setting['description_link']['label']}
                                                </a>
                                            {/if}
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <select
                                            id="{$setting['id']}"
                                            class="dct-select"
                                            {if $setting['type'] === 'multiple'}
                                                multiple
                                                name="{$setting['id']}[]"
                                            {else}
                                                name="{$setting['id']}"
                                            {/if}
                                        >
                                            {if isset($setting['default']) && is_array($setting['default'])}
                                                <option value="{$setting['default']['value']}">
                                                    {$setting['default']['label']}
                                                </option>
                                            {/if}

                                            {if $setting['options'] === 'lkn_hn_locales'}
                                                {foreach from=$lkn_hn_locales item=$locale}
                                                    <option
                                                        value="{$locale['value']}"
                                                        {if $setting['current'] == $locale['value']}
                                                            selected
                                                        {/if}
                                                    >
                                                        {$locale['label']}
                                                    </option>
                                                {/foreach}
                                            {elseif $setting['options'] === 'lkn_hn_custom_fields'}
                                                {foreach from=$lkn_hn_custom_fields item=$settingOption}
                                                    <option
                                                        value="{$settingOption['value']}"
                                                        {if $setting['type'] === 'multiple'}
                                                            {if $setting['current'] && in_array($settingOption['value'], $setting['current'])}
                                                                selected
                                                            {/if}
                                                        {else}
                                                            {if $setting['current'] == $settingOption['value']}
                                                                selected
                                                            {/if}
                                                        {/if}
                                                    >
                                                        {$settingOption['value']} - {$settingOption['label']}
                                                    </option>
                                                {/foreach}
                                            {else}
                                                {foreach from=$setting['options'] item=$settingOption}
                                                    <option
                                                        value="{$settingOption['value']}"
                                                        {if $setting['type'] === 'multiple'}
                                                            {if $setting['current'] && in_array($settingOption['value'], $setting['current'])}
                                                                selected
                                                            {/if}
                                                        {else}
                                                            {if $setting['current'] == $settingOption['value']}
                                                                selected
                                                            {/if}
                                                        {/if}
                                                    >
                                                        {$settingOption['label']}
                                                    </option>
                                                {/foreach}
                                            {/if}
                                        </select>
                                        {if isset($setting['description_right_link'])}
                                            <div class="dct-form-help">
                                                <a
                                                    href="{$setting['description_right_link']['link']}"
                                                    target="_blank"
                                                >
                                                    {$setting['description_right_link']['label']}
                                                </a>
                                            </div>
                                        {else isset($setting['description_link'])}
                                            <div class="dct-form-help">
                                                <a
                                                    href="{$setting['description_link']['link']}"
                                                    target="_blank"
                                                >
                                                    {$setting['description_link']['label']}
                                                </a>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {else}
                        <div class="dct-card">
                            <div class="dct-card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <label
                                            for="{$setting['id']}"
                                            class="dct-form-label"
                                            style="font-size: 14px;"
                                        >
                                            {$setting['label']}
                                        </label>

                                        <div class="dct-form-help">
                                            {$setting['description']}
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                            <input
                                                id="{$setting['id']}"
                                                name="{$setting['id']}"
                                                type="checkbox"
                                                {if $setting['current']}
                                                    checked
                                                {/if}
                                            >
                                            {$setting['label']}
                                        </label>

                                        {if !empty($setting['warning_on_unchecked']) && !$setting['current']}
                                            <div class="dct-alert dct-alert-warning" style="margin-top: 8px;">
                                                {$setting['warning_on_unchecked']}
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/if}
                {/foreach}

                <div style="margin-top: 30px;">
                    <button
                        type="submit"
                        class="dct-button dct-button-primary"
                        onclick="return confirmSubmit('{lkn_hn_lang text="Are you sure? The settings will take effect immediately." params=[$page_params.platform_title]}')"
                    >
                        {lkn_hn_lang text="Save Settings"}
                    </button>
                </div>
            </div>

            {if $platform_settings_controller_output}
                <div class="col-md-6">
                    {$platform_settings_controller_output}
                </div>
            {/if}
        </div>
    </form>
{/block}
