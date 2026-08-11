{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="WhatsApp Conversations"}
    <span id="lkn-hn-chat-live-indicator" style="font-size: 14px; font-weight: normal; color: #5cb85c;">
        <span id="lkn-hn-chat-live-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #5cb85c;"></span>
        {lkn_hn_lang text="live"}
    </span>
{/block}

{block "page_content"}
    <style>
        /* ===== Conversation layout (DCT-scoped, additive to existing IDs/classes
           the JS below still references directly - none renamed) ===== */
        .dct-conversation-layout {
            display: flex;
            gap: var(--dct-spacing-md);
            align-items: flex-start;
        }

        .dct-conversation-list-col {
            flex: 0 0 320px;
            max-width: 320px;
        }

        .dct-message-area-col {
            flex: 1 1 auto;
            min-width: 0;
        }

        .lkn-hn-chat-list {
            max-height: 640px;
            overflow-y: auto;
            padding: 0;
        }

        .lkn-hn-chat-list-item {
            display: block;
            padding: 10px 15px;
            border-bottom: 1px solid var(--dct-border-light);
            text-decoration: none;
            color: inherit;
        }

        .lkn-hn-chat-list-item:hover {
            background: var(--dct-surface-muted);
            text-decoration: none;
            color: inherit;
        }

        .lkn-hn-chat-list-item.active {
            background: var(--dct-primary-light);
        }

        .lkn-hn-chat-list-item .name {
            font-weight: 600;
            display: block;
        }

        .lkn-hn-chat-list-item .preview {
            color: var(--dct-text-muted);
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        .lkn-hn-chat-list-item .time {
            color: var(--dct-text-muted);
            font-size: 11px;
        }

        #lkn-hn-chat-thread {
            background: #ede0d3;
            min-height: 400px;
            max-height: 560px;
            overflow-y: auto;
            padding: 15px;
            border-radius: var(--dct-radius-md);
        }

        .lkn-hn-chat-bubble-row {
            display: flex;
            margin-bottom: 12px;
        }

        .lkn-hn-chat-bubble-row.outbound {
            justify-content: flex-end;
        }

        .lkn-hn-chat-bubble {
            max-width: 60%;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: var(--dct-shadow-sm);
        }

        .lkn-hn-chat-bubble.inbound {
            background: #ffffff;
        }

        .lkn-hn-chat-bubble.outbound {
            background: #d9f2c4;
        }

        .lkn-hn-chat-bubble .body {
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .lkn-hn-chat-bubble .meta {
            color: var(--dct-text-muted);
            font-size: 11px;
            margin-top: 4px;
            text-align: right;
        }

        /* Mobile: list is shown by default; selecting a conversation
           reveals the message area and hides the list, with a Back
           control to return - pure CSS/display toggle over data that is
           already on the page, not a new capability. */
        #dct-chat-back-link {
            display: none;
        }

        @media (max-width: 767px) {
            .dct-conversation-layout {
                flex-direction: column;
            }

            .dct-conversation-list-col,
            .dct-message-area-col {
                flex: 1 1 100%;
                max-width: 100%;
            }

            body.dct-chat-thread-open .dct-conversation-list-col {
                display: none;
            }

            body:not(.dct-chat-thread-open) .dct-message-area-col {
                display: none;
            }

            body.dct-chat-thread-open #dct-chat-back-link {
                display: inline-flex;
            }
        }
    </style>

    <div class="dct-conversation-layout">
        <div class="dct-conversation-list-col">
            <div class="dct-card">
                <div class="dct-card-header">
                    <span class="dct-card-title">{lkn_hn_lang text="Conversations"}</span>
                </div>
                <div class="lkn-hn-chat-list" id="lkn-hn-chat-conversations-list">
                    {foreach from=$page_params.conversations item=$conversation}
                        <a
                            href="{$lkn_hn_base_endpoint}&page=notification-chat&phone={$conversation.phone_number}"
                            class="lkn-hn-chat-list-item {if $conversation.phone_number == $page_params.selected_phone}active{/if}"
                        >
                            <span class="name">
                                {if $conversation.client_name}
                                    {$conversation.client_name}
                                {else}
                                    +{$conversation.phone_number}
                                {/if}
                            </span>
                            <span class="preview">
                                {if $conversation.last_message_direction == 'outbound'}&#8594; {/if}
                                {$conversation.last_message_preview|default:''|truncate:40}
                            </span>
                            <span class="time">
                                {if $conversation.last_message_at}{$conversation.last_message_at->format('Y-m-d H:i')}{/if}
                            </span>
                        </a>
                    {foreachelse}
                        <div class="dct-empty-state">
                            <div class="dct-empty-state-icon"><i class="far fa-comments"></i></div>
                            <div class="dct-empty-state-title">{lkn_hn_lang text="No conversations yet"}</div>
                            <div class="dct-empty-state-description">
                                {lkn_hn_lang text="Messages exchanged through supported DCTLAB WhatsApp integrations will appear here."}
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
            <a href="{$lkn_hn_base_endpoint}&page=notification-conversations" class="dct-button dct-button-ghost dct-text-small">
                {lkn_hn_lang text="View as table / analytics"}
            </a>
        </div>

        <div class="dct-message-area-col">
            {if $page_params.selected_phone}
                <a href="#" id="dct-chat-back-link" class="dct-button dct-button-ghost dct-text-small" style="margin-bottom: 8px;">
                    <i class="far fa-arrow-left"></i> {lkn_hn_lang text="Conversations"}
                </a>

                <div class="dct-card">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Conversation with"} {$page_params.selected_phone}</span>
                    </div>
                    <div class="dct-card-body">
                        <div id="lkn-hn-chat-thread" data-phone="{$page_params.selected_phone}">
                            {foreach from=$page_params.thread item=$message}
                                <div class="lkn-hn-chat-bubble-row {$message.direction}">
                                    <div class="lkn-hn-chat-bubble {$message.direction}">
                                        <div class="body">{$message.body|default:'—'|escape}</div>
                                        <div class="meta">
                                            {$message.sent_at->format('Y-m-d H:i:s')}
                                            {if $message.direction == 'outbound' && $message.status}
                                                &middot; {$message.status}
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            {foreachelse}
                                <div class="dct-text-muted" style="text-align: center; padding: 20px;">
                                    {lkn_hn_lang text="No messages in this conversation."}
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>

                <div class="dct-card">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Send a message"}</span>
                    </div>
                    <div class="dct-card-body">
                        <div id="lkn-hn-chat-send-error" class="dct-alert dct-alert-danger" style="display: none;"></div>

                        <div class="dct-form-group">
                            <label class="dct-form-label">{lkn_hn_lang text="Phone number (E.164 format, no + sign, e.g. 15551234567)"}</label>
                            <input
                                type="text"
                                class="dct-input"
                                id="lkn-hn-chat-phone"
                                value="{$page_params.selected_phone}"
                                readonly
                            >
                        </div>

                        <div class="dct-form-group">
                            <label class="dct-form-label">{lkn_hn_lang text="Message"}</label>
                            <textarea
                                class="dct-textarea"
                                id="lkn-hn-chat-message"
                                rows="3"
                                style="height: auto;"
                            ></textarea>
                        </div>

                        <button type="button" class="dct-button dct-button-primary" id="lkn-hn-chat-send-btn">
                            {lkn_hn_lang text="Send"}
                        </button>

                        <div class="dct-form-help" style="margin-top: 10px;">
                            {lkn_hn_lang text="Free-form text only works if this contact messaged you within the last 24 hours (Meta's customer service window). Outside that window, use an approved notification template instead."}
                        </div>
                    </div>
                </div>
            {else}
                <div class="dct-card">
                    <div class="dct-card-body dct-text-muted">
                        {lkn_hn_lang text="Select a conversation on the left to view its messages."}
                    </div>
                </div>
            {/if}
        </div>
    </div>

    <script>
        (function () {
            var thread = document.getElementById('lkn-hn-chat-thread');
            if (!thread) {
                return;
            }

            // Mobile: only start in "thread open" state if this phone was
            // explicitly requested via the URL - not when the controller
            // merely auto-selected the first conversation as a fallback
            // default on a fresh visit with no ?phone= param. That keeps
            // the conversation list as the actual default view on mobile.
            if (window.location.search.indexOf('phone=') !== -1) {
                document.body.classList.add('dct-chat-thread-open');
            }

            var backLink = document.getElementById('dct-chat-back-link');
            if (backLink) {
                backLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.body.classList.remove('dct-chat-thread-open');
                });
            }

            var phone = thread.getAttribute('data-phone');
            var baseEndpoint = '{$lkn_hn_base_endpoint}';
            var apiBaseUrl = '{$lkn_hn_api_base_url}';
            var lastTimestamp = null;

            var rows = thread.querySelectorAll('.lkn-hn-chat-bubble-row .meta');
            if (rows.length > 0) {
                lastTimestamp = rows[rows.length - 1].textContent.trim().split(' \u00b7')[0].trim();
            }

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str || '';
                return div.innerHTML;
            }

            function appendMessage(message) {
                var row = document.createElement('div');
                row.className = 'lkn-hn-chat-bubble-row ' + message.direction;

                var meta = message.sent_at + (message.direction === 'outbound' && message.status ? ' \u00b7 ' + message.status : '');

                row.innerHTML = '<div class="lkn-hn-chat-bubble ' + message.direction + '">' +
                    '<div class="body">' + escapeHtml(message.body || '\u2014') + '</div>' +
                    '<div class="meta">' + escapeHtml(meta) + '</div>' +
                    '</div>';

                thread.appendChild(row);
                lastTimestamp = message.sent_at;
            }

            function setLive(isLive) {
                var dot = document.getElementById('lkn-hn-chat-live-dot');
                var indicator = document.getElementById('lkn-hn-chat-live-indicator');
                if (!dot || !indicator) {
                    return;
                }
                dot.style.background = isLive ? '#5cb85c' : '#d9534f';
                indicator.style.color = isLive ? '#5cb85c' : '#d9534f';
            }

            function updateConversationsList(conversations) {
                var list = document.getElementById('lkn-hn-chat-conversations-list');
                if (!list || !conversations) {
                    return;
                }

                var html = '';
                conversations.forEach(function (c) {
                    var isActive = c.phone_number === phone;
                    var name = c.client_name ? c.client_name : ('+' + c.phone_number);
                    var arrow = c.last_message_direction === 'outbound' ? '\u2192 ' : '';
                    var preview = (c.last_message_preview || '');
                    if (preview.length > 40) {
                        preview = preview.substring(0, 40) + '...';
                    }

                    html += '<a href="' + baseEndpoint + '&page=notification-chat&phone=' + encodeURIComponent(c.phone_number) + '" ' +
                        'class="lkn-hn-chat-list-item' + (isActive ? ' active' : '') + '">' +
                        '<span class="name">' + escapeHtml(name) + '</span>' +
                        '<span class="preview">' + escapeHtml(arrow + preview) + '</span>' +
                        '<span class="time">' + escapeHtml(c.last_message_at || '') + '</span>' +
                        '</a>';
                });

                list.innerHTML = html;
            }

            function poll() {
                var url = apiBaseUrl + '?endpoint=chat/poll&phone=' + encodeURIComponent(phone) +
                    (lastTimestamp ? '&since=' + encodeURIComponent(lastTimestamp) : '');

                fetch(url, { credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        setLive(true);

                        var shouldScroll = (thread.scrollTop + thread.clientHeight) >= (thread.scrollHeight - 40);

                        (data.messages || []).forEach(appendMessage);

                        if (shouldScroll) {
                            thread.scrollTop = thread.scrollHeight;
                        }

                        updateConversationsList(data.conversations);
                    })
                    .catch(function () {
                        setLive(false);
                    });
            }

            thread.scrollTop = thread.scrollHeight;
            setInterval(poll, 5000);

            var sendBtn = document.getElementById('lkn-hn-chat-send-btn');
            var messageBox = document.getElementById('lkn-hn-chat-message');
            var errorBox = document.getElementById('lkn-hn-chat-send-error');

            if (sendBtn) {
                sendBtn.addEventListener('click', function () {
                    var text = messageBox.value.trim();
                    if (!text) {
                        return;
                    }

                    errorBox.style.display = 'none';
                    sendBtn.disabled = true;

                    fetch(apiBaseUrl + '?endpoint=chat/send&phone=' + encodeURIComponent(phone), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: text }),
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            sendBtn.disabled = false;

                            if (!data.success) {
                                errorBox.textContent = data.error || 'Failed to send message.';
                                errorBox.style.display = 'block';
                                return;
                            }

                            messageBox.value = '';
                            poll();
                        })
                        .catch(function () {
                            sendBtn.disabled = false;
                            errorBox.textContent = 'Network error sending message.';
                            errorBox.style.display = 'block';
                        });
                });
            }
        })();
    </script>
{/block}
