<div class="panel panel-default">
    <div
        class="panel-heading"
        role="tab"
        id="headingFour"
    >
        <h4 class="panel-title">
            <a
                class="collapsed"
                role="button"
                data-toggle="collapse"
                data-parent="#accordion"
                href="#collapse4"
                aria-expanded="false"
                aria-controls="collapse4"
            >
                {lkn_hn_lang text="Progress report"}
            </a>
        </h4>
    </div>
    <div
        id="collapse4"
        class="panel-collapse collapse in"
        role="tabpanel"
        aria-labelledby="headingFour"
    >
        <div class="dct-table-wrap dct-table-responsive">
            <table class="dct-table">
                <thead>
                    <tr>
                        <th>{lkn_hn_lang text="Status"}</th>
                        <th>{lkn_hn_lang text="Message"}</th>
                        <th>{lkn_hn_lang text="Client"}</th>
                        <th>{lkn_hn_lang text="Target"}</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    {foreach from=$page_params.bulk_notifications_list item=$queued}
                        <tr>
                            <td>
                                <span class="dct-status-badge {if $queued->status->value === 'error'}dct-status-badge-danger{elseif $queued->status->value === 'aborted'}dct-status-badge-warning{elseif $queued->status->value === 'waiting'}dct-status-badge-info{else}dct-status-badge-success{/if}">
                                    {lkn_hn_lang text="{$queued->status->label()}"}
                                </span>
                            </td>
                            <td>
                                {if $queued->reportData['msg']}
                                    {lkn_hn_lang text="{$queued->reportData['msg']}"}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>
                                <a
                                    href="clientssummary.php?userid={$queued->clientId}"
                                    target="_blank"
                                >
                                    #{$queued->clientId} - {$queued->clientData['full_name']}
                                </a>
                            </td>
                            <td>
                                {if $queued->reportData['target']}
                                    {$queued->reportData['target']}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>
                                {if $queued->status->value === 'error' && $page_params.bulk->status->value === 'in_progress'}
                                    <button
                                        type="submit"
                                        name="resend-notification"
                                        class="dct-button dct-button-primary dct-text-small"
                                        style="padding: 4px 10px;"
                                        value="{$queued->id}"
                                    >
                                        {lkn_hn_lang text="Resend"}
                                    </button>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
