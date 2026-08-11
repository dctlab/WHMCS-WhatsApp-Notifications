{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Notification Reports" params=[$page_params.platform_title]}
{/block}

{block "page_content"}
    <style>
        .report-link { padding: 0px; }
        .popover { max-width: 380px; }
        .popover-content, .popover-body { word-wrap: break-word; white-space: pre-wrap; }
        .lkn-hn-actions-col { white-space: nowrap; min-width: 90px; }
        .lkn-hn-actions-col .btn-group { white-space: nowrap; }
        .lkn-hn-actions-col .btn-group > .btn { float: none; display: inline-block; }
    </style>

    {assign "f" value=$page_params.filters}
    {assign "filters_qs" value=""}
    {foreach from=['f_client','f_invoice','f_domain','f_status','f_delivery_status','f_billable','f_platform','f_date_from','f_date_to','per_page'] item=$fkey}
        {if !empty($f.$fkey)}
            {assign "filters_qs" value="`$filters_qs`&`$fkey`=`$f.$fkey`"}
        {/if}
    {/foreach}

    {* ===== Page header ===== *}
    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{lkn_hn_lang text="Reports"}</h1>
            <div class="dct-page-header-description">
                {lkn_hn_lang text="Track WhatsApp notification delivery and messaging activity."}
            </div>
        </div>
    </div>
    {* No Export action here on purpose - no export functionality exists in
       the current backend, so none is invented for this UI. *}

    {* ===== Filter toolbar - every filter here maps 1:1 to an existing,
       working backend filter. No "Notification" filter is included: the
       repository layer can filter by notification code, but the controller
       never wires a request param to it today, so it is not reachable in
       practice - flagged separately rather than silently added here. ===== *}
    <form method="get" action="?" class="dct-toolbar" style="align-items: flex-end;">
        <input type="hidden" name="module" value="dct_whatsapp_notifications">
        <input type="hidden" name="page" value="notification-reports">

        <div class="dct-toolbar-group" style="flex: 1 1 200px;">
            <label class="dct-form-label">{lkn_hn_lang text="Client"}</label>
            <input type="text" class="dct-input" name="f_client" placeholder="{lkn_hn_lang text='ID, name, email or phone'}" value="{$f.f_client|default:''}">
        </div>
        <div class="dct-toolbar-group" style="flex: 1 1 130px;">
            <label class="dct-form-label">{lkn_hn_lang text="Invoice #"}</label>
            <input type="text" class="dct-input" name="f_invoice" value="{$f.f_invoice|default:''}">
        </div>
        <div class="dct-toolbar-group" style="flex: 1 1 150px;">
            <label class="dct-form-label">{lkn_hn_lang text="Domain"}</label>
            <input type="text" class="dct-input" name="f_domain" placeholder="example.com" value="{$f.f_domain|default:''}">
        </div>
        <div class="dct-toolbar-group" style="flex: 1 1 140px;">
            <label class="dct-form-label">{lkn_hn_lang text="Status"}</label>
            <select class="dct-select" name="f_status">
                <option value="">{lkn_hn_lang text="Any"}</option>
                {foreach from=$page_params.field_options.status_options item=$opt}
                    <option value="{$opt.value}" {if $f.f_status == $opt.value}selected{/if}>{$opt.label}</option>
                {/foreach}
            </select>
        </div>
        <div class="dct-toolbar-group" style="flex: 1 1 150px;">
            <label class="dct-form-label">{lkn_hn_lang text="Delivery Status"}</label>
            <select class="dct-select" name="f_delivery_status">
                <option value="">{lkn_hn_lang text="Any"}</option>
                {foreach from=$page_params.field_options.delivery_status_options item=$opt}
                    <option value="{$opt.value}" {if $f.f_delivery_status == $opt.value}selected{/if}>{$opt.label}</option>
                {/foreach}
            </select>
        </div>
        <div class="dct-toolbar-group" style="flex: 1 1 140px;">
            <label class="dct-form-label">{lkn_hn_lang text="Platform"}</label>
            <select class="dct-select" name="f_platform">
                <option value="">{lkn_hn_lang text="Any"}</option>
                {foreach from=$page_params.field_options.platform_options item=$opt}
                    <option value="{$opt.value}" {if $f.f_platform == $opt.value}selected{/if}>{$opt.label}</option>
                {/foreach}
            </select>
        </div>
        <div class="dct-toolbar-group" style="flex: 1 1 120px;">
            <label class="dct-form-label">{lkn_hn_lang text="Billable"}</label>
            <select class="dct-select" name="f_billable">
                <option value="">{lkn_hn_lang text="Any"}</option>
                {foreach from=$page_params.field_options.billable_options item=$opt}
                    <option value="{$opt.value}" {if $f.f_billable == $opt.value}selected{/if}>{$opt.label}</option>
                {/foreach}
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
        <div class="dct-toolbar-group" style="flex: 0 0 90px;">
            <label class="dct-form-label">{lkn_hn_lang text="Per Page"}</label>
            <select class="dct-select" name="per_page" onchange="this.form.submit()">
                {foreach from=$page_params.per_page_options item=$opt}
                    <option value="{$opt}" {if $page_params.reports_per_page == $opt}selected{/if}>{$opt}</option>
                {/foreach}
            </select>
        </div>
        <div class="dct-toolbar-actions">
            <button type="submit" class="dct-button dct-button-primary">
                <i class="fas fa-search"></i> {lkn_hn_lang text="Search"}
            </button>
            <a class="dct-button dct-button-secondary" href="?module=dct_whatsapp_notifications&page=notification-reports">
                {lkn_hn_lang text="Clear"}
            </a>
        </div>
    </form>

    {if empty($page_params.reports)}
        <div class="dct-empty-state">
            <div class="dct-empty-state-icon"><i class="far fa-search"></i></div>
            <div class="dct-empty-state-title">{lkn_hn_lang text="No matching notifications found"}</div>
            <div class="dct-empty-state-description">
                {lkn_hn_lang text="Try changing your filters or date range."}
            </div>
            <a class="dct-button dct-button-secondary" href="?module=dct_whatsapp_notifications&page=notification-reports">
                {lkn_hn_lang text="Clear Filters"}
            </a>
        </div>
    {else}
        <form method="post" action="?module=dct_whatsapp_notifications&page=notification-reports" id="lkn-hn-bulk-form">
            <input type="hidden" name="module" value="dct_whatsapp_notifications">
            <input type="hidden" name="page" value="notification-reports">

            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <select class="dct-select" style="width: auto;" name="bulk_action">
                    <option value="resend">{lkn_hn_lang text="Resend"}</option>
                    <option value="delete">{lkn_hn_lang text="Delete"}</option>
                </select>
                <button
                    type="submit"
                    class="dct-button dct-button-secondary"
                    onclick="return lknHnConfirmBulkAction(this.form)"
                >
                    {lkn_hn_lang text="Apply Action"}
                </button>
                <span class="dct-text-muted" id="lkn-hn-selected-count"></span>
            </div>

            <div class="dct-card">
                <div class="dct-table-wrap dct-table-responsive">
                    <table class="dct-table" id="lkn-hn-reports-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="lkn-hn-select-all"></th>
                                <th>#</th>
                                <th>{lkn_hn_lang text="Status"}</th>
                                <th>{lkn_hn_lang text="Delivery"}</th>
                                <th>{lkn_hn_lang text="Billable"}</th>
                                <th>{lkn_hn_lang text="WA Category"}</th>
                                <th>{lkn_hn_lang text="Message"}</th>
                                <th>{lkn_hn_lang text="Sent Message"}</th>
                                <th>{lkn_hn_lang text="Date"}</th>
                                <th>{lkn_hn_lang text="Platform"}</th>
                                <th>{lkn_hn_lang text="Notification"}</th>
                                <th>{lkn_hn_lang text="Client"}</th>
                                <th>{lkn_hn_lang text="Category"}</th>
                                <th class="lkn-hn-actions-col">{lkn_hn_lang text="Actions"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$page_params.reports item=$report}
                                <tr>
                                    <td><input type="checkbox" class="lkn-hn-row-checkbox" name="selected_ids[]" value="{$report->id}"></td>
                                    <td>{$report->id}</td>

                                    <td>
                                        <span class="dct-status-badge {if $report->status->value === 'error'}dct-status-badge-danger{elseif $report->status->value === 'not_sent'}dct-status-badge-warning{elseif $report->status->value === 'resent'}dct-status-badge-info{else}dct-status-badge-success{/if}">
                                            {$report->status->label()}
                                        </span>
                                    </td>
                                    <td>
                                        {if $report->deliveryStatus}
                                            {assign "dsClass" value=$report->deliveryStatus->badgeClass()}
                                            <span class="dct-status-badge {if $dsClass === 'label-danger'}dct-status-badge-danger{elseif $dsClass === 'label-success'}dct-status-badge-success{elseif $dsClass === 'label-info' || $dsClass === 'label-primary'}dct-status-badge-info{else}dct-status-badge-neutral{/if}">
                                                {$report->deliveryStatus->label()}
                                            </span>
                                        {else}
                                            <span class="dct-text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td>
                                        {assign "bClass" value=$report->billableBadgeClass()}
                                        <span class="dct-status-badge {if $bClass === 'label-danger'}dct-status-badge-danger{elseif $bClass === 'label-success'}dct-status-badge-success{else}dct-status-badge-neutral{/if}">
                                            {$report->billableLabel()}
                                        </span>
                                    </td>
                                    <td style="text-transform: capitalize;">
                                        {if $report->waCategory}
                                            {$report->waCategoryLabel()}
                                        {else}
                                            <span class="dct-text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td style="max-width: 150px;">
                                        {if !empty($report->msg)}
                                            <p
                                                {if strlen($report->msg) > 30}
                                                    data-toggle="popover"
                                                    data-animation="false"
                                                    data-placement="right"
                                                    data-container="body"
                                                    data-html="true"
                                                    {if $report->platform->value === 'wp' && $report->status->value === 'error'}
                                                        data-content="
                                                        {htmlspecialchars($report->msg)}
                                                        <br>
                                                        <a href='https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes/#error-codes' target='_blank'>WhatsApp Cloud API Error Codes <i class='fas fa-external-link-alt'></i></a>"
                                                    {else}
                                                        data-content="{htmlspecialchars($report->msg)}"
                                                    {/if}
                                                    data-trigger="click hover"
                                                {/if}
                                                class="dct-text-muted"
                                                style="margin-bottom: 0px !important; width: fit-content; cursor: pointer;"
                                            >
                                                {if strlen($report->msg) > 30}
                                                    <i class="fas fa-question-circle"></i>
                                                    {substr($report->msg, 0, 30)}...
                                                {else}
                                                    {lkn_hn_lang text="{$report->msg}"}
                                                {/if}
                                            </p>
                                        {/if}
                                    </td>
                                    <td style="max-width: 150px;">
                                        {if !empty($report->messagePreview)}
                                            <p
                                                {if strlen($report->messagePreview) > 40}
                                                    data-toggle="popover"
                                                    data-animation="false"
                                                    data-placement="top"
                                                    data-container="body"
                                                    data-html="true"
                                                    data-content="{htmlspecialchars(substr($report->messagePreview, 0, 500))}{if strlen($report->messagePreview) > 500}...{/if}"
                                                    data-trigger="click hover"
                                                {/if}
                                                class="dct-text-muted"
                                                style="margin-bottom: 0px !important; width: fit-content; cursor: pointer;"
                                            >
                                                {if strlen($report->messagePreview) > 40}
                                                    <i class="fas fa-comment-alt"></i>
                                                    {substr($report->messagePreview, 0, 40)}...
                                                {else}
                                                    {$report->messagePreview}
                                                {/if}
                                            </p>
                                        {else}
                                            <span class="dct-text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td class="dct-text-small">{$report->createdAt->format('Y-m-d H:i:s')}</td>
                                    <td>
                                        {if $report->platform}
                                            <a
                                                class="btn btn-link report-link"
                                                href="?module=dct_whatsapp_notifications&page=platforms/{$report->platform->value}/settings"
                                            >
                                                {$report->platform->label()}
                                            </a>
                                        {/if}
                                    </td>
                                    <td>
                                        {if !$report->platform || $report->platform->value === 'cw'}
                                            {lkn_hn_lang text="{$report->notificationCode}"}
                                        {else}
                                            <a
                                                class="btn btn-link report-link"
                                                href="?module=dct_whatsapp_notifications&page=notifications/{$report->notificationCode}/templates/first"
                                            >
                                                {lkn_hn_lang text="{$report->notificationCode}"}
                                            </a>
                                        {/if}
                                    </td>
                                    <td>
                                        {if $report->clientId}
                                            <a
                                                target="_blank"
                                                href="clientssummary.php?userid={$report->clientId}"
                                            >
                                                #{$report->clientId}
                                                {if $report->target}
                                                    at +{$report->target}
                                                {/if}
                                            </a>
                                        {/if}
                                    </td>
                                    <td>
                                        {if !empty($report->category) && !empty($report->categoryId)}
                                            {if $report->category->value === 'invoice'}
                                                {assign "category_link" "invoices.php?action=edit&id={$report->categoryId}"}
                                            {elseif $report->category->value === 'order'}
                                                {assign "category_link" "orders.php?action=view&id={$report->categoryId}"}
                                            {elseif $report->category->value === 'ticket'}
                                                {assign "category_link" "supporttickets.php?action=view&id={$report->categoryId}"}
                                            {elseif $report->category->value === 'domain'}
                                                {assign "category_link" "clientsdomains.php?userid={$report->clientId}&id={$report->categoryId}"}
                                            {/if}

                                            <a target="_blank" href="{$category_link}">
                                                {$report->category->label()} #{$report->categoryId}
                                            </a>
                                        {/if}
                                    </td>
                                    <td class="lkn-hn-actions-col">
                                        <div class="dct-table-actions">
                                            {if $report->canResend}
                                                <button
                                                    type="submit"
                                                    form="lkn-hn-bulk-form"
                                                    name="resend"
                                                    value="{$report->id}"
                                                    class="dct-button dct-button-primary"
                                                    style="padding: 4px 8px;"
                                                    data-toggle="tooltip"
                                                    title="{lkn_hn_lang text="Resend (Message only)"}"
                                                    onclick="return confirm('{lkn_hn_lang text="Resend this message now?"}')"
                                                >
                                                    <i class="far fa-play-circle"></i>
                                                </button>
                                            {/if}
                                            <button
                                                type="submit"
                                                form="lkn-hn-bulk-form"
                                                name="delete"
                                                value="{$report->id}"
                                                class="dct-button dct-button-danger"
                                                style="padding: 4px 8px;"
                                                data-toggle="tooltip"
                                                title="{lkn_hn_lang text="Delete"}"
                                                onclick="return confirm('{lkn_hn_lang text="Delete this report? This only removes the log entry - it does not unsend the message. This cannot be undone."}')"
                                            >
                                                <i class="far fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <div>
            {assign "total_pages" value=ceil($page_params.total_reports / $page_params.reports_per_page)}
            {assign "page_link_tpl" value="?module=dct_whatsapp_notifications&page=notification-reports`$filters_qs`&pageN"}

            {if $total_pages > 1}
                <nav aria-label="Page navigation" style="text-align: center;">
                    <ul class="pagination">
                        {if $page_params.current_page > 1}
                            <li>
                                <a href="{$page_link_tpl}=1">
                                    {lkn_hn_lang text="First Page"}
                                </a>
                            </li>
                        {/if}
                        <li {if $page_params.current_page == 1}class="disabled"{/if}>
                            <a
                                {if $page_params.current_page > 1}
                                    href="{$page_link_tpl}={$page_params.current_page - 1}"
                                {/if}
                                aria-label="Previous"
                            >
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        {if $total_pages >= 15}
                            {for $page=$page_params.current_page - 8 to $page_params.current_page}
                                {if $page > 0}
                                    <li {if $page == $page_params.current_page}class="active"{/if}>
                                        <a href="{$page_link_tpl}={$page}">{$page}</a>
                                    </li>
                                {/if}
                            {/for}

                            {for $page=$page_params.current_page + 1 to $page_params.current_page + 8}
                                {if $page < $total_pages}
                                    <li {if $page == $page_params.current_page}class="active"{/if}>
                                        <a href="{$page_link_tpl}={$page}">{$page}</a>
                                    </li>
                                {/if}
                            {/for}
                        {else}
                            {for $page=1 to $total_pages}
                                <li {if $page == $page_params.current_page}class="active"{/if}>
                                    <a href="{$page_link_tpl}={$page}">{$page}</a>
                                </li>
                            {/for}
                        {/if}

                        <li {if $page_params.current_page >= $total_pages}class="disabled"{/if}>
                            <a
                                {if $page_params.current_page < $total_pages}
                                    href="{$page_link_tpl}={$page_params.current_page + 1}"
                                {/if}
                                aria-label="Next"
                            >
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>

                        {if $page_params.current_page <= $total_pages - 1}
                            <li>
                                <a href="{$page_link_tpl}={$total_pages}">
                                    {lkn_hn_lang text="Last Page"}
                                </a>
                            </li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        </div>
    {/if}

    <script>
        (function () {
            if (window.jQuery) {
                jQuery('[data-toggle="tooltip"]').tooltip();
            }

            var selectAll = document.getElementById('lkn-hn-select-all');
            var countLabel = document.getElementById('lkn-hn-selected-count');

            function getRowCheckboxes() {
                return document.querySelectorAll('.lkn-hn-row-checkbox');
            }

            function updateCount() {
                if (!countLabel) {
                    return;
                }
                var checked = document.querySelectorAll('.lkn-hn-row-checkbox:checked').length;
                countLabel.textContent = checked > 0 ? checked + ' {lkn_hn_lang text="selected"}' : '';
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    getRowCheckboxes().forEach(function (cb) {
                        cb.checked = selectAll.checked;
                    });
                    updateCount();
                });
            }

            getRowCheckboxes().forEach(function (cb) {
                cb.addEventListener('change', updateCount);
            });

            window.lknHnConfirmBulkAction = function (form) {
                var checked = document.querySelectorAll('.lkn-hn-row-checkbox:checked').length;

                if (checked === 0) {
                    alert('{lkn_hn_lang text="Select at least one report first."}');
                    return false;
                }

                var action = form.querySelector('select[name="bulk_action"]').value;
                var message = action === 'delete'
                    ? '{lkn_hn_lang text="Delete"}'.concat(' ', checked, ' {lkn_hn_lang text="selected reports? This cannot be undone."}')
                    : '{lkn_hn_lang text="Resend"}'.concat(' ', checked, ' {lkn_hn_lang text="selected reports now?"}');

                return confirm(message);
            };
        })();
    </script>
{/block}
