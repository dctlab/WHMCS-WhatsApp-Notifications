<input
    name="message-template-lang"
    type="hidden"
    value="{$page_params.editing_message_template['language']}"
>

<div class="dct-form-group">
    <label for="message-template" class="dct-form-label" style="font-size: 14px;">{lkn_hn_lang text='Message Template'}</label>
    <select
        id="message-template"
        name="message-template"
        class="dct-select"
        onchange="(document.getElementById('notification-form') ?? document.getElementById('lkn-hn-new-bulk-form')).submit()"
        {if $page_params.editing_message_template_name}
            readonly
        {/if}
    >
        <option value="">{lkn_hn_lang text="Select a platform"}</option>

        {foreach from=$page_params.message_templates_options item=$value key=$label}
            <option
                value="{$value}"
                {if $page_params.editing_message_template_name === $value}
                    selected
                {/if}
            >
                {$label}
            </option>
        {/foreach}
    </select>

    {if $page_params.editing_message_template_name}
        <div style="margin-top: 8px;">
            {if !$page_params.disable_template_editor_changes}
                <button
                    id="btn-enable-message-template-change"
                    type="button"
                    class="dct-button dct-button-ghost dct-text-small"
                >
                    <i class="fas fa-exchange-alt"></i>
                    {lkn_hn_lang text="Change message template"}
                </button>
            {/if}

            <script type="text/javascript">
                const btnEnableMessageTemplateChange = document.getElementById('btn-enable-message-template-change')

                btnEnableMessageTemplateChange.addEventListener('click', () => {
                    btnEnableMessageTemplateChange.style.display = 'none'

                    const messageTemplateSelect = document.getElementById('message-template')

                    messageTemplateSelect.readonly = false
                    messageTemplateSelect.showPicker();
                })
            </script>
        </div>
    {/if}
</div>

{if $page_params.editing_message_template_name}
    <div class="dct-alert dct-alert-info">
        <i class="fas fa-caret-right"></i>
        {lkn_hn_lang text="Indicate for the notification what to put in the parameters of the message template created in Meta."}
    </div>
{else}
    <div class="dct-alert dct-alert-info">
        <i class="far fa-info-circle"></i>
        {lkn_hn_lang text="Please, choose a message template."}
    </div>
{/if}

<div id="dct-meta-tpl-components">
    {foreach from=$page_params.editing_message_template['components'] item=$component}
        <div class="dct-card" style="margin-bottom: 12px;">
            <div class="dct-card-header">
                <span class="dct-card-title">
                    {lkn_hn_lang text=$component['type']}
                    {if !empty($component['format'])}
                        &mdash; {lkn_hn_lang text=$component['format']}
                    {/if}
                </span>
            </div>

            <div class="dct-card-body" style="text-align: left;">
                {if $component['type'] === 'HEADER'}
                    <input type="hidden" name="header-format" value="{$component['format']}" />

                    {if $page_params.editing_template_header_view === null}
                        <div class="dct-alert dct-alert-warning" style="margin-bottom: 0;">
                            {lkn_hn_lang text="This header type is not supported by the module."} ({$component['format']}).
                            <a class="dct-button dct-button-ghost dct-text-small" href="https://dctlab.directcybertech.com/" target="_blank">
                                {lkn_hn_lang text="Request this feature"} <i class="far fa-external-link-alt"></i>
                            </a>
                        </div>
                    {else}
                        <div id="dct-meta-header-source">{$page_params.editing_template_header_view}</div>
                    {/if}

                {elseif $component['type'] === 'BODY'}
                    <div id="dct-meta-body-source" style="line-height: 2;">
                        {$page_params.editing_template_body_view}
                    </div>

                {elseif $component['type'] === 'FOOTER'}
                    <span class="dct-text-muted">{$component['text']}</span>

                {elseif $component['type'] === 'BUTTONS'}
                    <div id="dct-meta-buttons-source" style="display: flex; flex-direction: column; gap: 8px; align-items: stretch;">
                        {$page_params.editing_template_buttons_view}
                    </div>
                {/if}
            </div>
        </div>
    {/foreach}
</div>

{if $page_params.editing_message_template_name && $page_params.editing_message_template['components']}
    {* ===== Message Preview =====
       Client-side only: reads the currently selected option in each
       parameter <select> already rendered above and substitutes it into a
       WhatsApp-style bubble. No API call, no new backend data - built
       entirely from what is already on this page. Uses each parameter's
       label as sample text (not a real sent value), and is explicitly
       labeled "Preview" so it can never be mistaken for an actual message. *}
    <div class="dct-card">
        <div class="dct-card-header">
            <span class="dct-card-title">{lkn_hn_lang text="Preview"}</span>
            <span class="dct-text-muted dct-text-small">{lkn_hn_lang text="Sample values shown for illustration only"}</span>
        </div>
        <div class="dct-card-body" style="background: #e5ddd5; display: flex; justify-content: center;">
            <div style="max-width: 320px; width: 100%; background: #fff; border-radius: 8px; padding: 10px 12px; box-shadow: var(--dct-shadow-sm); font-size: 13px; line-height: 1.5;">
                <div id="dct-meta-preview-header" style="font-weight: 700; margin-bottom: 4px;"></div>
                <div id="dct-meta-preview-body" style="white-space: pre-wrap;"></div>
                <div id="dct-meta-preview-footer" class="dct-text-muted dct-text-small" style="margin-top: 6px;"></div>
                <div id="dct-meta-preview-buttons" style="display: flex; flex-direction: column; gap: 4px; margin-top: 8px; border-top: 1px solid var(--dct-border-light); padding-top: 6px;"></div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        (function () {
            function renderNodeAsText(container) {
                if (!container) {
                    return '';
                }

                var result = '';

                container.childNodes.forEach(function (node) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        result += node.textContent;
                    } else if (node.tagName === 'SELECT') {
                        var selected = node.selectedOptions && node.selectedOptions[0];
                        result += selected && selected.value
                            ? '[' + selected.text + ']'
                            : '[...]';
                    } else {
                        result += node.textContent || '';
                    }
                });

                return result.trim();
            }

            function updatePreview() {
                var headerSource = document.getElementById('dct-meta-header-source');
                var bodySource = document.getElementById('dct-meta-body-source');
                var buttonsSource = document.getElementById('dct-meta-buttons-source');

                var headerEl = document.getElementById('dct-meta-preview-header');
                var bodyEl = document.getElementById('dct-meta-preview-body');
                var buttonsEl = document.getElementById('dct-meta-preview-buttons');

                if (headerEl) {
                    headerEl.textContent = renderNodeAsText(headerSource);
                }

                if (bodyEl) {
                    bodyEl.textContent = renderNodeAsText(bodySource);
                }

                if (buttonsEl && buttonsSource) {
                    buttonsEl.innerHTML = '';

                    buttonsSource.querySelectorAll('button').forEach(function (btn) {
                        var row = document.createElement('div');
                        row.style.textAlign = 'center';
                        row.style.color = 'var(--dct-primary, #1a56db)';
                        row.style.fontSize = '13px';
                        row.textContent = btn.textContent.trim();
                        buttonsEl.appendChild(row);
                    });
                }
            }

            document.querySelectorAll('#dct-meta-tpl-components select').forEach(function (select) {
                select.addEventListener('change', updatePreview);
            });

            updatePreview();
        })();
    </script>
{/if}
