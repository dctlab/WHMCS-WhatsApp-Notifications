{extends "layout/layout.tpl"}

{assign var="is_homepage" value=true}

{block "page_content"}
    {$page_params.new_version_alert}

    {if !$page_params.dismiss_v400_alert}
        <div class="row">
            <div class="col-md-12">
                {include "{$lkn_hn_layout_path}/components/v400_alert.tpl"}
            </div>
        </div>
    {/if}

    {* ===== Header ===== *}
    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{lkn_hn_lang text="DCTLAB WhatsApp"}</h1>
            <div class="dct-page-header-description">{lkn_hn_lang text="Enterprise WhatsApp Messaging for WHMCS"}</div>
        </div>
        <div class="dct-page-header-actions">
            <span
                class="dct-status-badge {($page_params.license_status === 'yes') ? 'dct-status-badge-success' : 'dct-status-badge-neutral'}"
            >
                {if $page_params.license_status === 'yes'}
                    {lkn_hn_lang text="Pro"}
                {else}
                    {lkn_hn_lang text="Free"}
                {/if}
            </span>
            {if $page_params.license_status !== 'yes'}
                <a href="https://dctlab.directcybertech.com/" target="_blank" class="dct-button dct-button-secondary">
                    <i class="far fa-plus"></i> {lkn_hn_lang text="Get paid plan"}
                </a>
            {/if}
        </div>
    </div>

    {if $page_params.license_status === 'no'}
        <div class="dct-alert dct-alert-info">
            <i class="far fa-info-circle"></i>
            {lkn_hn_lang text="You are limited to 3 notifications per platform."}
        </div>
    {elseif $page_params.license_status === 'unable-to-check-license'}
        <div class="dct-alert dct-alert-warning">
            <i class="far fa-exclamation-triangle"></i>
            {lkn_hn_lang text="There was an error checking your license."}
        </div>
    {/if}

    {* ===== Provider status ===== *}
    <div class="dct-section-title">{lkn_hn_lang text="WhatsApp Providers"}</div>
    <div class="row">
        {foreach from=$page_params.providers key=$providerKey item=$provider}
            {if $provider.enabled}
                <div class="col-sm-6 col-md-4 col-lg-2 dct-provider-col" style="margin-bottom: 15px;">
                    <div class="dct-card" style="height: 100%;">
                        <div class="dct-card-body">
                            <div class="dct-card-title" style="text-transform: capitalize; margin-bottom: 8px;">
                                {$providerKey}
                            </div>
                            {if $provider.configured}
                                <span class="dct-status-badge dct-status-badge-info">{lkn_hn_lang text="Configured"}</span>
                            {else}
                                <span class="dct-status-badge dct-status-badge-warning">{lkn_hn_lang text="Not Configured"}</span>
                            {/if}
                        </div>
                    </div>
                </div>
            {/if}
        {foreachelse}
            <div class="col-md-12">
                <div class="dct-empty-state">
                    <div class="dct-empty-state-icon"><i class="far fa-plug"></i></div>
                    <div class="dct-empty-state-title">{lkn_hn_lang text="No WhatsApp provider enabled yet"}</div>
                    <div class="dct-empty-state-description">
                        {lkn_hn_lang text="Enable and configure a provider to start sending WhatsApp notifications."}
                    </div>
                    <a href="?module=dct_whatsapp_notifications&page=platforms/wp/settings" class="dct-button dct-button-primary">
                        {lkn_hn_lang text="Configure WhatsApp"}
                    </a>
                </div>
            </div>
        {/foreach}
    </div>

    {if $page_params.dashboard_error}
        <div class="dct-alert dct-alert-danger">
            <i class="far fa-exclamation-circle"></i>
            {$page_params.dashboard_error}
        </div>
    {else}
        {* ===== Date range ===== *}
        <div class="dct-toolbar">
            <div class="dct-toolbar-group" style="flex-direction: row; gap: 6px;">
                {foreach from=$page_params.date_range_options item=$rangeOpt}
                    <a
                        href="?module=dct_whatsapp_notifications&page=home&range={$rangeOpt.key}"
                        class="dct-button {if $page_params.date_range_key === $rangeOpt.key}dct-button-primary{else}dct-button-secondary{/if}"
                    >
                        {$rangeOpt.label}
                    </a>
                {/foreach}
            </div>
            <form method="get" action="?" class="dct-toolbar-group" style="flex-direction: row; align-items: flex-end; gap: 8px;">
                <input type="hidden" name="module" value="dct_whatsapp_notifications">
                <input type="hidden" name="page" value="home">
                <div>
                    <label class="dct-form-label">{lkn_hn_lang text="From"}</label>
                    <input type="date" class="dct-input" name="date_from" value="{$page_params.date_from}">
                </div>
                <div>
                    <label class="dct-form-label">{lkn_hn_lang text="To"}</label>
                    <input type="date" class="dct-input" name="date_to" value="{$page_params.date_to}">
                </div>
                <button type="submit" class="dct-button dct-button-secondary">{lkn_hn_lang text="Apply"}</button>
            </form>
        </div>

        {assign var="d" value=$page_params.dashboard}

        {* ===== KPI cards ===== *}
        <div class="row">
            <div class="col-sm-6 col-md-4 col-lg-2" style="margin-bottom: 15px;">
                <div class="dct-stat-card">
                    <div class="dct-stat-card-top">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Messages Sent"}</span>
                        <span class="dct-stat-card-icon"><i class="far fa-paper-plane"></i></span>
                    </div>
                    <div class="dct-stat-card-value">{$d.delivery.sent|default:0}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-2" style="margin-bottom: 15px;">
                <div class="dct-stat-card">
                    <div class="dct-stat-card-top">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Delivered"}</span>
                        <span class="dct-stat-card-icon"><i class="far fa-check"></i></span>
                    </div>
                    <div class="dct-stat-card-value">{$d.delivery.delivered|default:0}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-2" style="margin-bottom: 15px;">
                <div class="dct-stat-card">
                    <div class="dct-stat-card-top">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Read"}</span>
                        <span class="dct-stat-card-icon"><i class="far fa-check-double"></i></span>
                    </div>
                    <div class="dct-stat-card-value">{$d.delivery.read|default:0}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-2" style="margin-bottom: 15px;">
                <div class="dct-stat-card">
                    <div class="dct-stat-card-top">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Failed"}</span>
                        <span class="dct-stat-card-icon" style="background: var(--dct-danger-light); color: var(--dct-danger);"><i class="far fa-times"></i></span>
                    </div>
                    <div class="dct-stat-card-value">{$d.delivery.failed|default:0}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-2" style="margin-bottom: 15px;">
                <div class="dct-stat-card">
                    <div class="dct-stat-card-top">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Billable Conversations"}</span>
                        <span class="dct-stat-card-icon"><i class="far fa-comments"></i></span>
                    </div>
                    <div class="dct-stat-card-value">{$d.conversations.billable|default:0}</div>
                    <div class="dct-stat-card-description">{lkn_hn_lang text="of"} {$d.conversations.total_conversations|default:0} {lkn_hn_lang text="total"}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-2" style="margin-bottom: 15px;">
                <div class="dct-stat-card">
                    <div class="dct-stat-card-top">
                        <span class="dct-stat-card-label">{lkn_hn_lang text="Estimated Charges"}</span>
                        <span class="dct-stat-card-icon"><i class="far fa-receipt"></i></span>
                    </div>
                    <div class="dct-stat-card-value">{$d.approximate_charges.currency} {$d.approximate_charges.total|string_format:"%.2f"}</div>
                    <div class="dct-stat-card-description">{lkn_hn_lang text="Estimate only, not an invoice"}</div>
                </div>
            </div>
        </div>

        <div class="row">
            {* ===== Message Activity (lightweight chart, no external library) ===== *}
            <div class="col-md-7" style="margin-bottom: 15px;">
                <div class="dct-card" style="height: 100%;">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Message Activity"}</span>
                        <span class="dct-text-muted dct-text-small">{lkn_hn_lang text="Messages sent per day"}</span>
                    </div>
                    <div class="dct-card-body">
                        {if !$d.daily_activity_has_data}
                            <div class="dct-empty-state">
                                <div class="dct-empty-state-icon"><i class="far fa-chart-line"></i></div>
                                <div class="dct-empty-state-title">{lkn_hn_lang text="No WhatsApp activity yet"}</div>
                                <div class="dct-empty-state-description">
                                    {lkn_hn_lang text="Messages sent through DCTLAB WhatsApp will appear here."}
                                </div>
                            </div>
                        {else}
                            {assign var="maxDaily" value=1}
                            {foreach from=$d.daily_activity item=$dayCount}
                                {if $dayCount > $maxDaily}{assign var="maxDaily" value=$dayCount}{/if}
                            {/foreach}
                            <div
                                role="img"
                                aria-label="{lkn_hn_lang text='Bar chart of messages sent per day'}"
                                style="display: flex; align-items: flex-end; gap: 4px; height: 160px; border-bottom: 1px solid var(--dct-border);"
                            >
                                {foreach from=$d.daily_activity key=$day item=$dayCount}
                                    <div
                                        title="{$day}: {$dayCount}"
                                        style="flex: 1; background: var(--dct-primary); border-radius: 3px 3px 0 0; min-height: 2px; height: {($dayCount / $maxDaily) * 100}%;"
                                    ></div>
                                {/foreach}
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 6px;">
                                {foreach from=$d.daily_activity key=$day item=$dayCount name=dayLoop}
                                    {if $smarty.foreach.dayLoop.first || $smarty.foreach.dayLoop.last || $smarty.foreach.dayLoop.total <= 10}
                                        <span class="dct-text-muted dct-text-small">{$day|date_format:"%d %b"}</span>
                                    {/if}
                                {/foreach}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>

            {* ===== WhatsApp Usage ===== *}
            <div class="col-md-5" style="margin-bottom: 15px;">
                <div class="dct-card" style="height: 100%;">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="WhatsApp Usage"}</span>
                    </div>
                    <div class="dct-table-wrap">
                        <table class="dct-table">
                            <thead>
                                <tr>
                                    <th>{lkn_hn_lang text="Category"}</th>
                                    <th>{lkn_hn_lang text="Conversations"}</th>
                                    <th>{lkn_hn_lang text="Est. Charge"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$d.approximate_charges.by_category key=$category item=$catData}
                                    <tr>
                                        <td style="text-transform: capitalize;">{$category|replace:'_':' '}</td>
                                        <td>{$catData.count}</td>
                                        <td>
                                            {if $catData.subtotal !== null}
                                                {$d.approximate_charges.currency} {$catData.subtotal|string_format:"%.2f"}
                                            {else}
                                                <span class="dct-text-muted">{lkn_hn_lang text="Unknown"}</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {foreachelse}
                                    <tr>
                                        <td colspan="3" class="dct-text-muted">{lkn_hn_lang text="No conversation activity in this range."}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {* ===== Notification Performance ===== *}
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
                        {foreach from=$d.notification_performance item=$notif}
                            <tr>
                                <td>{$notif.notification}</td>
                                <td>{$notif.sent}</td>
                                <td>{$notif.delivered}</td>
                                <td>{$notif.read}</td>
                                <td>{$notif.failed}</td>
                                <td>{$notif.delivery_rate}%</td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="6">
                                    <div class="dct-empty-state" style="padding: 20px;">
                                        <div class="dct-empty-state-description">
                                            {lkn_hn_lang text="No notifications have been sent in this range yet."}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            {* ===== Recent Activity ===== *}
            <div class="col-md-7" style="margin-bottom: 15px;">
                <div class="dct-card" style="height: 100%;">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Recent Activity"}</span>
                    </div>
                    <div class="dct-card-body" style="padding: 0;">
                        {if empty($d.recent_activity)}
                            <div class="dct-empty-state">
                                <div class="dct-empty-state-title">{lkn_hn_lang text="No activity yet"}</div>
                            </div>
                        {else}
                            <ul style="list-style: none; margin: 0; padding: 0;">
                                {foreach from=$d.recent_activity item=$activity}
                                    <li style="padding: 10px 16px; border-bottom: 1px solid var(--dct-border-light); display: flex; align-items: center; gap: 10px;">
                                        {if $activity->status === 'sent' && $activity->delivery_status !== 'failed'}
                                            <i class="far fa-check-circle" style="color: var(--dct-success);" aria-hidden="true"></i>
                                        {else}
                                            <i class="far fa-times-circle" style="color: var(--dct-danger);" aria-hidden="true"></i>
                                        {/if}
                                        <span style="flex: 1;">
                                            {$activity->notification}
                                            {if $activity->delivery_status}
                                                <span class="dct-text-muted">&mdash; {$activity->delivery_status}</span>
                                            {/if}
                                        </span>
                                        <span class="dct-text-muted dct-text-small">{$activity->created_at}</span>
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}
                    </div>
                </div>
            </div>

            {* ===== Quick Actions ===== *}
            <div class="col-md-5" style="margin-bottom: 15px;">
                <div class="dct-card" style="height: 100%;">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Quick Actions"}</span>
                    </div>
                    <div class="dct-card-body" style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="?module=dct_whatsapp_notifications&page=notifications" class="dct-button dct-button-secondary" style="justify-content: flex-start;">
                            <i class="far fa-bell"></i> {lkn_hn_lang text="Manage Notifications"}
                        </a>
                        <a href="?module=dct_whatsapp_notifications&page=notification-chat" class="dct-button dct-button-secondary" style="justify-content: flex-start;">
                            <i class="fal fa-comments-alt"></i> {lkn_hn_lang text="View Conversations"}
                        </a>
                        <a href="?module=dct_whatsapp_notifications&page=notification-reports" class="dct-button dct-button-secondary" style="justify-content: flex-start;">
                            <i class="fal fa-table"></i> {lkn_hn_lang text="View Reports"}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    {/if}

    {* ===== Help / Documentation (preserved from the previous homepage, restyled) ===== *}
    <div class="row">
        <div class="col-sm-6" style="margin-bottom: 15px;">
            <div class="dct-card">
                <div class="dct-card-header">
                    <span class="dct-card-title">{lkn_hn_lang text="Contribute and request for new features!"}</span>
                </div>
                <div class="dct-card-body">
                    <ul class="nav nav-pills nav-stacked">
                        <li role="presentation">
                            <a href="https://dctlab.directcybertech.com/" target="_blank">
                                <i class="fas fa-exclamation-triangle"></i> {lkn_hn_lang text="Report error"}
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="https://dctlab.directcybertech.com/" target="_blank">
                                <i class="fas fa-plus-circle"></i> {lkn_hn_lang text="Request new feature"}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-sm-6" style="margin-bottom: 15px;">
            <div class="dct-card">
                <div class="dct-card-header">
                    <span class="dct-card-title">{lkn_hn_lang text="Documentation"}</span>
                </div>
                <div class="dct-card-body">
                    <ul class="nav nav-pills nav-stacked">
                        <li role="presentation">
                            <a href="https://dctlab.directcybertech.com/" target="_blank">
                                <i class="fas fa-cog"></i> {lkn_hn_lang text="How to setup the module?"}
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="https://dctlab.directcybertech.com/" target="_blank">
                                <i class="fas fa-cloud-download"></i> {lkn_hn_lang text="How to install new notifications?"}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
{/block}
