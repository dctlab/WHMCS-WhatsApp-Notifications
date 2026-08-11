<div class="panel panel-default">
    <div class="panel-heading">
        <strong>{lkn_hn_lang text="Webhook"}</strong>
    </div>
    <div class="panel-body">
        <p>
            {lkn_hn_lang text="botms.in sends events here (incoming messages, connection status, disconnects, battery, etc). This module registers it automatically whenever you save these settings with your Instance ID and Access Token filled in."}
        </p>

        <label>{lkn_hn_lang text="Webhook URL"}</label>
        <input type="text" class="form-control" readonly value="{$page_params.webhook_url}" style="margin-bottom: 10px;">

        {if $page_params.result}
            {if $page_params.result.succeeded}
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    {lkn_hn_lang text="Webhook registered successfully with botms.in."}
                </div>
            {else}
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    {lkn_hn_lang text="Failed to register the webhook"}{if $page_params.result.message}: {$page_params.result.message|escape}{/if}
                </div>
            {/if}
        {elseif $page_params.registered_at}
            <p class="text-muted">
                <i class="fas fa-check-circle text-success"></i>
                {lkn_hn_lang text="Last registered"}: {$page_params.registered_at}
            </p>
        {elseif !$page_params.has_credentials}
            <p class="text-muted">
                {lkn_hn_lang text="Fill in the Instance ID and Access Token above and save to register the webhook."}
            </p>
        {/if}

        {if $page_params.has_credentials}
            <button
                type="submit"
                name="register_webhook"
                value="1"
                class="btn btn-default btn-sm"
            >
                <i class="fas fa-sync"></i> {lkn_hn_lang text="Re-register Webhook Now"}
            </button>
        {/if}
    </div>
</div>
