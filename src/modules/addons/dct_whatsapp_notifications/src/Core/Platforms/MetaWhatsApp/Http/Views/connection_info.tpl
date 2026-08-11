<div class="dct-card">
    <div class="dct-card-body" style="text-align: center;">
        {if $page_params.step === 1}
            <div class="dct-empty-state">
                <div class="dct-empty-state-icon"><i class="far fa-plug"></i></div>
                <div class="dct-empty-state-title">{lkn_hn_lang text="Please, fill the setting on the side."}</div>
            </div>
        {elseif $page_params.step === 'error'}
            {* The raw API error response is deliberately not shown here -
               it can contain internal details not meant for on-screen
               display. The controller already passes it as
               $page_params.error, unchanged - this view simply chooses not
               to render it, pointing to the module logs instead where the
               full detail remains available for debugging. *}
            <div class="dct-alert dct-alert-danger" style="text-align: left;">
                <i class="far fa-exclamation-circle"></i>
                {lkn_hn_lang text="Unable to connect to Meta. Check your credentials above, or check the module logs for the full error details."}
            </div>
        {else}
            <div style="padding: 10px 0;">
                <i class="fas fa-check-square" style="color: #2A9E2A; font-size: 32px;"></i>
                <div class="dct-page-title" style="margin-top: 8px;">
                    {lkn_hn_lang text="Connected to [1]" params=[{$page_params.connected_to_name}]}
                </div>
            </div>
            <a
                href="?module=dct_whatsapp_notifications&page=notifications/InvoiceReminder/templates/new"
                target="_blank"
                class="dct-button dct-button-secondary"
            >
                {lkn_hn_lang text="Notification Settings "} <i class="fas fa-external-link-alt"></i>
            </a>
        {/if}
    </div>
</div>
