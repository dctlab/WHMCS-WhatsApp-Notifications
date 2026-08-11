{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Message Analytics"}
{/block}

{block "page_content"}
    {* ===== Page header ===== *}
    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{lkn_hn_lang text="Analytics"}</h1>
            <div class="dct-page-header-description">
                {lkn_hn_lang text="WhatsApp messaging performance, delivery and usage."}
            </div>
        </div>
    </div>

    {if $page_params.analytics_error}
        <div class="dct-alert dct-alert-danger">
            <i class="far fa-exclamation-circle"></i>
            {$page_params.analytics_error}
        </div>
    {else}
        {* ===== Date range - same preset pattern as the Dashboard, using
           this page's own existing f_date_from/f_date_to param names ===== *}
        <div class="dct-toolbar">
            <div class="dct-toolbar-group" style="flex-direction: row; gap: 6px;">
                {foreach from=$page_params.date_range_options item=$rangeOpt}
                    <a
                        href="?module=dct_whatsapp_notifications&page=notification-analytics&range={$rangeOpt.key}"
                        class="dct-button {if $page_params.date_range_key === $rangeOpt.key}dct-button-primary{else}dct-button-secondary{/if}"
                    >
                        {$rangeOpt.label}
                    </a>
                {/foreach}
            </div>
            <form method="get" action="?" class="dct-toolbar-group" style="flex-direction: row; align-items: flex-end; gap: 8px;">
                <input type="hidden" name="module" value="dct_whatsapp_notifications">
                <input type="hidden" name="page" value="notification-analytics">
                <div>
                    <label class="dct-form-label">{lkn_hn_lang text="From"}</label>
                    <input type="date" class="dct-input" name="f_date_from" value="{$page_params.date_from}">
                </div>
                <div>
                    <label class="dct-form-label">{lkn_hn_lang text="To"}</label>
                    <input type="date" class="dct-input" name="f_date_to" value="{$page_params.date_to}">
                </div>
                <button type="submit" class="dct-button dct-button-secondary">{lkn_hn_lang text="Apply"}</button>
            </form>
        </div>

        {if $page_params.total_sent_attempts == 0 && $page_params.conversations.total_conversations == 0}
            <div class="dct-empty-state">
                <div class="dct-empty-state-icon"><i class="far fa-chart-line"></i></div>
                <div class="dct-empty-state-title">{lkn_hn_lang text="No analytics data available"}</div>
                <div class="dct-empty-state-description">
                    {lkn_hn_lang text="WhatsApp activity will appear here once notifications are sent."}
                </div>
            </div>
        {else}
            {* ===== Delivery KPIs ===== *}
            <div class="row">
                <div class="col-sm-6 col-md-3" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <div class="dct-stat-card-top">
                            <span class="dct-stat-card-label">{lkn_hn_lang text="Messages Sent"}</span>
                            <span class="dct-stat-card-icon"><i class="far fa-paper-plane"></i></span>
                        </div>
                        <div class="dct-stat-card-value">{$page_params.total_sent_attempts}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <div class="dct-stat-card-top">
                            <span class="dct-stat-card-label">{lkn_hn_lang text="Delivered"}</span>
                            <span class="dct-stat-card-icon"><i class="far fa-check"></i></span>
                        </div>
                        <div class="dct-stat-card-value">{$page_params.delivery.delivered|default:0}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <div class="dct-stat-card-top">
                            <span class="dct-stat-card-label">{lkn_hn_lang text="Read"}</span>
                            <span class="dct-stat-card-icon"><i class="far fa-check-double"></i></span>
                        </div>
                        <div class="dct-stat-card-value">{$page_params.delivery.read|default:0}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <div class="dct-stat-card-top">
                            <span class="dct-stat-card-label">{lkn_hn_lang text="Failed"}</span>
                            <span class="dct-stat-card-icon" style="background: var(--dct-danger-light); color: var(--dct-danger);"><i class="far fa-times"></i></span>
                        </div>
                        <div class="dct-stat-card-value">{$page_params.delivery.failed|default:0}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Delivery Rate"}</span>
                        <div class="dct-stat-card-value">{$page_params.delivery_rate}%</div>
                        <div class="dct-stat-card-description">{lkn_hn_lang text="Delivered or read, of all sent"}</div>
                    </div>
                </div>
                <div class="col-sm-4" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Read Rate"}</span>
                        <div class="dct-stat-card-value">{$page_params.read_rate}%</div>
                    </div>
                </div>
                <div class="col-sm-4" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Failure Rate"}</span>
                        <div class="dct-stat-card-value">{$page_params.failure_rate}%</div>
                    </div>
                </div>
            </div>

            <div class="row">
                {* ===== Delivery Performance (lightweight, no chart library) ===== *}
                <div class="col-md-6" style="margin-bottom: 15px;">
                    <div class="dct-card" style="height: 100%;">
                        <div class="dct-card-header">
                            <span class="dct-card-title">{lkn_hn_lang text="Delivery Performance"}</span>
                        </div>
                        <div class="dct-card-body">
                            {assign "dpMax" value=1}
                            {if $page_params.total_sent_attempts > $dpMax}{assign "dpMax" value=$page_params.total_sent_attempts}{/if}

                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px;">
                                    <span>{lkn_hn_lang text="Delivered"}</span>
                                    <span class="dct-text-muted">{$page_params.delivery.delivered|default:0}</span>
                                </div>
                                <div style="background: var(--dct-border-light); border-radius: 4px; height: 10px; overflow: hidden;">
                                    <div style="height: 100%; border-radius: 4px; width: {($page_params.delivery.delivered|default:0) / $dpMax * 100}%; background: var(--dct-primary);"></div>
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px;">
                                    <span>{lkn_hn_lang text="Read"}</span>
                                    <span class="dct-text-muted">{$page_params.delivery.read|default:0}</span>
                                </div>
                                <div style="background: var(--dct-border-light); border-radius: 4px; height: 10px; overflow: hidden;">
                                    <div style="height: 100%; border-radius: 4px; width: {($page_params.delivery.read|default:0) / $dpMax * 100}%; background: var(--dct-success);"></div>
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px;">
                                    <span>{lkn_hn_lang text="Failed"}</span>
                                    <span class="dct-text-muted">{$page_params.delivery.failed|default:0}</span>
                                </div>
                                <div style="background: var(--dct-border-light); border-radius: 4px; height: 10px; overflow: hidden;">
                                    <div style="height: 100%; border-radius: 4px; width: {($page_params.delivery.failed|default:0) / $dpMax * 100}%; background: var(--dct-danger);"></div>
                                </div>
                            </div>
                            <div class="dct-form-help">
                                {lkn_hn_lang text="Out of"} {$page_params.total_sent_attempts} {lkn_hn_lang text="messages sent in this range."}
                            </div>
                        </div>
                    </div>
                </div>

                {* ===== Message Activity (same lightweight chart pattern as the Dashboard) ===== *}
                <div class="col-md-6" style="margin-bottom: 15px;">
                    <div class="dct-card" style="height: 100%;">
                        <div class="dct-card-header">
                            <span class="dct-card-title">{lkn_hn_lang text="Message Activity"}</span>
                            <span class="dct-text-muted dct-text-small">{lkn_hn_lang text="Messages sent per day"}</span>
                        </div>
                        <div class="dct-card-body">
                            {if empty($page_params.daily_activity)}
                                <div class="dct-text-muted dct-text-small">{lkn_hn_lang text="No activity in this range."}</div>
                            {else}
                                {assign "maxDaily" value=1}
                                {foreach from=$page_params.daily_activity item=$dayCount}
                                    {if $dayCount > $maxDaily}{assign "maxDaily" value=$dayCount}{/if}
                                {/foreach}
                                <div
                                    role="img"
                                    aria-label="{lkn_hn_lang text='Bar chart of messages sent per day'}"
                                    style="display: flex; align-items: flex-end; gap: 4px; height: 130px; border-bottom: 1px solid var(--dct-border);"
                                >
                                    {foreach from=$page_params.daily_activity key=$day item=$dayCount}
                                        <div
                                            title="{$day}: {$dayCount}"
                                            style="flex: 1; background: var(--dct-primary); border-radius: 3px 3px 0 0; min-height: 2px; height: {($dayCount / $maxDaily) * 100}%;"
                                        ></div>
                                    {/foreach}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>

            {* ===== Notification Performance - now date-range-aware (reuses
               the Phase 2 getNotificationPerformance() query, replacing the
               previous "last hour only" data with data matching the range
               selected above, same as every other section on this page) ===== *}
            <div class="dct-card">
                <div class="dct-card-header">
                    <span class="dct-card-title">{lkn_hn_lang text="Notification Performance"}</span>
                </div>
                <div class="dct-table-wrap dct-table-responsive">
                    <table class="dct-table">
                        <thead>
                            <tr>
                                <th>{lkn_hn_lang text="Notification"}</th>
                                <th>{lkn_hn_lang text="Sent"}</th>
                                <th>{lkn_hn_lang text="Delivered"}</th>
                                <th>{lkn_hn_lang text="Read"}</th>
                                <th>{lkn_hn_lang text="Failed"}</th>
                                <th>{lkn_hn_lang text="Delivery %"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$page_params.notification_performance item=$notif}
                                <tr>
                                    <td>{lkn_hn_lang text="{$notif.notification}"}</td>
                                    <td>{$notif.sent}</td>
                                    <td>{$notif.delivered}</td>
                                    <td>{$notif.read}</td>
                                    <td>{$notif.failed}</td>
                                    <td>{$notif.delivery_rate}%</td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="6" class="dct-text-muted">{lkn_hn_lang text="No notifications sent in this range."}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>

            {* ===== Usage & Estimated Charges - same existing calculation,
               unchanged, clearly labeled as estimates ===== *}
            <div class="row">
                <div class="col-sm-4" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Billable Messages"}</span>
                        <div class="dct-stat-card-value">{$page_params.message_billable.billable_total|default:0}</div>
                    </div>
                </div>
                <div class="col-sm-4" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Free Messages Delivered"}</span>
                        <div class="dct-stat-card-value">{$page_params.message_billable.free_delivered|default:0}</div>
                    </div>
                </div>
                <div class="col-sm-4" style="margin-bottom: 15px;">
                    <div class="dct-stat-card">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Estimated Charges"}</span>
                        <div class="dct-stat-card-value">
                            {if $page_params.approximate_charges.currency}{$page_params.approximate_charges.currency} {/if}{$page_params.approximate_charges.total|string_format:"%.2f"}
                        </div>
                        <div class="dct-stat-card-description">{lkn_hn_lang text="Estimate only, not an invoice"}</div>
                    </div>
                </div>
            </div>

            {if $page_params.approximate_charges.by_category}
                <div class="dct-card">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Estimated Charges by Category"}</span>
                    </div>
                    <div class="dct-table-wrap">
                        <table class="dct-table">
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
                                        <td style="text-transform: capitalize;">{lkn_hn_wa_category_label($category)}</td>
                                        <td>{$row.count}</td>
                                        <td>
                                            {if $row.rate !== null}
                                                {$page_params.approximate_charges.currency} {$row.rate|string_format:"%.2f"}
                                            {else}
                                                <span class="dct-text-muted">{lkn_hn_lang text="Not configured"}</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $row.subtotal !== null}
                                                {$page_params.approximate_charges.currency} {$row.subtotal|string_format:"%.2f"}
                                            {else}
                                                <span class="dct-text-muted">&mdash;</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                    <div class="dct-card-footer dct-text-muted dct-text-small">
                        {lkn_hn_lang text="Estimates only, based on rates you configure in Settings \u2192 WhatsApp Meta. Meta's actual rates vary by country/market and change periodically \u2014 this is not an exact invoice amount."}
                    </div>
                </div>
            {/if}

            {* ===== Conversation Analytics - preserved exactly, restyled ===== *}
            <div class="dct-card">
                <div class="dct-card-header">
                    <span class="dct-card-title">{lkn_hn_lang text="Conversation Analytics"}</span>
                    <span class="dct-text-muted dct-text-small">
                        ({lkn_hn_lang text="Total conversations"}: {$page_params.conversations.total_conversations|default:0})
                    </span>
                </div>
                <div class="dct-card-body" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="dct-status-badge dct-status-badge-danger">{lkn_hn_lang text="Billable"}: {$page_params.conversations.billable|default:0}</span>
                    <span class="dct-status-badge dct-status-badge-success">{lkn_hn_lang text="Free"}: {$page_params.conversations.free_or_unbilled|default:0}</span>
                    <span class="dct-status-badge dct-status-badge-neutral">{lkn_hn_lang text="Unknown"}: {$page_params.conversations.unknown|default:0}</span>
                </div>
                <div class="dct-table-wrap">
                    <table class="dct-table">
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
                                    <td colspan="2" class="dct-text-muted">
                                        {lkn_hn_lang text="No conversation data yet. Make sure the WhatsApp webhook is configured in the Meta WhatsApp settings page."}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="dct-card-footer dct-text-muted dct-text-small">
                    {lkn_hn_lang text="WhatsApp conversation categories: marketing, utility, authentication and service. Meta bills per 24-hour conversation window, not per message."}
                    <div style="margin-top: 8px;">
                        <a href="?module=dct_whatsapp_notifications&page=notification-conversations" class="dct-button dct-button-ghost dct-text-small">
                            <i class="fal fa-comments-alt"></i> {lkn_hn_lang text="View every conversation"}
                        </a>
                    </div>
                </div>
            </div>

            <a href="?module=dct_whatsapp_notifications&page=notification-reports" class="dct-button dct-button-ghost">
                <i class="fal fa-table"></i> {lkn_hn_lang text="View every message sent"}
            </a>
        {/if}
    {/if}
{/block}
