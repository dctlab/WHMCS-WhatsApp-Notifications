{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {if $page_params.mode === 'edit'}
        {lkn_hn_lang text="Bulk message - #[1] [2]" params=[$page_params.bulk->id, $page_params.bulk->title]}
    {else}
        {lkn_hn_lang text="New bulk message" params=[$page_params.platform_title]}
    {/if}
{/block}

{block "page_content"}
    <style>
        #lkn-hn-new-bulk-form {
            max-width: 720px;
        }

        #lkn-hn-new-bulk-form label {
            text-align: left !important;
        }

        #lkn-hn-new-bulk-form .panel-body {
            padding: 30px;
        }

        {if $page_params.mode === 'edit'}
            #lkn-hn-msg-tpl-select-cont select {
                pointer-events: none;
                background-color: #cdcdcd38;
            }
        {/if}
    </style>

    <div class="dct-page-header">
        <div class="dct-page-header-text">
            <div class="dct-page-header-description">
                {lkn_hn_lang text="Configure recipients and the message for this bulk campaign."}
            </div>
        </div>
    </div>

    <form
        id="lkn-hn-new-bulk-form"
        class="form-horizontal"
        target="_self"
        method="POST"
    >
        <div
            class="panel-group"
            id="accordion"
            role="tablist"
            aria-multiselectable="true"
        >
            {* STEP 1 - DETAILS *}

            <div class="panel panel-default">
                <div
                    class="panel-heading"
                    role="tab"
                    id="headingOne"
                >
                    <h4 class="panel-title">
                        <a
                            role="button"
                            data-toggle="collapse"
                            data-parent="#accordion"
                            href="#collapseOne"
                            aria-expanded="true"
                            aria-controls="collapseOne"
                        >
                            {lkn_hn_lang text="Details"}
                        </a>
                    </h4>
                </div>
                <div
                    id="collapseOne"
                    class="panel-collapse collapse in"
                    role="tabpanel"
                    aria-labelledby="headingOne"
                >
                    <div class="panel-body">
                        {if $page_params.mode === 'edit' && "now"|date_format:"%Y-%m-%d %H:%M:%S" >= $page_params.state->startAt->format('Y-m-d H:i:s')}
                            <div class="dct-form-group" style="display: flex; align-items: center; gap: 16px;">
                                <label for="title" class="dct-form-label" style="flex: 0 0 140px; margin: 0;">
                                    {lkn_hn_lang text="Progress"}
                                </label>
                                <div style="display: flex; gap: 10px; align-items: center; flex: 1;">
                                    <div style="flex-grow: 1; background: var(--dct-border-light); border-radius: 4px; height: 18px; overflow: hidden; position: relative;">
                                        <div style="height: 100%; background: var(--dct-primary); width: {$page_params.bulk->progress}%;"></div>
                                        <span style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 11px;">
                                            {$page_params.bulk->progress}%
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="dct-button dct-button-ghost"
                                        style="padding: 4px 8px;"
                                        onclick="document.querySelector('#lkn-hn-new-bulk-form').submit()"
                                        data-toggle="tooltip"
                                        data-placement="top"
                                        title="{lkn_hn_lang text="Refresh progress"}"
                                    >
                                        <i class="far fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="dct-form-group" style="display: flex; align-items: flex-start; gap: 16px;">
                                <label for="status" class="dct-form-label" style="flex: 0 0 140px; margin: 0; padding-top: 6px;">
                                    {lkn_hn_lang text="Status"}
                                </label>
                                <div style="flex: 1;">
                                    <select
                                        class="dct-select"
                                        id="bulk-status"
                                        name="bulk-status"
                                        required
                                        {if $page_params.bulk->status->value !== 'in_progress'}
                                            disabled
                                        {/if}
                                        onchange="confirmStatusChange()"
                                        disabled
                                    >
                                        {foreach from=$page_params.field_options['bulk_message_status'] item=$status}
                                            {if $status['value'] !== 'completed' || $page_params.bulk->status->value === 'completed'}
                                                <option
                                                    {if $status['value'] === $page_params.bulk->status->value}
                                                        selected
                                                    {/if}
                                                    value="{$status['value']}"
                                                >
                                                    {$status['label']}
                                                </option>
                                            {/if}
                                        {/foreach}
                                    </select>

                                    <div style="margin-top: 6px;">
                                        {if $page_params.state->status->value === 'in_progress'}
                                            <button
                                                type="button"
                                                class="dct-button dct-button-ghost dct-text-small"
                                                onclick="document.getElementById('bulk-status').disabled = false"
                                            >
                                                <i class="fas fa-exchange-alt"></i>
                                                {lkn_hn_lang text="Change bulk status"}
                                            </button>
                                        {/if}
                                    </div>

                                    <script type="text/javascript">
                                        function confirmStatusChange() {
                                            const result = confirm("{lkn_hn_lang text='Are you sure? The changes will affect in progress notifications too.'}");

                                            if (result) {
                                                document.getElementById('lkn-hn-new-bulk-form').submit()
                                            }
                                        }
                                    </script>
                                </div>
                            </div>
                        {/if}

                        {* DATE TO SEND *}

                        <div class="dct-form-group">
                            <label for="date-to-send" class="dct-form-label">
                                {lkn_hn_lang text="Start date"}
                            </label>
                            <input
                                type="datetime-local"
                                class="dct-input"
                                id="date-to-send"
                                name="date-to-send"
                                required
                                {if $page_params.mode === 'edit'}disabled{/if}
                                {if $page_params.state->startAt}
                                    value="{$page_params.state->startAt->format('Y-m-d\TH:i')}"
                                {/if}
                            >

                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const input = document.getElementById('date-to-send');
                                    const now = new Date();

                                    const pad = num => String(num).padStart(2, '0');
                                    const localDatetime = [
                                        now.getFullYear(),
                                        pad(now.getMonth() + 1),
                                        pad(now.getDate())
                                    ].join('-') + 'T' + [
                                        pad(now.getHours()),
                                        pad(now.getMinutes() + 5)
                                    ].join(':');

                                    input.min = localDatetime;
                                });
                            </script>
                        </div>

                        <hr>

                        {* TITLE *}

                        <div class="dct-form-group">
                            <label for="title" class="dct-form-label">
                                {lkn_hn_lang text="Title"}
                            </label>
                            <input
                                type="text"
                                class="dct-input"
                                id="title"
                                name="title"
                                required
                                value="{$page_params.state->title}"
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                        </div>

                        {* Description *}

                        <div class="dct-form-group">
                            <label for="description" class="dct-form-label">
                                {lkn_hn_lang text="Description"}
                            </label>
                            <input
                                type="text"
                                class="dct-input"
                                id="description"
                                name="description"
                                required
                                value="{$page_params.state->descrip}"
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                        </div>

                        {* MAX SIMULTANEUS SENDING *}

                        <div class="dct-form-group">
                            <label for="max-concurrency" class="dct-form-label">
                                {lkn_hn_lang text="Max concurrency"}
                            </label>
                            <div class="dct-form-help" style="margin-bottom: 6px;">
                                {lkn_hn_lang text="Number of shots per cron cycle, normally 5 minutes. Enter limit 2 to 50."}
                            </div>
                            <input
                                type="number"
                                max="50"
                                min="2"
                                class="dct-input"
                                id="max-concurrency"
                                name="max-concurrency"
                                required
                                value="{$page_params.state->maxConcurrency}"
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                        </div>
                    </div>
                </div>
            </div>

            {* FILTERS *}

            <div class="panel panel-default">
                <div
                    class="panel-heading"
                    role="tab"
                    id="headingTwo"
                >
                    <h4 class="panel-title">
                        <a
                            class="collapsed"
                            role="button"
                            data-toggle="collapse"
                            data-parent="#accordion"
                            href="#collapseTwo"
                            aria-expanded="false"
                            aria-controls="collapseTwo"
                        >
                            {lkn_hn_lang text="Filters"}
                        </a>
                    </h4>
                </div>
                <div
                    id="collapseTwo"
                    class="panel-collapse collapse {if $page_params.mode !== 'edit'}in{/if}"
                    role="tabpanel"
                    aria-labelledby="headingTwo"
                >
                    <div class="panel-body">
                        {* CLIENT STATUS *}

                        <div class="dct-form-group">
                            <label for="client-status" class="dct-form-label">
                                {lkn_hn_lang text="Client status"}
                            </label>
                            <select
                                class="dct-select"
                                id="client-status"
                                name="client-status[]"
                                multiple
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                                {foreach from=$page_params.field_options.whmcs_client_statuses item=$status}
                                    <option
                                        {if $page_params.state->filters['client_status'] && in_array($status['value'], $page_params.state->filters['client_status']) || empty($page_params.state->filters['client_status'])}
                                            selected
                                        {/if}
                                        value="{$status['value']}"
                                    >
                                        {$status['label']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        {* CLIENT LOCALE *}

                        <div class="dct-form-group">
                            <label for="client-locale" class="dct-form-label">
                                {lkn_hn_lang text="Client language"}
                            </label>
                            <select
                                class="dct-select"
                                id="client-locale"
                                name="client-locale[]"
                                multiple
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                                <option
                                    value="default"
                                    {if (isset($page_params.state->filters['client_locale']) && in_array('default', $page_params.state->filters['client_locale'])) || empty($page_params.state->filters['client_locale'])}
                                        selected
                                    {/if}
                                >
                                    {lkn_hn_lang text="Default"}
                                </option>

                                {foreach from=$page_params.field_options.whmcs_client_lang item=$locale}
                                    <option
                                        {if (isset($page_params.state->filters['client_locale']) && in_array($locale['locale_expanded'], $page_params.state->filters['client_locale'])) || empty($page_params.state->filters['client_locale'])}
                                            selected
                                        {/if}
                                        value="{$locale['locale_expanded']}"
                                    >
                                        {$locale['label']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        {* CLIENT COUNTRY *}
                        <div class="dct-form-group">
                            <label for="client-country" class="dct-form-label">
                                {lkn_hn_lang text="Client country"}
                            </label>
                            <select
                                class="dct-select"
                                id="client-country"
                                name="client-country[]"
                                multiple
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                                {foreach from=$page_params.field_options['whmcs_client_countries'] item=$country}
                                    <option
                                        {if $page_params.state->filters['client_country'] && in_array($country['value'], $page_params.state->filters['client_country']) || empty($page_params.state->filters['client_country'])}
                                            selected
                                        {/if}
                                        value="{$country['value']}"
                                    >
                                        {$country['label']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        <hr>

                        {* SERVICES *}

                        <div class="dct-form-group">
                            <label for="services" class="dct-form-label">
                                {lkn_hn_lang text="Services"}
                            </label>
                            <select
                                class="dct-select"
                                id="services"
                                name="services[]"
                                multiple
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                                {foreach from=$page_params.field_options['whmcs_products'] item=$product}
                                    <option
                                        {if $page_params.state->filters['services'] && in_array($product['value'], $page_params.state->filters['services'])}
                                            selected
                                        {/if}
                                        value="{$product['value']}"
                                    >
                                        {$product['label']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        {* SERVICE STATUS *}

                        <div class="dct-form-group">
                            <label for="service-status" class="dct-form-label">
                                {lkn_hn_lang text="Service status"}
                            </label>
                            <select
                                class="dct-select"
                                id="service-status"
                                name="service-status[]"
                                multiple
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                                {foreach from=$page_params.field_options['whmcs_client_product_status'] item=$productStatus}
                                    <option
                                        {if $page_params.state->filters['service_status'] && in_array($productStatus['value'], $page_params.state->filters['service_status'])}
                                            selected
                                        {/if}
                                        value="{$productStatus['value']}"
                                    >
                                        {$productStatus['label']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        {* CLIENTS *}
                        {if $page_params.mode !== 'edit'}
                            <hr>
                            <div class="dct-form-group">
                                <label for="client-ids" class="dct-form-label">
                                    {lkn_hn_lang text="Selected clients"}
                                    {if isset($page_params.field_options['client_options'])}
                                        <span id="selected-clients-count">
                                            {count($page_params.field_options['client_options'])}
                                        </span>
                                    {/if}
                                </label>
                                <div class="dct-form-help" style="margin-bottom: 8px;">
                                    {lkn_hn_lang text="If none is specified, the notification will be sent to all matched clients."}
                                </div>

                                {if isset($page_params.field_options['client_options'])}
                                    {if empty($page_params.field_options['client_options'])}
                                        <div class="dct-alert dct-alert-danger">{lkn_hn_lang text="No client matched the filters."}</div>
                                    {else}
                                        <table
                                            id="table_id"
                                            class="display compact stripe"
                                        >
                                            <thead>
                                                <tr>
                                                    <th>{lkn_hn_lang text="Client"}</th>
                                                    <th>{lkn_hn_lang text="Send?"}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {foreach from=$page_params.field_options['client_options'] item=$client}
                                                    <tr>
                                                        <td>#{$client['value']} {$client['label']}</td>
                                                        <td>
                                                            <input
                                                                type="checkbox"
                                                                {if !in_array($client['value'], $page_params.state->filters['not_sending_clients'])}
                                                                    checked
                                                                {/if}
                                                                data-will-send-client-id="{$client['value']}"
                                                                onchange="handleNotSendToClient({$client['value']}, '{$client['label']}')"
                                                            >
                                                        </td>
                                                    </tr>
                                                {/foreach}
                                            </tbody>
                                        </table>

                                        <div id="not-sending-cont">
                                            {foreach from=$page_params.state->filters['not_sending_clients'] item=$clientId}
                                                <input
                                                    type="hidden"
                                                    id="not-sending-client-{$clientId}"
                                                    name="not-sending-clients[]"
                                                    value="{$clientId}"
                                                />
                                            {/foreach}
                                            <div id="not-sending-view" style="display: flex; gap: 5px;">
                                                {foreach from=$page_params.state->filters['not_sending_clients'] item=$clientId}
                                                    <span
                                                        class="dct-status-badge dct-status-badge-neutral"
                                                        id="data-not-sending-client-id-{$clientId}"
                                                        data-container="data-not-sending-client-id-{$clientId}"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="{lkn_hn_lang text="Send?"}"
                                                        style="text-transform: none; cursor: pointer;"
                                                        onclick="handleNotSendToClient({$clientId})"
                                                    >
                                                        # {$clientId}
                                                        <i class="far fa-share" style="margin-left: 4px;"></i>
                                                    </span>
                                                {/foreach}
                                            </div>
                                        </div>

                                        <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
                                        <script type="text/javascript">
                                            const notSendingCont = document.getElementById('not-sending-cont')
                                            const notSendingView = document.getElementById('not-sending-view')
                                            const selectedClientCount = document.getElementById('selected-clients-count')

                                            let table = new DataTable('#table_id', {})

                                            function handleNotSendToClient(clientId, clientName = '') {
                                                const isSelected = document.getElementById('not-sending-client-' + clientId)

                                                if (!isSelected) {
                                                    selectedClientCount.innerText = parseInt(selectedClientCount.innerText) - 1
                                                    const clientNotSendingLabel = document.createElement('span')

                                                    clientNotSendingLabel.className = 'dct-status-badge dct-status-badge-neutral'

                                                    clientNotSendingLabel.id = 'data-not-sending-client-id-' + clientId
                                                    clientNotSendingLabel.setAttribute('data-toggle', 'tooltip')
                                                    clientNotSendingLabel.setAttribute('data-placement', 'top')
                                                    clientNotSendingLabel.setAttribute('title', '{lkn_hn_lang text="Send?"}')

                                                    clientNotSendingLabel.innerHTML = '#' + clientId + ' ' + clientName +
                                                        '<i class="far fa-share" style="margin-left: 4px;"></i>'
                                                    clientNotSendingLabel.style = 'text-transform: none; cursor: pointer;'

                                                    notSendingView.appendChild(clientNotSendingLabel)

                                                    clientNotSendingLabel.addEventListener('click', () => handleNotSendToClient(
                                                        clientId, clientName))

                                                    $(clientNotSendingLabel).tooltip({ container: '#' + clientNotSendingLabel.id })

                                                    const newClientNotSendingInput = document.createElement('input')

                                                    newClientNotSendingInput.id = 'not-sending-client-' + clientId
                                                    newClientNotSendingInput.name = 'not-sending-clients[]'
                                                    newClientNotSendingInput.value = clientId
                                                    newClientNotSendingInput.type = 'hidden'

                                                    notSendingCont.appendChild(newClientNotSendingInput)
                                                } else {
                                                    selectedClientCount.innerText = parseInt(selectedClientCount.innerText) + 1
                                                    if (!isSelected) {
                                                        return
                                                    }

                                                    const clientNotSendingLabel = document.getElementById(
                                                        'data-not-sending-client-id-' + clientId)
                                                    const clientNotSendingInput = isSelected
                                                    const willSendCheckbox = document.querySelector(
                                                        'input[data-will-send-client-id="' + clientId + '"]')

                                                    willSendCheckbox.checked = true
                                                    clientNotSendingInput.remove()
                                                    clientNotSendingLabel.remove()
                                                }
                                            }
                                        </script>
                                    {/if}
                                {/if}

                                {if $page_params.mode !== 'edit'}
                                    <input
                                        name="get-matched-clients"
                                        value="1"
                                        type="hidden"
                                    />
                                    <button
                                        id="btn-view-matched-clients"
                                        type="button"
                                        class="dct-button dct-button-success"
                                    >
                                        {lkn_hn_lang text='View matched clients'}
                                    </button>

                                    <script type="text/javascript">
                                        document.getElementById('btn-view-matched-clients').addEventListener('click', () => {
                                            document.getElementById('lkn-hn-new-bulk-form').submit()
                                        })
                                    </script>
                                {/if}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>

            {* STEP - MESSAGE *}

            <div class="panel panel-default">
                <div
                    class="panel-heading"
                    role="tab"
                    id="headingThree"
                >
                    <h4 class="panel-title">
                        <a
                            class="collapsed"
                            role="button"
                            data-toggle="collapse"
                            data-parent="#accordion"
                            href="#collapseDelta"
                            aria-expanded="false"
                            aria-controls="collapseDelta"
                        >
                            {lkn_hn_lang text="Message"}
                        </a>
                    </h4>
                </div>
                <div
                    id="collapseDelta"
                    class="panel-collapse collapse {if $page_params.mode !== 'edit'}in{/if}"
                    role="tabpanel"
                    aria-labelledby="headingThree"
                >
                    <div class="panel-body">
                        {* PLATFORM *}

                        <div class="dct-form-group">
                            <label for="platform" class="dct-form-label" style="font-size: 15px;">
                                {lkn_hn_lang text='Platform'}
                            </label>
                            <select
                                id="platform"
                                name="platform"
                                class="dct-select"
                                onchange="(document.getElementById('notification-form') ?? document.getElementById('lkn-hn-new-bulk-form')).submit()"
                                {if $page_params.mode === 'edit'}disabled{/if}
                            >
                                <option value="">{lkn_hn_lang text="Select a platform"}</option>

                                {foreach from=$page_params.field_options['platform_options'] item=$platform}
                                    <option
                                        {if $platform === $page_params.state->platform}
                                            selected
                                        {/if}
                                        value="{$platform->value}"
                                    >
                                        {$platform->label()}
                                    </option>
                                {/foreach}
                            </select>

                            {if $page_params.editing_template}
                                <div style="margin-top: 6px;">
                                    <button
                                        id="btn-enable-platform-change"
                                        type="button"
                                        class="dct-button dct-button-ghost dct-text-small"
                                    >
                                        <i class="fas fa-exchange-alt"></i>
                                        {lkn_hn_lang text="Change template platform"}
                                    </button>

                                    <script type="text/javascript">
                                        const btnEnablePlatformChange = document.getElementById('btn-enable-platform-change')

                                        btnEnablePlatformChange.addEventListener('click', () => {
                                            btnEnablePlatformChange.style.display = 'none'

                                            const platformSelect = document.getElementById('platform')

                                            platformSelect.readonly = false
                                            platformSelect.showPicker();
                                        })
                                    </script>
                                </div>
                            {/if}
                        </div>

                        {$page_params.template_editor_view}
                    </div>
                </div>
            </div>

            {if $page_params.mode === 'edit'}
                {include file="../components/bulk_messages.tpl"}
            {/if}

            {if $page_params.mode !== 'edit'}
                <div style="margin-top: 30px;">
                    <button
                        type="submit"
                        class="dct-button dct-button-primary"
                        onclick="return confirmSubmit('{lkn_hn_lang text='Do you really want to create the message? After confirmation, you will no longer be able to edit the message.'}')"
                        name="create-bulk"
                    >
                        {lkn_hn_lang text="Create Bulk Message"}
                    </button>
                </div>
            {/if}
        </div>
    </form>
{/block}
