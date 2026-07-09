{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="WhatsApp Conversations"}
{/block}

{block "page_content"}
    <style>
        .lkn-hn-filters .form-group {
            margin-bottom: 10px;
        }
    </style>

    {assign "f" value=$page_params.filters}
    {assign "filters_qs" value=""}
    {foreach from=['f_client','f_category','f_billable','f_date_from','f_date_to'] item=$fkey}
        {if !empty($f.$fkey)}
            {assign "filters_qs" value="`$filters_qs`&`$fkey`=`$f.$fkey`"}
        {/if}
    {/foreach}

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body lkn-hn-filters">
                    <form method="get" action="?">
                        <input type="hidden" name="module" value="lknhooknotification">
                        <input type="hidden" name="page" value="notification-conversations">

                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Client"}</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="f_client"
                                        placeholder="{lkn_hn_lang text='ID, name, email or phone'}"
                                        value="{$f.f_client|default:''}"
                                    >
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Category"}</label>
                                    <select class="form-control" name="f_category">
                                        <option value="">{lkn_hn_lang text="Any"}</option>
                                        {foreach from=$page_params.field_options.category_options item=$opt}
                                            <option value="{$opt.value}" {if $f.f_category == $opt.value}selected{/if}>{$opt.label}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Billable"}</label>
                                    <select class="form-control" name="f_billable">
                                        <option value="">{lkn_hn_lang text="Any"}</option>
                                        {foreach from=$page_params.field_options.billable_options item=$opt}
                                            <option value="{$opt.value}" {if $f.f_billable == $opt.value}selected{/if}>{$opt.label}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="From"}</label>
                                    <input type="date" class="form-control" name="f_date_from" value="{$f.f_date_from|default:''}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3">
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
                                    href="?module=lknhooknotification&page=notification-conversations"
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
                                <th>{lkn_hn_lang text="Conversation"}</th>
                                <th>{lkn_hn_lang text="Client"}</th>
                                <th>{lkn_hn_lang text="Category"}</th>
                                <th>{lkn_hn_lang text="Billable"}</th>
                                <th>{lkn_hn_lang text="Origin"}</th>
                                <th>{lkn_hn_lang text="Messages"}</th>
                                <th>{lkn_hn_lang text="Last Message"}</th>
                                <th>{lkn_hn_lang text="First Message"}</th>
                                <th>{lkn_hn_lang text="Last Message At"}</th>
                                <th>{lkn_hn_lang text="Expires"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$page_params.conversations item=$conversation}
                                <tr>
                                    <td>
                                        <span
                                            class="text-muted"
                                            title="{$conversation->conversationId}"
                                            style="cursor: help;"
                                        >
                                            {substr($conversation->conversationId, 0, 12)}...
                                        </span>
                                    </td>
                                    <td>
                                        {if $conversation->clientId}
                                            <a target="_blank" href="clientssummary.php?userid={$conversation->clientId}">
                                                #{$conversation->clientId}
                                            </a>
                                        {elseif $conversation->phoneNumber}
                                            +{$conversation->phoneNumber}
                                        {else}
                                            <span class="text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td style="text-transform: capitalize;">{$conversation->categoryLabel()}</td>
                                    <td>
                                        <span class="label {$conversation->billableBadgeClass()}">
                                            {$conversation->billableLabel()}
                                        </span>
                                    </td>
                                    <td style="text-transform: capitalize;">
                                        {if $conversation->originType}
                                            {$conversation->originType|replace:'_':' '}
                                        {else}
                                            <span class="text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td>{$conversation->messageCount}</td>
                                    <td style="max-width: 260px;">
                                        {if $conversation->lastMessagePreview}
                                            {if $conversation->lastMessageDirectionIcon()}
                                                <i
                                                    class="fas {$conversation->lastMessageDirectionIcon()}"
                                                    title="{$conversation->lastMessageDirectionLabel()}"
                                                ></i>
                                            {/if}
                                            <span title="{$conversation->lastMessagePreview}">
                                                {$conversation->lastMessagePreview|truncate:60}
                                            </span>
                                        {else}
                                            <span class="text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td>{if $conversation->firstMessageAt}{$conversation->firstMessageAt->format('Y-m-d H:i')}{/if}</td>
                                    <td>{if $conversation->lastMessageAt}{$conversation->lastMessageAt->format('Y-m-d H:i')}{/if}</td>
                                    <td>
                                        {if $conversation->expirationAt}
                                            <span class="{if $conversation->isExpired()}text-muted{else}text-success{/if}">
                                                {$conversation->expirationAt->format('Y-m-d H:i')}
                                            </span>
                                        {else}
                                            <span class="text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="10" class="text-muted">
                                        {lkn_hn_lang text="No conversations yet. Make sure the WhatsApp webhook is configured in the Meta WhatsApp settings page."}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                {assign "total_pages" value=ceil($page_params.total_conversations / $page_params.per_page)}
                {assign "page_link_tpl" value="?module=lknhooknotification&page=notification-conversations`$filters_qs`&pageN"}

                {if $total_pages > 1}
                    <nav aria-label="Page navigation" style="text-align: center;">
                        <ul class="pagination">
                            {if $page_params.current_page > 1}
                                <li><a href="{$page_link_tpl}=1">{lkn_hn_lang text="First Page"}</a></li>
                            {/if}
                            <li {if $page_params.current_page == 1}class="disabled"{/if}>
                                <a
                                    {if $page_params.current_page > 1}href="{$page_link_tpl}={$page_params.current_page - 1}"{/if}
                                    aria-label="Previous"
                                >
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            {for $page=max(1, $page_params.current_page - 8) to min($total_pages, $page_params.current_page + 8)}
                                <li {if $page == $page_params.current_page}class="active"{/if}>
                                    <a href="{$page_link_tpl}={$page}">{$page}</a>
                                </li>
                            {/for}

                            <li {if $page_params.current_page >= $total_pages}class="disabled"{/if}>
                                <a
                                    {if $page_params.current_page < $total_pages}href="{$page_link_tpl}={$page_params.current_page + 1}"{/if}
                                    aria-label="Next"
                                >
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>

                            {if $page_params.current_page <= $total_pages - 1}
                                <li><a href="{$page_link_tpl}={$total_pages}">{lkn_hn_lang text="Last Page"}</a></li>
                            {/if}
                        </ul>
                    </nav>
                {/if}
            </div>
        </div>
    </div>
{/block}
