{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="Bulk Messages" params=[$page_params.platform_title]}
{/block}

{block "page_content"}
    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <h1 class="dct-page-title">{lkn_hn_lang text="Bulk Messages"}</h1>
        </div>
        <div class="dct-page-header-actions">
            <a class="dct-button dct-button-primary" href="?module=dct_whatsapp_notifications&amp;page=bulk/new">
                <i class="far fa-plus"></i>
                {lkn_hn_lang text="New bulk message"}
            </a>
        </div>
    </div>

    {if count($page_params.bulks) === 0}
        <div class="dct-empty-state">
            <div class="dct-empty-state-icon"><i class="far fa-mail-bulk"></i></div>
            <div class="dct-empty-state-title">{lkn_hn_lang text="No bulk messages."}</div>
            <a class="dct-button dct-button-primary" href="?module=dct_whatsapp_notifications&amp;page=bulk/new">
                <i class="far fa-plus"></i>
                {lkn_hn_lang text="New bulk message"}
            </a>
        </div>
    {else}
        <div class="dct-card">
            <div class="dct-table-wrap dct-table-responsive">
                <table class="dct-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{lkn_hn_lang text="Title"}</th>
                            <th>{lkn_hn_lang text="Status"}</th>
                            <th>{lkn_hn_lang text="Description"}</th>
                            <th>{lkn_hn_lang text="Progress"}</th>
                            <th>{lkn_hn_lang text="Platform"}</th>
                            <th>{lkn_hn_lang text="Start date"}</th>
                            <th>{lkn_hn_lang text="Completed at"}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        {foreach from=$page_params.bulks item=$bulk key=$key}
                            <tr>
                                <td>{$bulk->id}</td>
                                <td>{$bulk->title}</td>
                                <td>
                                    {if "now"|date_format:"%Y-%m-%d %H:%M:%S" >= $bulk->startAt->format('Y-m-d H:i:s')}
                                        <span class="dct-status-badge {if $bulk->status->value === 'aborted'}dct-status-badge-warning{elseif $bulk->status->value === 'completed'}dct-status-badge-success{else}dct-status-badge-info{/if}">
                                            {$bulk->status->label()}
                                        </span>
                                    {else}
                                        <span class="dct-status-badge dct-status-badge-neutral">
                                            {lkn_hn_lang text="Awaiting"}
                                        </span>
                                    {/if}
                                </td>
                                <td>{$bulk->description}</td>
                                <td style="min-width: 140px;">
                                    <div style="background: var(--dct-border-light); border-radius: 4px; height: 16px; overflow: hidden; position: relative;">
                                        <div style="height: 100%; border-radius: 4px; width: {$bulk->progress}%; background: var(--dct-primary);"></div>
                                        <span style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--dct-text);">
                                            {$bulk->progress}%
                                        </span>
                                    </div>
                                </td>
                                <td>{$bulk->platform->label()}</td>
                                <td class="dct-text-small">{$bulk->startAt->format('M d Y - H:i')}</td>
                                <td class="dct-text-small">
                                    {if $bulk->completedAt}
                                        {$bulk->completedAt->format('M d Y - H:i')}
                                    {else}
                                        -
                                    {/if}
                                </td>
                                <td>
                                    <a
                                        class="dct-button dct-button-ghost dct-text-small"
                                        href="?module=dct_whatsapp_notifications&page=bulks/{$bulk->id}"
                                    >
                                        {lkn_hn_lang text="View bulk"}
                                    </a>
                                    {if "now"|date_format:"%Y-%m-%d %H:%M:%S" < $bulk->startAt->format('Y-m-d H:i:s')}
                                        <a
                                            class="dct-button dct-button-ghost dct-text-small"
                                            href="?module=dct_whatsapp_notifications&page=bulk/list&send-now=1&bulk-id={$bulk->id}"
                                        >
                                            {lkn_hn_lang text="Send now"}
                                        </a>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}
{/block}
