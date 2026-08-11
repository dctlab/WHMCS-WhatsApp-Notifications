{$page_params.messenger_script}

<script type="text/javascript">
    {* Found during the Phase 10 security scan: values below were previously
       interpolated into single-quoted JS string literals with no
       JS-context escaping - a client name or email containing a single
       quote could break out of the string and inject arbitrary
       JavaScript. Fixed with Smarty's own |escape:'javascript' modifier,
       which escapes exactly for this context (quotes, backslashes,
       newlines) - same fix class as other escaping issues found across
       this redesign, not a change to what data is sent. *}
    window.addEventListener("chatwoot:ready", function() {
        const client_identifier_hash = '{$page_params.client_identifier_key|escape:"javascript"}';

        window.$chatwoot.setUser(client_identifier_hash, {
            identifier_hash: '{$page_params.identifier_hash|escape:"javascript"}',
            name: '{$page_params.client_details['name']|escape:"javascript"}',
            email: '{$page_params.client_details['email']|escape:"javascript"}',
            phone_number: '{$page_params.client_details['phone_number']|escape:"javascript"}',
            country_code: '{$page_params.client_details['country_code']|escape:"javascript"}',
            {if !empty($page_params.client_details['city'])}
                city: '{$page_params.client_details['city']|escape:"javascript"}',
            {/if}
            company_name: '{$page_params.client_details['company_name']|escape:"javascript"}',
        });

        window.$chatwoot.setCustomAttributes({$page_params.custom_attrs_script});
    })
</script>
