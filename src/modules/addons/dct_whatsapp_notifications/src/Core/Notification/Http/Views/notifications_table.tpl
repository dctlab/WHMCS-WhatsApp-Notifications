{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Notifications"}
{/block}

{block "page_content"}
    {* ===== Page header ===== *}
    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{lkn_hn_lang text="Notifications"}</h1>
            <div class="dct-page-header-description">
                {lkn_hn_lang text="Automate WhatsApp communications for your WHMCS events."}
            </div>
        </div>
        <div class="dct-page-header-actions">
            <a
                class="dct-button dct-button-ghost"
                href="https://dctlab.directcybertech.com/"
                target="_blank"
            >
                <i class="far fa-question-circle"></i>
                {lkn_hn_lang text="How to create your own notification?"}
            </a>
        </div>
    </div>
    {* Note: there is no single "+ Create Notification" action here on purpose -
       notification TYPES are added by creating a new PHP class in the codebase,
       not through this UI. The real, existing "create" action is per-notification
       ("Setup template" below), preserved exactly as it already works. *}

    {if $page_params.must_block_add_other_notifications}
        <div class="dct-alert dct-alert-warning" style="justify-content: space-between; align-items: center;">
            <div>
                {lkn_hn_lang text="You are on free plan and limited to 3 notifications."}
                {if $page_params.must_block_edit_notification}
                    <br>
                    {lkn_hn_lang text="You have to keep only three notifications configured to be able to use the module."}
                {/if}
            </div>
            <a class="dct-button dct-button-success" href="https://dctlab.directcybertech.com/" target="_blank">
                <i class="far fa-plus"></i> {lkn_hn_lang text="Get paid plan now for more notifications!"}
            </a>
        </div>
    {/if}

    {* ===== Toolbar: search + filters (client-side only - the full dataset is
       already loaded on the page via file-based discovery, not a paginated DB
       query, so filtering it in JS adds zero new queries/requests) ===== *}
    <div class="dct-toolbar">
        <div class="dct-toolbar-group" style="flex: 1 1 240px;">
            <label class="dct-form-label">{lkn_hn_lang text="Search"}</label>
            <input
                type="text"
                id="dctNotifSearch"
                class="dct-input"
                placeholder="{lkn_hn_lang text='Search by name or trigger...'}"
            >
        </div>
        <div class="dct-toolbar-group">
            <label class="dct-form-label">{lkn_hn_lang text="Status"}</label>
            <select id="dctNotifStatusFilter" class="dct-select">
                <option value="">{lkn_hn_lang text="Any"}</option>
                <option value="enabled">{lkn_hn_lang text="Enabled"}</option>
                <option value="disabled">{lkn_hn_lang text="Disabled"}</option>
            </select>
        </div>
        <div class="dct-toolbar-group">
            <label class="dct-form-label">{lkn_hn_lang text="Provider"}</label>
            <select id="dctNotifProviderFilter" class="dct-select">
                <option value="">{lkn_hn_lang text="Any"}</option>
                {foreach from=$page_params.platform_list item=$platform}
                    <option value="{$platform->value}">{$platform->label()}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="dct-card">
        <div class="dct-table-wrap dct-table-responsive">
            <table class="dct-table" id="dctNotificationsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{lkn_hn_lang text="Status"}</th>
                        <th>{lkn_hn_lang text="Notification"}</th>
                        <th>{lkn_hn_lang text="Templates"}</th>
                    </tr>
                </thead>

                <tbody>
                    {foreach from=$page_params.notifications item=$notification key=$key}
                        {assign var="isEnabled" value=(isset($notification->templates) && count($notification->templates) > 0)}
                        {capture name="notifProviders"}{if isset($notification->templates)}{foreach from=$notification->templates item=$tplForFilter}{$tplForFilter->platform->value} {/foreach}{/if}{/capture}
                        <tr
                            class="dct-notif-row"
                            data-status="{if $isEnabled}enabled{else}disabled{/if}"
                            data-providers="{$smarty.capture.notifProviders|trim}"
                            data-search="{$notification->code|lower} {$notification->hook->value|lower}"
                        >
                            <td>{$key + 1}</td>
                            <td>
                                {if $isEnabled}
                                    <span class="dct-status-badge dct-status-badge-success">{lkn_hn_lang text="Enabled"}</span>
                                {else}
                                    <span class="dct-status-badge dct-status-badge-neutral">{lkn_hn_lang text="Disabled"}</span>
                                {/if}
                            </td>
                            <td>
                                <div
                                    {if $notification->description}
                                        data-toggle="popover"
                                        title="{lkn_hn_lang text=$notification->code}"
                                        data-content="{lkn_hn_lang text=$notification->description}"
                                        data-trigger="hover"
                                    {/if}
                                >
                                    <strong>{lkn_hn_lang text=$notification->code}</strong>
                                    {if $notification->description}<i class="far fa-question-circle dct-text-muted"></i>{/if}

                                    {if $notification->hook->value}
                                        <br>
                                        <a
                                            href="https://developers.whmcs.com/hooks/hook-index/#:~:text={$notification->hook->value}"
                                            target="_blank"
                                            class="dct-text-muted dct-text-small"
                                        >{$notification->hook->value}</a>
                                    {/if}
                                </div>
                            </td>
                            <td>
                                {if !$page_params.must_block_add_other_notifications}
                                    <a
                                        class="dct-button dct-button-ghost dct-text-small"
                                        href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/templates/new"
                                        style="margin-bottom: 6px;"
                                    >
                                        <i class="fas fa-plus"></i> {lkn_hn_lang text="Setup template"}
                                    </a>
                                {/if}

                                {if isset($notification->templates) && (count($notification->templates) > 0)}
                                    <div class="dct-table-wrap" style="margin-top: 4px;">
                                        <table class="dct-table dct-table-compact">
                                            <tbody>
                                                {foreach from=$notification->templates item=$template}
                                                    <tr>
                                                        <td style="width: 70px;">
                                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                                {if !in_array($template->platform, $page_params.platform_list)}
                                                                    <span
                                                                        data-toggle="tooltip"
                                                                        data-placement="left"
                                                                        title="{lkn_hn_lang text='This template will not be sent because its is disabled.'}"
                                                                        class="dct-status-badge dct-status-badge-danger"
                                                                        style="padding: 2px 6px;"
                                                                    >!</span>
                                                                {/if}
                                                                <span class="dct-text-muted">{$template->lang}</span>
                                                            </div>
                                                        </td>
                                                        <td style="width: 140px;">
                                                            <span class="dct-text-muted">{$template->platform->label()}</span>
                                                        </td>
                                                        <td>
                                                            <span
                                                                {if $template->platform->value !== 'wp' && strlen($template->template) > 60}
                                                                    data-toggle="tooltip"
                                                                    data-placement="left"
                                                                    title="{$template->template}"
                                                                {/if}
                                                                class="dct-text-muted"
                                                            >
                                                                {if strlen($template->template) > 60}
                                                                    {substr($template->template, 0, 60)}...
                                                                {else}
                                                                    {$template->template}
                                                                {/if}
                                                            </span>
                                                        </td>
                                                        <td class="dct-table-actions" style="width: 150px;">
                                                            {if !$page_params.must_block_edit_notification}
                                                                <a
                                                                    class="dct-button dct-button-primary dct-text-small"
                                                                    style="padding: 4px 10px;"
                                                                    href="{$lkn_hn_base_endpoint}&page=notifications/{$notification->code}/templates/{$template->lang}"
                                                                >
                                                                    {lkn_hn_lang text="Edit"}
                                                                </a>
                                                            {/if}

                                                            <form
                                                                id="delete-notif-form-{$notification->code}-{$template->lang}"
                                                                style="display: none;"
                                                                target="_self"
                                                                method="POST"
                                                            >
                                                                <input type="hidden" name="delete-template">
                                                                <input type="hidden" name="notification-code" value="{$notification->code}">
                                                                <input type="hidden" name="template-locale" value="{$template->lang}">
                                                            </form>

                                                            <button
                                                                type="submit"
                                                                form="delete-notif-form-{$notification->code}-{$template->lang}"
                                                                class="dct-button dct-button-danger dct-text-small"
                                                                style="padding: 4px 10px;"
                                                                onclick="return window.confirm('{lkn_hn_lang text='Are you sure you want to delete this template?'}')"
                                                            >
                                                                {lkn_hn_lang text="Delete"}
                                                            </button>
                                                        </td>
                                                    </tr>
                                                {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
                                {/if}
                            </td>
                        </tr>
                    {foreachelse}
                        <tr>
                            <td colspan="4">
                                <div class="dct-empty-state">
                                    <div class="dct-empty-state-icon"><i class="far fa-bell-slash"></i></div>
                                    <div class="dct-empty-state-title">{lkn_hn_lang text="No WhatsApp notifications configured"}</div>
                                    <div class="dct-empty-state-description">
                                        {lkn_hn_lang text="Set up a template on one of the notification types below to start automating WhatsApp messages from WHMCS events."}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div id="dctNotifNoResults" class="dct-empty-state" style="display: none;">
        <div class="dct-empty-state-icon"><i class="far fa-search"></i></div>
        <div class="dct-empty-state-title">{lkn_hn_lang text="No matching notifications found."}</div>
        <button type="button" class="dct-button dct-button-secondary" id="dctNotifClearFilters">
            {lkn_hn_lang text="Clear Filters"}
        </button>
    </div>

    <script>
        (function () {
            var $ = window.jQuery;

            if (!$) {
                return;
            }

            var $rows = $('#dctNotificationsTable tbody tr.dct-notif-row');
            var $search = $('#dctNotifSearch');
            var $statusFilter = $('#dctNotifStatusFilter');
            var $providerFilter = $('#dctNotifProviderFilter');
            var $noResults = $('#dctNotifNoResults');
            var $table = $('#dctNotificationsTable').closest('.dct-card');

            function applyFilters() {
                var term = ($search.val() || '').toLowerCase().trim();
                var status = $statusFilter.val();
                var provider = $providerFilter.val();
                var visibleCount = 0;

                $rows.each(function () {
                    var $row = $(this);
                    var matchesSearch = !term || $row.data('search').indexOf(term) !== -1;
                    var matchesStatus = !status || $row.data('status') === status;
                    var providers = ('' + $row.data('providers')).split(/\s+/).filter(Boolean);
                    var matchesProvider = !provider || providers.indexOf(provider) !== -1;
                    var visible = matchesSearch && matchesStatus && matchesProvider;

                    $row.toggle(visible);

                    if (visible) {
                        visibleCount++;
                    }
                });

                var hasActiveFilter = term || status || provider;
                $noResults.toggle(hasActiveFilter && visibleCount === 0);
                $table.toggle(!(hasActiveFilter && visibleCount === 0));
            }

            $search.on('keyup', applyFilters);
            $statusFilter.on('change', applyFilters);
            $providerFilter.on('change', applyFilters);

            $('#dctNotifClearFilters').on('click', function () {
                $search.val('');
                $statusFilter.val('');
                $providerFilter.val('');
                applyFilters();
            });
        })();
    </script>
{/block}
