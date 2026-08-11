<div
    class="alert alert-success alert-dismissible fade in"
    role="alert"
>
    <button
        id="dct_whatsapp_notifications-dismiss-icon"
        type="button"
        class="close"
        data-dismiss="alert"
        aria-label="Close"
    >
        <span aria-hidden="true">×</span>
    </button>
    <h4 style="margin-bottom: 30px;">{lkn_hn_lang text="A new version is available for WhatsApp and Chatwoot"}</h4>
    <p style="max-width: 750px;">{lkn_hn_lang text="Download now for new features and bug fixes."}</p>
    <p style="max-width: 750px;">
        {lkn_hn_lang text="<br>Please, if you face any problems with the current installed notifications after you update the module, remove the current notifications files and download and install the ones compatible with the new version <a
href='https://dctlab.directcybertech.com/'
>by clicking here</a>.<br><br>If you have developed your own notifications, <a href='https://dctlab.directcybertech.com/'>click here to familiarize with the new requirements.</a>"}
    </p>
    <div style="margin-top: 30px; display: flex; align-items: end; justify-content: space-between;">
        <p style="max-width: 750px;">
            <a
                class="btn btn-success"
                target="_blank"
                href="https://dctlab.directcybertech.com/"
                role="button"
            ><i class="fas fa-cloud-download"></i> {lkn_hn_lang text="Download new version"}
                v{$page_params.new_version}</a>
        </p>

        <p>
            <a
                class="btn btn-link btn-sm"
                target="_blank"
                href="https://dctlab.directcybertech.com/"
                role="button"
            >

                {lkn_hn_lang text="View changelog"}
            </a>
        </p>
    </div>
</div>

<script type="text/javascript">
    const dismissIcon = document.getElementById('dct_whatsapp_notifications-dismiss-icon');

    dismissIcon.addEventListener('click', () => {
        const url = new URL(window.location.href);

        url.searchParams.set('new-version-dismiss-on-admin-home', '1');

        window.location.href = url.toString();
    });
</script>
