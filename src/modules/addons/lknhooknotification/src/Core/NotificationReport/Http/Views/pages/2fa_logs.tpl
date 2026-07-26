{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {$page_params.title}
{/block}

{block "page_content"}
    {assign "f" value=$page_params.filters}

    {if $page_params.table_missing}
        <div class="alert alert-info">
            {lkn_hn_lang text="No 2FA activity has been logged yet. This shows up once the \"WhatsApp Verification\" 2FA module (modules/security/lknwa2fa) has been activated and at least one login has gone through it."}
        </div>
    {else}
        <div class="panel panel-default">
            <div class="panel-body">
                <form method="get" action="?">
                    <input type="hidden" name="module" value="lknhooknotification">
                    <input type="hidden" name="page" value="{if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}">

                    <div class="row">
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{lkn_hn_lang text="User ID"}</label>
                                <input type="text" class="form-control" name="f_user_id" value="{$f.f_user_id|default:''}">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{lkn_hn_lang text="Event"}</label>
                                <select class="form-control" name="f_event">
                                    <option value="">{lkn_hn_lang text="Any"}</option>
                                    <option value="code_sent" {if $f.f_event == 'code_sent'}selected{/if}>{lkn_hn_lang text="Code Sent"}</option>
                                    <option value="verify_success" {if $f.f_event == 'verify_success'}selected{/if}>{lkn_hn_lang text="Verify Success"}</option>
                                    <option value="verify_failed" {if $f.f_event == 'verify_failed'}selected{/if}>{lkn_hn_lang text="Verify Failed"}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{lkn_hn_lang text="From"}</label>
                                <input type="date" class="form-control" name="f_date_from" value="{$f.f_date_from|default:''}">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>{lkn_hn_lang text="To"}</label>
                                <input type="date" class="form-control" name="f_date_to" value="{$f.f_date_to|default:''}">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> {lkn_hn_lang text="Search"}
                            </button>
                        </div>
                        <div class="col-sm-2">
                            <label>&nbsp;</label>
                            <a
                                class="btn btn-default btn-block"
                                href="?module=lknhooknotification&page={if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}"
                            >
                                {lkn_hn_lang text="Clear"}
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-condensed">
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
                                <td>{$log->created_at}</td>
                                <td>
                                    {if $log->user_name}{$log->user_name} {/if}
                                    <span class="text-muted">#{$log->user_id}</span>
                                </td>
                                <td>
                                    {if $log->event == 'code_sent'}
                                        <span class="label label-info">{lkn_hn_lang text="Code Sent"}</span>
                                    {elseif $log->event == 'verify_success'}
                                        <span class="label label-success">{lkn_hn_lang text="Verify Success"}</span>
                                    {elseif $log->event == 'verify_failed'}
                                        <span class="label label-danger">{lkn_hn_lang text="Verify Failed"}</span>
                                    {else}
                                        {$log->event}
                                    {/if}
                                </td>
                                <td>{$log->ip_address|default:'—'}</td>
                                <td>{$log->details|default:'—'}</td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="5" class="text-muted">{lkn_hn_lang text="No matching log entries."}</td>
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
                            <a href="?module=lknhooknotification&page={if $page_params.user_type == 'admin'}notification-2fa-admin-logs{else}notification-2fa-user-logs{/if}&pageN={$page}">{$page}</a>
                        </li>
                    {/for}
                </ul>
            </nav>
        {/if}
    {/if}
{/block}
