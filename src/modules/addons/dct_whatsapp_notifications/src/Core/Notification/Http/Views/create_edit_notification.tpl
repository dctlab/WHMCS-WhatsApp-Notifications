{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Editing Notification [1] - [2]" params=[{lkn_hn_lang text=$page_params.editing_notification->code},
    $page_params.editing_template->lang]}
{/block}

{block "page_content"}
    <div class="dct-breadcrumb">
        <a href="{$lkn_hn_base_endpoint}&page=notifications"><i class="far fa-arrow-left"></i> {lkn_hn_lang text="Notifications"}</a>
    </div>

    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{lkn_hn_lang text=$page_params.editing_notification->code}</h1>
            <div class="dct-page-header-description">{lkn_hn_lang text="WhatsApp Template"}</div>
        </div>
    </div>

    <div style="max-width: 640px;">
        <form
            id="notification-form"
            class="dct-form"
            method="POST"
            target="_self"
        >
            {* ===== Language ===== *}
            <div class="dct-card">
                <div class="dct-card-body">
                    <div class="dct-form-group">
                        <label for="locale" class="dct-form-label" style="font-size: 14px;">{lkn_hn_lang text='Language'}</label>
                        <div class="dct-form-help" style="margin-top: -2px; margin-bottom: 8px;">
                            {lkn_hn_lang text='This template will only be sent to clients with the same language as defined below.'}
                        </div>
                        <select
                            id="locale"
                            name="locale"
                            class="dct-select"
                            {if $page_params.editing_template}
                                readonly
                            {else}
                                onchange="document.getElementById('notification-form').submit()"
                            {/if}
                        >
                            <option value="">{lkn_hn_lang text="Select a language"}</option>

                            {foreach from=$lkn_hn_locales item=$locale}
                                <option
                                    value="{$locale['value']}"
                                    {if $page_params.editing_locale === $locale['value']}
                                        selected
                                    {/if}
                                >
                                    {$locale['label']}
                                </option>
                            {/foreach}
                        </select>

                        {if $page_params.editing_template}
                            <div style="margin-top: 8px;">
                                <a
                                    class="dct-button dct-button-ghost dct-text-small"
                                    href="{$lkn_hn_base_endpoint}&page=notifications/{$page_params.editing_notification->code}/templates/new"
                                    target="_blank"
                                >
                                    <i class="fas fa-plus"></i>
                                    {lkn_hn_lang text="Setup another language"}
                                </a>
                            </div>
                        {/if}
                    </div>

                    {if $page_params.request_locale_selection}
                        <div class="dct-alert dct-alert-info" style="margin-top: 16px;">
                            <i class="far fa-info-circle"></i>
                            {lkn_hn_lang text="Please, choose a language."}
                        </div>
                    {else}
                        {* ===== Platform ===== *}
                        <div class="dct-form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--dct-border-light);">
                            <label for="platform" class="dct-form-label" style="font-size: 14px;">{lkn_hn_lang text='Provider'}</label>
                            <select
                                id="platform"
                                name="platform"
                                class="dct-select"
                                onchange="document.getElementById('notification-form').submit()"
                                {if $page_params.editing_template}
                                    readonly
                                {/if}
                            >
                                <option value="">{lkn_hn_lang text="Select a platform"}</option>

                                {foreach from=$page_params.platform_list item=$platform}
                                    {if $platform->value !== 'mod'}
                                        <option
                                            value="{$platform->value}"
                                            {if $page_params.editing_template->platform === $platform}
                                                selected
                                            {/if}
                                        >
                                            {$platform->label()}
                                        </option>
                                    {/if}
                                {/foreach}
                            </select>

                            {if $page_params.editing_template}
                                <div style="margin-top: 8px;">
                                    <button
                                        id="btn-enable-platform-change"
                                        type="button"
                                        class="dct-button dct-button-ghost dct-text-small"
                                    >
                                        <i class="fas fa-exchange-alt"></i>
                                        {lkn_hn_lang text="Change template platform"}
                                    </button>

                                    <script type="text/javascript">
                                        const btnEnablePlatformChange = document.getElementById('btn-enable-platform-change')

                                        btnEnablePlatformChange.addEventListener('click', () => {
                                            btnEnablePlatformChange.style.display = 'none'

                                            const platformSelect = document.getElementById('platform')

                                            platformSelect.readonly = false
                                            platformSelect.showPicker();
                                        })
                                    </script>
                                </div>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>

            {* ===== Template ===== *}

            {if $page_params.request_platform_selection}
                {if empty($page_params.platform_list)}
                    <div class="dct-alert dct-alert-warning">
                        <i class="far fa-exclamation-triangle"></i>
                        {lkn_hn_lang text='You must configure and enable a platform first. Go to the "Settings" menu.'}
                    </div>
                {else}
                    <div class="dct-alert dct-alert-info">
                        <i class="far fa-info-circle"></i>
                        {lkn_hn_lang text="Please, choose a platform."}
                    </div>
                {/if}
            {/if}

            {if !$page_params.request_locale_selection && !$page_params.request_platform_selection}
                <div class="dct-card">
                    <div class="dct-card-body">
                        {$page_params.template_editor_view}
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <button
                        type="submit"
                        class="dct-button dct-button-primary"
                        onclick="return confirmSave()"
                    >
                        {lkn_hn_lang text="Save Template"}
                    </button>
                    <a
                        href="{$lkn_hn_base_endpoint}&page=notifications"
                        class="dct-button dct-button-secondary"
                    >
                        {lkn_hn_lang text="Cancel"}
                    </a>
                </div>

                {* Delete-from-within-the-editor stays disabled, matching the
                   original template exactly - it was already commented out
                   there (unreachable from this page in production), so
                   re-enabling it here would be adding functionality, not
                   preserving it. Delete already works correctly from the
                   Notifications list (Phase 3A), which remains the only
                   active path to it. *}

                <input
                    type="hidden"
                    name="operation"
                    id="operationType"
                    value=""
                >

                <script type="text/javascript">
                    function confirmDelete() {
                        if (confirm("{lkn_hn_lang text='Are you sure you want to delete this template?'}")) {
                        document.getElementById('operationType').value = 'delete';

                        return true;
                    }

                    return false;
                    }

                    function confirmSave() {
                        document.getElementById('operationType').value = 'save';

                        return confirm("{lkn_hn_lang text="Are you sure? The changes will take effect immediately."}");
                    }
                </script>
            {/if}
        </form>
    </div>
{/block}
