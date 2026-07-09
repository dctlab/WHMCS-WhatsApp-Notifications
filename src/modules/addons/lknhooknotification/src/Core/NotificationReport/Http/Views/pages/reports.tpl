{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Notification Reports" params=[$page_params.platform_title]}
{/block}

{block "page_content"}
    <style>
        .report-link {
            padding: 0px;
        }
        .lkn-hn-filters .form-group {
            margin-bottom: 10px;
        }
        .popover {
            max-width: 380px;
        }
        .popover-content, .popover-body {
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .lkn-hn-actions-col {
            white-space: nowrap;
            min-width: 90px;
        }
        .lkn-hn-actions-col .btn-group {
            white-space: nowrap;
        }
        .lkn-hn-actions-col .btn-group > .btn {
            float: none;
            display: inline-block;
        }
        #lkn-hn-reports-table th,
        #lkn-hn-reports-table td {
            padding: 6px 8px;
        }
        #lkn-hn-reports-table .label {
            white-space: nowrap;
        }
    </style>

    {assign "f" value=$page_params.filters}
    {assign "filters_qs" value=""}
    {foreach from=['f_client','f_invoice','f_domain','f_status','f_delivery_status','f_billable','f_platform','f_date_from','f_date_to','per_page'] item=$fkey}
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
                        <input type="hidden" name="page" value="notification-reports">

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
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Invoice #"}</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="f_invoice"
                                        value="{$f.f_invoice|default:''}"
                                    >
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Domain"}</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="f_domain"
                                        placeholder="example.com"
                                        value="{$f.f_domain|default:''}"
                                    >
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Status"}</label>
                                    <select class="form-control" name="f_status">
                                        <option value="">{lkn_hn_lang text="Any"}</option>
                                        {foreach from=$page_params.field_options.status_options item=$opt}
                                            <option value="{$opt.value}" {if $f.f_status == $opt.value}selected{/if}>{$opt.label}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Delivery Status"}</label>
                                    <select class="form-control" name="f_delivery_status">
                                        <option value="">{lkn_hn_lang text="Any"}</option>
                                        {foreach from=$page_params.field_options.delivery_status_options item=$opt}
                                            <option value="{$opt.value}" {if $f.f_delivery_status == $opt.value}selected{/if}>{$opt.label}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Platform"}</label>
                                    <select class="form-control" name="f_platform">
                                        <option value="">{lkn_hn_lang text="Any"}</option>
                                        {foreach from=$page_params.field_options.platform_options item=$opt}
                                            <option value="{$opt.value}" {if $f.f_platform == $opt.value}selected{/if}>{$opt.label}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-2">
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
                            <div class="col-sm-1">
                                <div class="form-group">
                                    <label>{lkn_hn_lang text="Per Page"}</label>
                                    <select class="form-control" name="per_page" onchange="this.form.submit()">
                                        {foreach from=$page_params.per_page_options item=$opt}
                                            <option value="{$opt}" {if $page_params.reports_per_page == $opt}selected{/if}>{$opt}</option>
                                        {/foreach}
                                    </select>
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
                                    href="?module=lknhooknotification&page=notification-reports"
                                >
                                    {lkn_hn_lang text="Clear"}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <form method="post" action="?module=lknhooknotification&page=notification-reports" id="lkn-hn-bulk-form">
                    <input type="hidden" name="module" value="lknhooknotification">
                    <input type="hidden" name="page" value="notification-reports">

                    <div class="panel-body" style="padding-top: 0; padding-bottom: 0;">
                        <div class="form-inline" style="margin-bottom: 10px;">
                            <select class="form-control input-sm" name="bulk_action">
                                <option value="resend">{lkn_hn_lang text="Resend"}</option>
                                <option value="delete">{lkn_hn_lang text="Delete"}</option>
                            </select>
                            <button
                                type="submit"
                                class="btn btn-sm btn-default"
                                onclick="return lknHnConfirmBulkAction(this.form)"
                            >
                                {lkn_hn_lang text="Apply Action"}
                            </button>
                            <span class="text-muted" id="lkn-hn-selected-count"></span>
                        </div>
                    </div>

                <div class="table-responsive">
                    <table class="table table-hover table-condensed" id="lkn-hn-reports-table">
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
                                    <th scope="row">{$report->id}</th>

                                    <td>
                                        <span
                                            class="label label-{if $report->status->value === 'error'}danger{elseif $report->status->value === 'not_sent'}warning{elseif $report->status->value === 'resent'}info{else}success{/if}"
                                        >
                                            {$report->status->label()}
                                        </span>
                                    </td>
                                    <td>
                                        {if $report->deliveryStatus}
                                            <span class="label {$report->deliveryStatus->badgeClass()}">
                                                {$report->deliveryStatus->label()}
                                            </span>
                                        {else}
                                            <span class="text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td>
                                        <span class="label {$report->billableBadgeClass()}">
                                            {$report->billableLabel()}
                                        </span>
                                    </td>
                                    <td style="text-transform: capitalize;">
                                        {if $report->waCategory}
                                            {$report->waCategoryLabel()}
                                        {else}
                                            <span class="text-muted">&mdash;</span>
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
                                                class="text-muted"
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
                                                class="text-muted"
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
                                            <span class="text-muted">&mdash;</span>
                                        {/if}
                                    </td>
                                    <td>{$report->createdAt->format('Y-m-d H:i:s')}</td>
                                    <td>
                                        {if $report->platform}
                                            <a
                                                class="btn btn-link report-link"
                                                href="?module=lknhooknotification&page=platforms/{$report->platform->value}/settings"
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
                                                href="?module=lknhooknotification&page=notifications/{$report->notificationCode}/templates/first"
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

                                            <a
                                                target="_blank"
                                                href="{$category_link}"
                                            >
                                                {$report->category->label()} #{$report->categoryId}
                                            </a>
                                        {/if}
                                    </td>
                                    <td class="lkn-hn-actions-col">
                                        <div class="btn-group" role="group" aria-label="Message Control">
                                            {if $report->canResend}
                                                <button
                                                    type="submit"
                                                    form="lkn-hn-bulk-form"
                                                    name="resend"
                                                    value="{$report->id}"
                                                    class="btn btn-primary"
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
                                                class="btn btn-danger"
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
                </form>
            </div>

            <div>
                {assign "total_pages" value=ceil($page_params.total_reports / $page_params.reports_per_page)}
                {assign "page_link_tpl" value="?module=lknhooknotification&page=notification-reports`$filters_qs`&pageN"}

                {if $total_pages > 1}
                    <nav
                        aria-label="Page navigation"
                        style="text-align: center;"
                    >
                        <ul class="pagination">
                            {if $page_params.current_page > 1}
                                <li>
                                    <a href="{$page_link_tpl}=1">
                                        {lkn_hn_lang text="First Page"}
                                    </a>
                                </li>
                            {/if}
                            <li
                                {if $page_params.current_page == 1}
                                    class="disabled"
                                {/if}
                            >
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
                                        <li
                                            {if $page == $page_params.current_page}
                                                class="active"
                                            {/if}
                                        >
                                            <a href="{$page_link_tpl}={$page}">{$page}</a>
                                        </li>
                                    {/if}
                                {/for}

                                {for $page=$page_params.current_page + 1 to $page_params.current_page + 8}
                                    {if $page < $total_pages}
                                        <li
                                            {if $page == $page_params.current_page}
                                                class="active"
                                            {/if}
                                        >
                                            <a href="{$page_link_tpl}={$page}">{$page}</a>
                                        </li>
                                    {/if}
                                {/for}


                            {else}
                                {for $page=1 to $total_pages}
                                    <li
                                        {if $page == $page_params.current_page}
                                            class="active"
                                        {/if}
                                    ><a href="{$page_link_tpl}={$page}">{$page}</a></li>
                                {/for}
                            {/if}

                            <li
                                {if $page_params.current_page >= $total_pages}
                                    class="disabled"
                                {/if}
                            >
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
        </div>
    </div>

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
