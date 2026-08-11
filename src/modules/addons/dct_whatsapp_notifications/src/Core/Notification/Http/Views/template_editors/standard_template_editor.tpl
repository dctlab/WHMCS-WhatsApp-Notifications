<div class="dct-form-group">
    <label class="dct-form-label" style="font-size: 14px;">{lkn_hn_lang text='Available Parameters'}</label>
    <div class="dct-form-help" style="margin-bottom: 8px;">
        {lkn_hn_lang text='Click a parameter to insert it into the message below.'}
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
        {foreach from=$page_params.editing_notification->parameters->params item=$param}
            <span
                class="clickable-param dct-status-badge dct-status-badge-info"
                style="cursor: pointer; padding: 5px 10px;"
            >
                <i class="far fa-hand-pointer"></i>
                {literal}{{{/literal}{lkn_hn_lang text={$param->code}}{literal}}}{/literal}
            </span>
        {/foreach}
    </div>
</div>

<div class="dct-form-group">
    <label for="template" class="dct-form-label" style="font-size: 14px;">{lkn_hn_lang text='Template'}</label>
    <textarea
        name="template"
        id="template"
        class="dct-textarea notif-body-input"
        required
        style="height: 260px; resize: vertical;"
        placeholder="{lkn_hn_lang text='Type here...'}"
    >{if $page_params.editing_template}{$page_params.editing_template->template}{/if}</textarea>
    <div class="dct-form-help">
        {lkn_hn_lang text="No character limit is enforced by this provider."}
    </div>
</div>

<script type="text/javascript">
    const notifBodyInput = document.querySelector('.notif-body-input')


    document.querySelectorAll(".clickable-param").forEach(element => {
        element.addEventListener("click", function(event) {
            const textToInsert = event.target.textContent.trim() + ' ';

            const startPos = notifBodyInput.selectionStart;
            const endPos = notifBodyInput.selectionEnd;

            notifBodyInput.value = notifBodyInput.value.substring(0, startPos) +
                textToInsert +
                notifBodyInput.value.substring(endPos);

            notifBodyInput.focus();
            notifBodyInput.selectionStart = notifBodyInput.selectionEnd = startPos + textToInsert
                .length;
        });
    });
</script>
