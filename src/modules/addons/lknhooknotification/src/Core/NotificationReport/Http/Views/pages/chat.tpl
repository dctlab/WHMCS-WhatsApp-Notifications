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
        .lkn-hn-chat-list {
            max-height: 640px;
            overflow-y: auto;
            padding: 0;
        }

        .lkn-hn-chat-list-item {
            display: block;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            text-decoration: none;
            color: inherit;
        }

        .lkn-hn-chat-list-item:hover {
            background: #f5f5f5;
            text-decoration: none;
            color: inherit;
        }

        .lkn-hn-chat-list-item.active {
            background: #eef3fb;
        }

        .lkn-hn-chat-list-item .name {
            font-weight: 600;
            display: block;
        }

        .lkn-hn-chat-list-item .preview {
            color: #888;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        .lkn-hn-chat-list-item .time {
            color: #aaa;
            font-size: 11px;
        }

        #lkn-hn-chat-thread {
            background: #ede0d3;
            min-height: 400px;
            max-height: 640px;
            overflow-y: auto;
            padding: 15px;
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
            box-shadow: 0 1px 1px rgba(0, 0, 0, .1);
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
            color: #999;
            font-size: 11px;
            margin-top: 4px;
            text-align: right;
        }
    </style>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">{lkn_hn_lang text="Conversations"}</div>
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
                        <div style="padding: 15px;" class="text-muted">
                            {lkn_hn_lang text="No conversations yet. Make sure the WhatsApp webhook is configured in the Meta WhatsApp settings page."}
                        </div>
                    {/foreach}
                </div>
            </div>
            <a href="{$lkn_hn_base_endpoint}&page=notification-conversations" class="btn btn-xs btn-link">
                {lkn_hn_lang text="View as table / analytics"}
            </a>
        </div>

        <div class="col-md-8">
            {if $page_params.selected_phone}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {lkn_hn_lang text="Conversation with"} {$page_params.selected_phone}
                    </div>
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
                        {/foreach}
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">{lkn_hn_lang text="Send a message"}</div>
                    <div class="panel-body">
                        <div id="lkn-hn-chat-send-error" class="alert alert-danger" style="display: none;"></div>

                        <label>{lkn_hn_lang text="Phone number (E.164 format, no + sign, e.g. 15551234567)"}</label>
                        <input
                            type="text"
                            class="form-control"
                            id="lkn-hn-chat-phone"
                            value="{$page_params.selected_phone}"
                            readonly
                            style="margin-bottom: 10px;"
                        >

                        <label>{lkn_hn_lang text="Message"}</label>
                        <textarea
                            class="form-control"
                            id="lkn-hn-chat-message"
                            rows="3"
                            style="margin-bottom: 10px;"
                        ></textarea>

                        <button type="button" class="btn btn-primary" id="lkn-hn-chat-send-btn">
                            {lkn_hn_lang text="Send"}
                        </button>

                        <p class="text-muted" style="margin-top: 10px;">
                            {lkn_hn_lang text="Free-form text only works if this contact messaged you within the last 24 hours (Meta's customer service window). Outside that window, use an approved notification template instead."}
                        </p>
                    </div>
                </div>
            {else}
                <div class="panel panel-default">
                    <div class="panel-body text-muted">
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
