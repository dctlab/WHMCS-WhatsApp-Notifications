<div class="row">
    <div class="col-sm-12 text-center">
        {* The raw API error response is deliberately not shown here - see
           the same fix already applied to Meta's connection_info.tpl in
           Phase 8. This file was missed at the time since Evolution API's
           setup views were deferred as a scope decision - found during the
           Phase 10 provider security scan and fixed as the same class of
           issue, per this phase's explicit authorization to fix security
           vulnerabilities directly. *}
        <div
            id="lkn-hn-alert"
            class="alert alert-danger alert-dismissible"
            role="alert"
        >
            <i class="fas fa-exclamation-square"></i>
            {lkn_hn_lang text="Unable to connect to Evolution API. Check your settings above, or check the module logs for the full error details."}
        </div>
    </div>
</div>
