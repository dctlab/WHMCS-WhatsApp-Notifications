{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Message Analytics"}
{/block}

{block "page_content"}
    <style>
        .lkn-hn-stat-card {
            border-radius: 4px;
            padding: 20px;
            color: #fff;
            margin-bottom: 20px;
        }
        .lkn-hn-stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            display: block;
        }
        .lkn-hn-stat-card .stat-label {
            opacity: .85;
        }
        .lkn-hn-stat-sent { background-color: #5bc0de; }
        .lkn-hn-stat-delivered { background-color: #337ab7; }
        .lkn-hn-stat-read { background-color: #5cb85c; }
        .lkn-hn-stat-failed { background-color: #d9534f; }
        .lkn-hn-stat-billable { background-color: #6f42c1; }
        .lkn-hn-stat-free { background-color: #5cb85c; }
        .lkn-hn-stat-paid { background-color: #f0ad4e; }
        .lkn-hn-stat-charges { background-color: #343a40; }
    </style>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="get" action="?" class="form-inline">
                        <input type="hidden" name="module" value="lknhooknotification">
                        <input type="hidden" name="page" value="notification-analytics">

                        <div class="form-group">
                            <label>{lkn_hn_lang text="From"}</label>
                            <input type="date" class="form-control" name="f_date_from" value="{$page_params.filters.f_date_from|default:''}">
                        </div>
                        <div class="form-group" style="margin-left: 10px;">
                            <label>{lkn_hn_lang text="To"}</label>
                            <input type="date" class="form-control" name="f_date_to" value="{$page_params.filters.f_date_to|default:''}">
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                            <i class="fas fa-filter"></i> {lkn_hn_lang text="Filter"}
                        </button>
                        <a class="btn btn-default" href="?module=lknhooknotification&page=notification-analytics">
                            {lkn_hn_lang text="Clear"}
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-sent">
                <span class="stat-value">{$page_params.delivery.sent|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Messages Sent"}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-delivered">
                <span class="stat-value">{$page_params.delivery.delivered|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Delivered"}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-read">
                <span class="stat-value">{$page_params.delivery.read|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Read"}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-failed">
                <span class="stat-value">{$page_params.delivery.failed|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Failed"}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-billable">
                <span class="stat-value">{$page_params.message_billable.billable_total|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Billable Messages"}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-free">
                <span class="stat-value">{$page_params.message_billable.free_delivered|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Free Messages Delivered"}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-paid">
                <span class="stat-value">{$page_params.message_billable.paid_delivered|default:0}</span>
                <span class="stat-label">{lkn_hn_lang text="Paid Messages Delivered"}</span>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="lkn-hn-stat-card lkn-hn-stat-charges">
                <span class="stat-value">
                    {if $page_params.approximate_charges.currency}{$page_params.approximate_charges.currency} {/if}{$page_params.approximate_charges.total|string_format:"%.2f"}
                </span>
                <span class="stat-label">{lkn_hn_lang text="Approximate Total Charges"}</span>
            </div>
        </div>
    </div>

    {if $page_params.approximate_charges.by_category}
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>{lkn_hn_lang text="Approximate Charges by Category"}</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>{lkn_hn_lang text="Category"}</th>
                                    <th>{lkn_hn_lang text="Billable Conversations"}</th>
                                    <th>{lkn_hn_lang text="Configured Rate"}</th>
                                    <th>{lkn_hn_lang text="Subtotal"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$page_params.approximate_charges.by_category key=$category item=$row}
                                    <tr>
                                        <td>{lkn_hn_wa_category_label($category)}</td>
                                        <td>{$row.count}</td>
                                        <td>
                                            {if $row.rate !== null}
                                                {$page_params.approximate_charges.currency} {$row.rate|string_format:"%.2f"}
                                            {else}
                                                <span class="text-muted">{lkn_hn_lang text="Not configured"}</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $row.subtotal !== null}
                                                {$page_params.approximate_charges.currency} {$row.subtotal|string_format:"%.2f"}
                                            {else}
                                                <span class="text-muted">&mdash;</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer text-muted">
                        <small>
                            {lkn_hn_lang text="Estimates only, based on rates you configure in Settings \u2192 WhatsApp Meta. Meta's actual rates vary by country/market and change periodically \u2014 this is not an exact invoice amount."}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>{lkn_hn_lang text="Conversation Analytics"}</strong>
                    <span class="text-muted">
                        ({lkn_hn_lang text="Total conversations"}: {$page_params.conversations.total_conversations|default:0})
                    </span>
                </div>
                <div class="panel-body" style="padding-bottom: 0;">
                    <span class="label label-danger">{lkn_hn_lang text="Billable"}: {$page_params.conversations.billable|default:0}</span>
                    <span class="label label-success">{lkn_hn_lang text="Free"}: {$page_params.conversations.free_or_unbilled|default:0}</span>
                    <span class="label label-default">{lkn_hn_lang text="Unknown"}: {$page_params.conversations.unknown|default:0}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>{lkn_hn_lang text="Category"}</th>
                                <th>{lkn_hn_lang text="Conversations"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$page_params.conversations.by_category key=$category item=$total}
                                <tr>
                                    <td style="text-transform: capitalize;">{$category}</td>
                                    <td>{$total}</td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="2" class="text-muted">
                                        {lkn_hn_lang text="No conversation data yet. Make sure the WhatsApp webhook is configured in the Meta WhatsApp settings page."}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="panel-footer text-muted">
                    <small>
                        {lkn_hn_lang text="WhatsApp conversation categories: marketing, utility, authentication and service. Meta bills per 24-hour conversation window, not per message."}
                    </small>
                    <div style="margin-top: 8px;">
                        <a href="?module=lknhooknotification&page=notification-conversations" class="btn btn-xs btn-link">
                            <i class="fal fa-comments-alt"></i> {lkn_hn_lang text="View every conversation"}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>{lkn_hn_lang text="Most sent notifications (last hour)"}</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>{lkn_hn_lang text="Notification"}</th>
                                <th>{lkn_hn_lang text="Total"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$page_params.top_notifications item=$row}
                                <tr>
                                    <td>{lkn_hn_lang text="{$row->notification}"}</td>
                                    <td>{$row->total}</td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="2" class="text-muted">{lkn_hn_lang text="No notifications sent in the last hour."}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <a href="?module=lknhooknotification&page=notification-reports" class="btn btn-link">
                <i class="fal fa-table"></i> {lkn_hn_lang text="View every message sent"}
            </a>
        </div>
    </div>
{/block}
