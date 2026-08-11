{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {$page_params.title}
{/block}

{block "page_content"}
    {assign "f" value=$page_params.filters}

    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{$page_params.title}</h1>
            <div class="dct-page-header-description">
                {lkn_hn_lang text="Review WhatsApp-based two-factor authentication activity."}
            </div>
        </div>
    </div>

    {if $page_params.table_missing}
        <div class="dct-alert dct-alert-info">
            <i class="far fa-info-circle"></i>
            {lkn_hn_lang text="No 2FA activity has been logged yet. This shows up once the \"WhatsApp Verification\" 2FA module (modules/security/dct2fa) has been activated and at least one login has gone through it."}
        </div>
    {else}
        {* ===== Filter toolbar - every field here maps 1:1 to an existing,
           working backend filter. No Status/Phone/IP filter is included:
           none exist in the current implementation (event itself already
           encodes the outcome - there is no separate status column), and
           the only identity filter that exists is a numeric User ID field,
           not a name search. ===== *}
        <form method="get" action="?" class="dct-toolbar" style="align-items: flex-end;">
            <input type="hidden" name="module" value="dct_whatsapp_notifications">
            <input type="hidden" name="page" value="{if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}">

            <div class="dct-toolbar-group" style="flex: 1 1 120px;">
                <label class="dct-form-label">{lkn_hn_lang text="User ID"}</label>
                <input type="text" class="dct-input" name="f_user_id" value="{$f.f_user_id|default:''}">
            </div>
            <div class="dct-toolbar-group" style="flex: 1 1 160px;">
                <label class="dct-form-label">{lkn_hn_lang text="Event"}</label>
                <select class="dct-select" name="f_event">
                    <option value="">{lkn_hn_lang text="Any"}</option>
                    <option value="code_sent" {if $f.f_event == 'code_sent'}selected{/if}>{lkn_hn_lang text="Code Sent"}</option>
                    <option value="verify_success" {if $f.f_event == 'verify_success'}selected{/if}>{lkn_hn_lang text="Verify Success"}</option>
                    <option value="verify_failed" {if $f.f_event == 'verify_failed'}selected{/if}>{lkn_hn_lang text="Verify Failed"}</option>
                </select>
            </div>
            <div class="dct-toolbar-group" style="flex: 1 1 130px;">
                <label class="dct-form-label">{lkn_hn_lang text="From"}</label>
                <input type="date" class="dct-input" name="f_date_from" value="{$f.f_date_from|default:''}">
            </div>
            <div class="dct-toolbar-group" style="flex: 1 1 130px;">
                <label class="dct-form-label">{lkn_hn_lang text="To"}</label>
                <input type="date" class="dct-input" name="f_date_to" value="{$f.f_date_to|default:''}">
            </div>
            <div class="dct-toolbar-actions">
                <button type="submit" class="dct-button dct-button-primary">
                    <i class="fas fa-search"></i> {lkn_hn_lang text="Search"}
                </button>
                <a
                    class="dct-button dct-button-secondary"
                    href="?module=dct_whatsapp_notifications&page={if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}"
                >
                    {lkn_hn_lang text="Clear"}
                </a>
            </div>
        </form>

        {if empty($page_params.logs)}
            <div class="dct-empty-state">
                <div class="dct-empty-state-icon"><i class="far fa-search"></i></div>
                <div class="dct-empty-state-title">{lkn_hn_lang text="No 2FA activity found"}</div>
                <div class="dct-empty-state-description">
                    {lkn_hn_lang text="Try changing your filters or date range."}
                </div>
                <a
                    class="dct-button dct-button-secondary"
                    href="?module=dct_whatsapp_notifications&page={if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}"
                >
                    {lkn_hn_lang text="Clear Filters"}
                </a>
            </div>
        {else}
            <div class="dct-card">
                <div class="dct-table-wrap dct-table-responsive">
                    <table class="dct-table">
                        <thead>
                            <tr>
                                <th>{lkn_hn_lang text="Date"}</th>
                                <th>{if $page_params.user_type == 'admin'}{lkn_hn_lang text="Admin"}{else}{lkn_hn_lang text="Client"}{/if}</th>
                                <th>{lkn_hn_lang text="Event"}</th>
                                <th>{lkn_hn_lang text="IP Address"}</th>
                                <th>{lkn_hn_lang text="Details"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$page_params.logs item=$log}
                                <tr>
                                    <td class="dct-text-small">{$log->created_at}</td>
                                    <td>
                                        {if $log->user_name}{$log->user_name|escape} {/if}
                                        <span class="dct-text-muted">#{$log->user_id}</span>
                                    </td>
                                    <td>
                                        {if $log->event == 'code_sent'}
                                            <span class="dct-status-badge dct-status-badge-info">{lkn_hn_lang text="Code Sent"}</span>
                                        {elseif $log->event == 'verify_success'}
                                            <span class="dct-status-badge dct-status-badge-success">{lkn_hn_lang text="Verify Success"}</span>
                                        {elseif $log->event == 'verify_failed'}
                                            <span class="dct-status-badge dct-status-badge-danger">{lkn_hn_lang text="Verify Failed"}</span>
                                        {else}
                                            <span class="dct-status-badge dct-status-badge-neutral">{$log->event|escape}</span>
                                        {/if}
                                    </td>
                                    <td class="dct-text-small">{$log->ip_address|default:'—'|escape}</td>
                                    <td class="dct-text-small">{$log->details|default:'—'|escape}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            {assign "total_pages" value=ceil($page_params.total_logs / $page_params.per_page)}
            {if $total_pages > 1}
                <nav aria-label="Page navigation" style="text-align: center;">
                    <ul class="pagination">
                        {for $page=max(1, $page_params.current_page - 8) to min($total_pages, $page_params.current_page + 8)}
                            <li {if $page == $page_params.current_page}class="active"{/if}>
                                <a href="?module=dct_whatsapp_notifications&page={if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}&pageN={$page}">{$page}</a>
                            </li>
                        {/for}
                    </ul>
                </nav>
            {/if}
        {/if}
    {/if}
{/block}
