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
        /* ===== 5.13.0: media bubbles + composer ===== */
        .lkn-hn-chat-bubble .media { margin-bottom: 4px; }
        .lkn-hn-chat-bubble .media img {
            display: block; max-width: 100%; max-height: 320px; border-radius: 4px; cursor: zoom-in;
        }
        .lkn-hn-chat-bubble .media img.sticker { max-width: 140px; max-height: 140px; cursor: default; }
        .lkn-hn-chat-bubble .media audio { width: 280px; max-width: 100%; display: block; }
        .lkn-hn-chat-bubble .media video { display: block; max-width: 100%; max-height: 320px; border-radius: 4px; }
        .lkn-hn-chat-doc {
            display: flex; align-items: center; gap: 10px; padding: 8px 10px;
            background: rgba(0,0,0,0.05); border-radius: 4px; color: inherit; text-decoration: none; min-width: 200px;
        }
        .lkn-hn-chat-doc:hover { background: rgba(0,0,0,0.09); text-decoration: none; color: inherit; }
        .lkn-hn-chat-doc i { font-size: 24px; color: #d9534f; }
        .lkn-hn-chat-doc .doc-name { font-weight: 600; word-break: break-all; display: block; }
        .lkn-hn-chat-doc .doc-meta { font-size: 11px; color: var(--dct-text-muted); }
        .lkn-hn-chat-unavailable { font-style: italic; color: var(--dct-text-muted); }
        .lkn-hn-chat-voice-label { font-size: 11px; color: var(--dct-text-muted); margin-bottom: 2px; }

        .dct-chat-composer-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .dct-chat-composer-actions .spacer { flex: 1 1 auto; }
        .dct-chat-icon-btn {
            border: 1px solid var(--dct-border-light, #ddd); background: #fff; border-radius: 50%;
            width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; color: #555; padding: 0;
        }
        .dct-chat-icon-btn:hover { background: var(--dct-surface-muted, #f5f5f5); }
        .dct-chat-icon-btn.recording { background: #d9534f; color: #fff; border-color: #d9534f; animation: dctPulse 1.2s infinite; }
        @keyframes dctPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(217,83,79,.5); } 50% { box-shadow: 0 0 0 6px rgba(217,83,79,0); } }
        #dct-chat-attachment {
            display: none; align-items: center; gap: 10px; padding: 8px 10px; margin-bottom: 8px;
            border: 1px dashed var(--dct-border-light, #ccc); border-radius: 6px; background: var(--dct-surface-muted, #fafafa);
        }
        #dct-chat-attachment img { max-height: 60px; max-width: 90px; border-radius: 4px; }
        #dct-chat-attachment .att-name { font-weight: 600; word-break: break-all; }
        #dct-chat-attachment .att-meta { font-size: 11px; color: var(--dct-text-muted); }
        #dct-chat-recorder {
            display: none; align-items: center; gap: 10px; padding: 8px 10px; margin-bottom: 8px;
            border-radius: 6px; background: #fdecea; color: #a94442;
        }
        #dct-chat-recorder .rec-dot { width: 10px; height: 10px; border-radius: 50%; background: #d9534f; animation: dctPulse 1.2s infinite; }
        #lkn-hn-chat-compose.dct-drop-hover { outline: 2px dashed #5cb85c; outline-offset: 4px; }

        /* ===== Emoji picker ===== */
        .dct-chat-composer-actions { position: relative; }
        .dct-chat-icon-btn.active { background: var(--dct-primary-light, #e8f0fe); color: #f0ad4e; }
        #dct-emoji-panel {
            display: none; position: absolute; bottom: 46px; left: 0; z-index: 50;
            width: 340px; max-width: calc(100vw - 40px); background: #fff;
            border: 1px solid var(--dct-border-light, #ddd); border-radius: 8px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.15);
        }
        #dct-emoji-panel.open { display: block; }
        #dct-emoji-panel .emoji-search { padding: 8px 8px 4px; }
        #dct-emoji-panel .emoji-search input {
            width: 100%; border: 1px solid var(--dct-border-light, #ddd); border-radius: 4px; padding: 5px 8px; font-size: 13px;
        }
        #dct-emoji-panel .emoji-tabs { display: flex; border-bottom: 1px solid var(--dct-border-light, #eee); padding: 0 4px; }
        #dct-emoji-panel .emoji-tabs button {
            flex: 1 1 0; border: 0; background: none; padding: 6px 0; font-size: 18px; cursor: pointer;
            border-bottom: 2px solid transparent; opacity: .6;
        }
        #dct-emoji-panel .emoji-tabs button.active { border-bottom-color: #5cb85c; opacity: 1; }
        #dct-emoji-panel .emoji-grid {
            display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; padding: 6px;
            height: 220px; overflow-y: auto; align-content: start;
        }
        #dct-emoji-panel .emoji-grid button {
            border: 0; background: none; font-size: 22px; line-height: 1; padding: 5px 0; border-radius: 4px; cursor: pointer;
            font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif;
        }
        #dct-emoji-panel .emoji-grid button:hover { background: var(--dct-surface-muted, #f2f2f2); }
        #dct-emoji-panel .emoji-empty { grid-column: 1 / -1; text-align: center; color: var(--dct-text-muted); font-size: 12px; padding: 20px 0; }
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
                        <div id="lkn-hn-chat-thread" data-phone="{$page_params.selected_phone|escape}">
                            {if !$page_params.thread}
                                <div class="dct-text-muted" id="lkn-hn-chat-empty" style="text-align: center; padding: 20px;">
                                    {lkn_hn_lang text="No messages in this conversation."}
                                </div>
                            {/if}
                        </div>
                        <script type="application/json" id="lkn-hn-chat-initial">{$page_params.thread_json}</script>
                    </div>
                </div>

                <div class="dct-card">
                    <div class="dct-card-header">
                        <span class="dct-card-title">{lkn_hn_lang text="Send a message"}</span>
                    </div>
                    <div class="dct-card-body">
                        <div id="lkn-hn-chat-send-error" class="dct-alert dct-alert-danger" style="display: none;"></div>

                        <div id="lkn-hn-chat-compose">
                        <div class="dct-form-group">
                            <label class="dct-form-label">{lkn_hn_lang text="Phone number (E.164 format, no + sign, e.g. 15551234567)"}</label>
                            <input
                                type="text"
                                class="dct-input"
                                id="lkn-hn-chat-phone"
                                value="{$page_params.selected_phone|escape}"
                                readonly
                            >
                        </div>

                        <div id="dct-chat-attachment">
                            <span id="dct-chat-att-preview"></span>
                            <div style="flex: 1 1 auto; min-width: 0;">
                                <div class="att-name" id="dct-chat-att-name"></div>
                                <div class="att-meta" id="dct-chat-att-meta"></div>
                                <label style="font-weight: normal; font-size: 12px; margin: 4px 0 0;" id="dct-chat-att-doc-wrap">
                                    <input type="checkbox" id="dct-chat-att-as-doc"> {lkn_hn_lang text="Send as document (original quality)"}
                                </label>
                            </div>
                            <button type="button" class="dct-chat-icon-btn" id="dct-chat-att-remove" title="{lkn_hn_lang text='Remove'}"><i class="fas fa-times"></i></button>
                        </div>

                        <div id="dct-chat-recorder">
                            <span class="rec-dot"></span>
                            <strong id="dct-chat-rec-time">0:00</strong>
                            <span style="flex: 1 1 auto;">{lkn_hn_lang text="Recording voice message..."}</span>
                            <button type="button" class="dct-button dct-button-ghost dct-text-small" id="dct-chat-rec-cancel">{lkn_hn_lang text="Cancel"}</button>
                            <button type="button" class="dct-button dct-button-primary dct-text-small" id="dct-chat-rec-send"><i class="fas fa-paper-plane"></i> {lkn_hn_lang text="Send voice"}</button>
                        </div>

                        <div class="dct-form-group" style="margin-bottom: 0;">
                            <label class="dct-form-label" id="dct-chat-message-label">{lkn_hn_lang text="Message"}</label>
                            <textarea
                                class="dct-textarea"
                                id="lkn-hn-chat-message"
                                rows="3"
                                style="height: auto;"
                                placeholder="{lkn_hn_lang text='Type a message, or paste / drop a file here'}"
                            ></textarea>
                        </div>

                        <div class="dct-chat-composer-actions">
                            <input type="file" id="dct-chat-file" style="display: none;">
                            <div id="dct-emoji-panel" role="dialog" aria-label="{lkn_hn_lang text='Emoji'}"></div>
                            <button type="button" class="dct-chat-icon-btn" id="dct-chat-emoji-btn" title="{lkn_hn_lang text='Emoji'}" aria-haspopup="true">
                                <i class="far fa-smile"></i>
                            </button>
                            <button type="button" class="dct-chat-icon-btn" id="dct-chat-attach-btn" title="{lkn_hn_lang text='Attach image, video, audio or document'}">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <button type="button" class="dct-chat-icon-btn" id="dct-chat-record-btn" title="{lkn_hn_lang text='Record voice message'}">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <span class="spacer"></span>
                            <span class="dct-text-small dct-text-muted" id="dct-chat-sending" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> {lkn_hn_lang text="Sending..."}
                            </span>
                            <button type="button" class="dct-button dct-button-primary" id="lkn-hn-chat-send-btn">
                                <i class="fas fa-paper-plane"></i> {lkn_hn_lang text="Send"}
                            </button>
                        </div>
                        </div>

                        <div class="dct-form-help" style="margin-top: 10px;">
                            {lkn_hn_lang text="Free-form text only works if this contact messaged you within the last 24 hours (Meta's customer service window). Outside that window, use an approved notification template instead."}
                            <br>
                            {lkn_hn_lang text="Supported: images (JPG/PNG, 5 MB), videos (MP4/3GP, 16 MB), audio (MP3/M4A/AAC/OGG/AMR, 16 MB), documents (any type, 100 MB). Other image/video formats are sent as documents."}
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
        window.DCT_CHAT_CONFIG = {
            baseEndpoint: '{$lkn_hn_base_endpoint|escape:"javascript"}',
            apiBaseUrl: '{$lkn_hn_api_base_url|escape:"javascript"}',
            token: '{$page_params.chat_token|escape:"javascript"}',
            lameJsUrl: '{$page_params.lame_js_url|escape:"javascript"}',
            uploadMaxBytes: {$page_params.upload_max_bytes|default:0},
            i18n: {$page_params.chat_i18n_json}
        };
    </script>
    <script>
    {literal}
        (function () {
            var cfg = window.DCT_CHAT_CONFIG;
            var thread = document.getElementById('lkn-hn-chat-thread');
            if (!thread) {
                return;
            }

            // Mobile: only start in "thread open" state if this phone was
            // explicitly requested via the URL.
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
            var lastTimestamp = null;
            var renderedIds = {};

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str == null ? '' : String(str);
                return div.innerHTML;
            }

            function formatBytes(bytes) {
                if (!bytes && bytes !== 0) { return ''; }
                if (bytes < 1024) { return bytes + ' B'; }
                if (bytes < 1048576) { return (bytes / 1024).toFixed(1) + ' KB'; }
                return (bytes / 1048576).toFixed(1) + ' MB';
            }

            function mediaUrl(id, download) {
                return cfg.apiBaseUrl + '?endpoint=chat/media&id=' + encodeURIComponent(id) + (download ? '&download=1' : '');
            }

            function docIcon(mime, name) {
                var n = (name || '').toLowerCase();
                if (mime === 'application/pdf' || /\.pdf$/.test(n)) { return 'fa-file-pdf'; }
                if (/(word|\.docx?$)/.test(mime + n)) { return 'fa-file-word'; }
                if (/(sheet|excel|csv|\.xlsx?$)/.test(mime + n)) { return 'fa-file-excel'; }
                if (/(zip|rar|7z|compressed)/.test(mime + n)) { return 'fa-file-archive'; }
                if (/^image\//.test(mime || '')) { return 'fa-file-image'; }
                if (/^video\//.test(mime || '')) { return 'fa-file-video'; }
                if (/^audio\//.test(mime || '')) { return 'fa-file-audio'; }
                return 'fa-file-alt';
            }

            function renderMedia(m) {
                var type = m.type || 'text';

                if (m.media_unavailable) {
                    var label = { image: 'Image', audio: 'Audio', video: 'Video', document: 'Document', sticker: 'Sticker' }[type] || type;
                    return '<div class="media lkn-hn-chat-unavailable"><i class="far fa-eye-slash"></i> [' + escapeHtml(label) + '] ' + escapeHtml(cfg.i18n.unavailable) + '</div>';
                }

                if (!m.has_media || !m.id) {
                    return '';
                }

                var src = mediaUrl(m.id, false);

                switch (type) {
                    case 'image':
                        return '<div class="media"><a href="' + src + '" target="_blank" rel="noopener"><img loading="lazy" src="' + src + '" alt="' + escapeHtml(cfg.i18n.image) + '"></a></div>';
                    case 'sticker':
                        return '<div class="media"><img class="sticker" loading="lazy" src="' + src + '" alt="Sticker"></div>';
                    case 'audio':
                        return '<div class="media"><div class="lkn-hn-chat-voice-label"><i class="fas fa-microphone"></i> ' + escapeHtml(cfg.i18n.voice) + '</div>' +
                            '<audio controls preload="none" src="' + src + '"></audio></div>';
                    case 'video':
                        return '<div class="media"><video controls preload="metadata" src="' + src + '"></video></div>';
                    default:
                        var name = m.media_filename || ('document-' + m.id);
                        return '<div class="media"><a class="lkn-hn-chat-doc" href="' + mediaUrl(m.id, true) + '">' +
                            '<i class="far ' + docIcon(m.media_mime, name) + '"></i>' +
                            '<span><span class="doc-name">' + escapeHtml(name) + '</span>' +
                            '<span class="doc-meta">' + escapeHtml(formatBytes(m.media_size)) + (m.media_mime ? ' &middot; ' + escapeHtml(m.media_mime) : '') + '</span></span>' +
                            '</a></div>';
                }
            }

            function appendMessage(message) {
                if (message.id && renderedIds[message.id]) {
                    return;
                }
                if (message.id) {
                    renderedIds[message.id] = true;
                }

                var empty = document.getElementById('lkn-hn-chat-empty');
                if (empty) { empty.parentNode.removeChild(empty); }

                var row = document.createElement('div');
                row.className = 'lkn-hn-chat-bubble-row ' + message.direction;

                var meta = message.sent_at + (message.direction === 'outbound' && message.status ? ' · ' + message.status : '');
                var mediaHtml = renderMedia(message);
                var isMedia = (message.type || 'text') !== 'text';
                var bodyHtml = '';

                if (message.body) {
                    bodyHtml = '<div class="body">' + escapeHtml(message.body) + '</div>';
                } else if (!isMedia) {
                    bodyHtml = '<div class="body">—</div>';
                }

                row.innerHTML = '<div class="lkn-hn-chat-bubble ' + message.direction + '">' +
                    mediaHtml + bodyHtml +
                    '<div class="meta">' + escapeHtml(meta) + '</div>' +
                    '</div>';

                thread.appendChild(row);

                if (!lastTimestamp || message.sent_at > lastTimestamp) {
                    lastTimestamp = message.sent_at;
                }
            }

            try {
                JSON.parse(document.getElementById('lkn-hn-chat-initial').textContent || '[]').forEach(appendMessage);
            } catch (e) {
                console.error('DCT chat: could not parse initial thread', e);
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
                    var arrow = c.last_message_direction === 'outbound' ? '→ ' : '';
                    var preview = (c.last_message_preview || '');
                    if (preview.length > 40) {
                        preview = preview.substring(0, 40) + '...';
                    }

                    html += '<a href="' + cfg.baseEndpoint + '&page=notification-chat&phone=' + encodeURIComponent(c.phone_number) + '" ' +
                        'class="lkn-hn-chat-list-item' + (isActive ? ' active' : '') + '">' +
                        '<span class="name">' + escapeHtml(name) + '</span>' +
                        '<span class="preview">' + escapeHtml(arrow + preview) + '</span>' +
                        '<span class="time">' + escapeHtml(c.last_message_at || '') + '</span>' +
                        '</a>';
                });

                list.innerHTML = html;
            }

            function poll() {
                var url = cfg.apiBaseUrl + '?endpoint=chat/poll&phone=' + encodeURIComponent(phone) +
                    (lastTimestamp ? '&since=' + encodeURIComponent(lastTimestamp) : '');

                return fetch(url, { credentials: 'same-origin' })
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
            // Images load after first paint - keep the view pinned to the bottom.
            thread.addEventListener('load', function (e) {
                if (e.target && e.target.tagName === 'IMG') {
                    if ((thread.scrollHeight - thread.scrollTop - thread.clientHeight) < 400) {
                        thread.scrollTop = thread.scrollHeight;
                    }
                }
            }, true);
            setInterval(poll, 5000);

            /* ================= Composer ================= */
            var sendBtn = document.getElementById('lkn-hn-chat-send-btn');
            var messageBox = document.getElementById('lkn-hn-chat-message');
            var messageLabel = document.getElementById('dct-chat-message-label');
            var errorBox = document.getElementById('lkn-hn-chat-send-error');
            var sendingEl = document.getElementById('dct-chat-sending');
            var fileInput = document.getElementById('dct-chat-file');
            var attachBtn = document.getElementById('dct-chat-attach-btn');
            var recordBtn = document.getElementById('dct-chat-record-btn');
            var attBox = document.getElementById('dct-chat-attachment');
            var attPreview = document.getElementById('dct-chat-att-preview');
            var attName = document.getElementById('dct-chat-att-name');
            var attMeta = document.getElementById('dct-chat-att-meta');
            var attAsDoc = document.getElementById('dct-chat-att-as-doc');
            var attDocWrap = document.getElementById('dct-chat-att-doc-wrap');
            var attRemove = document.getElementById('dct-chat-att-remove');
            var compose = document.getElementById('lkn-hn-chat-compose');
            var recorderBox = document.getElementById('dct-chat-recorder');
            var recTime = document.getElementById('dct-chat-rec-time');
            var recCancel = document.getElementById('dct-chat-rec-cancel');
            var recSend = document.getElementById('dct-chat-rec-send');

            if (!sendBtn) {
                return;
            }

            var pendingFile = null;
            var busy = false;

            function showError(msg) {
                errorBox.textContent = msg;
                errorBox.style.display = 'block';
            }

            function setBusy(state) {
                busy = state;
                sendBtn.disabled = state;
                attachBtn.disabled = state;
                recordBtn.disabled = state;
                sendingEl.style.display = state ? 'inline' : 'none';
            }

            function clearAttachment() {
                pendingFile = null;
                fileInput.value = '';
                attBox.style.display = 'none';
                attPreview.innerHTML = '';
                attAsDoc.checked = false;
                messageLabel.textContent = cfg.i18n.messageLabel;
            }

            function setAttachment(file) {
                errorBox.style.display = 'none';

                if (cfg.uploadMaxBytes && file.size > cfg.uploadMaxBytes) {
                    showError(cfg.i18n.tooBig + ' (' + formatBytes(cfg.uploadMaxBytes) + ').');
                    return;
                }

                pendingFile = file;
                attName.textContent = file.name || 'file';
                attMeta.textContent = formatBytes(file.size) + (file.type ? ' · ' + file.type : '');
                attPreview.innerHTML = '';

                var isMedia = /^(image|video)\//.test(file.type || '');
                attDocWrap.style.display = isMedia ? 'block' : 'none';

                if (/^image\//.test(file.type || '')) {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    attPreview.appendChild(img);
                } else {
                    attPreview.innerHTML = '<i class="far ' + docIcon(file.type, file.name) + '" style="font-size: 32px;"></i>';
                }

                attBox.style.display = 'flex';
                messageLabel.textContent = /^audio\//.test(file.type || '') ? cfg.i18n.messageLabel : cfg.i18n.captionLabel;
                messageBox.focus();
            }

            attachBtn.addEventListener('click', function () { fileInput.click(); });
            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files[0]) {
                    setAttachment(fileInput.files[0]);
                }
            });
            attRemove.addEventListener('click', clearAttachment);

            // Paste an image/file straight into the message box.
            messageBox.addEventListener('paste', function (e) {
                var items = (e.clipboardData && e.clipboardData.files) || [];
                if (items.length > 0) {
                    e.preventDefault();
                    var f = items[0];
                    if (!f.name || f.name === 'image.png') {
                        f = new File([f], 'pasted-' + Date.now() + '.' + ((f.type.split('/')[1] || 'png').replace('jpeg', 'jpg')), { type: f.type });
                    }
                    setAttachment(f);
                }
            });

            // Drag & drop onto the composer.
            ['dragenter', 'dragover'].forEach(function (ev) {
                compose.addEventListener(ev, function (e) { e.preventDefault(); compose.classList.add('dct-drop-hover'); });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                compose.addEventListener(ev, function (e) { e.preventDefault(); compose.classList.remove('dct-drop-hover'); });
            });
            compose.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                    setAttachment(e.dataTransfer.files[0]);
                }
            });

            function handleResponse(res) {
                return res.text().then(function (text) {
                    var data;
                    try { data = JSON.parse(text); } catch (e) { data = { success: false, error: 'Unexpected server response (HTTP ' + res.status + ').' }; }
                    return data;
                });
            }

            function sendText(text) {
                return fetch(cfg.apiBaseUrl + '?endpoint=chat/send&phone=' + encodeURIComponent(phone), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-DCT-Chat-Token': cfg.token },
                    body: JSON.stringify({ message: text })
                }).then(handleResponse);
            }

            function sendFile(file, caption, asDocument) {
                var fd = new FormData();
                fd.append('file', file, file.name || 'file');
                if (caption) { fd.append('caption', caption); }
                if (asDocument) { fd.append('as_document', '1'); }
                fd.append('dct_chat_token', cfg.token);

                return fetch(cfg.apiBaseUrl + '?endpoint=chat/send-media&phone=' + encodeURIComponent(phone), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-DCT-Chat-Token': cfg.token },
                    body: fd
                }).then(handleResponse);
            }

            function afterSend(data, onSuccess) {
                setBusy(false);
                if (!data.success) {
                    showError(data.error || 'Failed to send message.');
                    return;
                }
                if (onSuccess) { onSuccess(); }
                poll().then(function () { thread.scrollTop = thread.scrollHeight; });
            }

            sendBtn.addEventListener('click', function () {
                if (busy) { return; }
                var text = messageBox.value.trim();
                errorBox.style.display = 'none';

                if (pendingFile) {
                    var isAudio = /^audio\//.test(pendingFile.type || '');
                    setBusy(true);

                    // Audio can't carry a caption - send any typed text as a
                    // separate message afterwards.
                    sendFile(pendingFile, isAudio ? '' : text, attAsDoc.checked)
                        .then(function (data) {
                            if (data.success && isAudio && text) {
                                return sendText(text);
                            }
                            return data;
                        })
                        .then(function (data) {
                            afterSend(data, function () { clearAttachment(); messageBox.value = ''; });
                        })
                        .catch(function () { setBusy(false); showError(cfg.i18n.netError); });
                    return;
                }

                if (!text) {
                    return;
                }

                setBusy(true);
                sendText(text)
                    .then(function (data) { afterSend(data, function () { messageBox.value = ''; }); })
                    .catch(function () { setBusy(false); showError(cfg.i18n.netError); });
            });

            /* ================= Emoji picker ================= */
            (function () {
                var btn = document.getElementById('dct-chat-emoji-btn');
                var panel = document.getElementById('dct-emoji-panel');
                if (!btn || !panel) { return; }

                // [icon, name, emojis(space separated), keywords for search]
                var CATS = [
                    ['😀', 'Smileys', '😀 😃 😄 😁 😆 😅 🤣 😂 🙂 🙃 😉 😊 😇 🥰 😍 🤩 😘 😗 😚 😙 😋 😛 😜 🤪 😝 🤑 🤗 🤭 🤫 🤔 🤐 🤨 😐 😑 😶 😏 😒 🙄 😬 😌 😔 😪 🤤 😴 😷 🤒 🤕 🤢 🤮 🥵 🥶 🥴 😵 🤯 🤠 🥳 😎 🤓 🧐 😕 😟 🙁 😮 😯 😲 😳 🥺 😦 😧 😨 😰 😥 😢 😭 😱 😖 😣 😞 😓 😩 😫 🥱 😤 😡 😠 🤬'],
                    ['👍', 'Gestures', '👍 👎 👌 🤌 ✌️ 🤞 🤟 🤘 🤙 👈 👉 👆 👇 ☝️ ✋ 🤚 🖐️ 🖖 👋 👏 🙌 👐 🤲 🤝 🙏 ✍️ 💪 🫡 🫶 🙋 🙆 🙅 🤷 🤦 💁'],
                    ['❤️', 'Hearts', '❤️ 🧡 💛 💚 💙 💜 🖤 🤍 🤎 💔 ❣️ 💕 💞 💓 💗 💖 💘 💝 💟 ✨ ⭐ 🌟 💫 🔥 🎉 🎊 🎁 🎈 🏆 🥇 🎯 💯'],
                    ['💼', 'Business', '💼 📧 📨 📩 📞 📱 💻 🖥️ ⌨️ 🖨️ 🌐 🔒 🔓 🔑 🛡️ ⚙️ 🛠️ 🔧 🧾 📄 📃 📑 📊 📈 📉 📅 📆 🗓️ ⏰ ⏳ ⌛ 💰 💵 💳 🏦 🧮 📦 🚚 🚀 💡 📌 📎 🔗 ✏️ 📝 🔔 📢'],
                    ['✅', 'Symbols', '✅ ☑️ ✔️ ❌ ❎ ⚠️ 🚫 ⛔ ❗ ❓ ❕ ❔ ‼️ ⁉️ ℹ️ 🆗 🆕 🆓 🔴 🟠 🟡 🟢 🔵 🟣 ⚫ ⚪ ➡️ ⬅️ ⬆️ ⬇️ 🔄 🔁 ▶️ ⏸️ ⏹️ 🔝 🔜 💤 ♻️ 🕐 🌙 ☀️ ⛅ 🌧️']
                ];
                var KEYWORDS = {
                    '😀': 'smile happy grin', '😂': 'laugh lol joy tears', '🤣': 'rofl laugh', '😊': 'smile blush happy', '😍': 'love heart eyes',
                    '😘': 'kiss', '😉': 'wink', '😎': 'cool sunglasses', '🤔': 'think hmm', '😢': 'sad cry', '😭': 'cry sob sad', '😡': 'angry mad',
                    '😱': 'scream shock', '😴': 'sleep', '🙄': 'eyeroll', '🥳': 'party celebrate', '😇': 'angel innocent', '🙂': 'smile',
                    '👍': 'thumbs up ok yes like good', '👎': 'thumbs down no dislike', '👌': 'ok perfect', '🙏': 'thanks please pray namaste',
                    '👏': 'clap applause', '🤝': 'handshake deal agree', '👋': 'wave hello hi bye', '💪': 'strong muscle', '🙌': 'hooray celebrate',
                    '❤️': 'love heart red', '🔥': 'fire hot lit', '🎉': 'party tada celebrate congrats', '✨': 'sparkles', '⭐': 'star', '💯': 'hundred perfect',
                    '💼': 'work business briefcase', '📧': 'email mail', '📞': 'phone call', '📱': 'mobile phone', '💻': 'laptop computer', '🌐': 'web globe internet domain',
                    '🔒': 'lock secure ssl', '🔑': 'key password', '🛠️': 'tools fix support', '🧾': 'invoice receipt bill', '📄': 'document page file', '📊': 'chart stats report',
                    '📅': 'calendar date', '⏰': 'alarm clock time reminder', '⏳': 'hourglass wait pending', '💰': 'money', '💳': 'card payment', '🚀': 'rocket launch fast',
                    '💡': 'idea bulb', '📎': 'paperclip attach', '📝': 'note memo write', '🔔': 'bell notification', '✅': 'check done yes ok tick', '❌': 'cross no wrong cancel',
                    '⚠️': 'warning alert', '❗': 'important exclamation', '❓': 'question', 'ℹ️': 'info information', '🔄': 'refresh renew sync', '🟢': 'green online', '🔴': 'red offline'
                };

                var RECENT_KEY = 'dct_hn_recent_emojis';
                var activeCat = 0;

                function getRecent() {
                    try { return JSON.parse(window.localStorage.getItem(RECENT_KEY) || '[]'); } catch (e) { return []; }
                }
                function pushRecent(emoji) {
                    try {
                        var list = getRecent().filter(function (x) { return x !== emoji; });
                        list.unshift(emoji);
                        window.localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, 24)));
                    } catch (e) { /* storage unavailable - ignore */ }
                }

                function allCats() {
                    var recent = getRecent();
                    return recent.length ? [['🕘', 'Recent', recent.join(' ')]].concat(CATS) : CATS;
                }

                panel.innerHTML =
                    '<div class="emoji-search"><input type="text" id="dct-emoji-search" placeholder="Search emoji..." autocomplete="off"></div>' +
                    '<div class="emoji-tabs" id="dct-emoji-tabs"></div>' +
                    '<div class="emoji-grid" id="dct-emoji-grid"></div>';

                var tabsEl = document.getElementById('dct-emoji-tabs');
                var gridEl = document.getElementById('dct-emoji-grid');
                var searchEl = document.getElementById('dct-emoji-search');

                function renderGrid(list) {
                    if (!list.length) {
                        gridEl.innerHTML = '<div class="emoji-empty">No emoji found</div>';
                        return;
                    }
                    gridEl.innerHTML = list.map(function (e) {
                        return '<button type="button" data-emoji="' + e + '" title="' + (KEYWORDS[e] || '').split(' ')[0] + '">' + e + '</button>';
                    }).join('');
                }

                function render() {
                    var cats = allCats();
                    if (activeCat >= cats.length) { activeCat = 0; }
                    tabsEl.innerHTML = cats.map(function (c, i) {
                        return '<button type="button" data-cat="' + i + '" title="' + c[1] + '" class="' + (i === activeCat ? 'active' : '') + '">' + c[0] + '</button>';
                    }).join('');
                    renderGrid(cats[activeCat][2].split(' ').filter(Boolean));
                }

                function search(q) {
                    q = q.trim().toLowerCase();
                    if (!q) { render(); return; }
                    var seen = {}, out = [];
                    CATS.forEach(function (c) {
                        c[2].split(' ').forEach(function (e) {
                            if (!e || seen[e]) { return; }
                            if ((KEYWORDS[e] || '').indexOf(q) !== -1 || c[1].toLowerCase().indexOf(q) === 0) {
                                seen[e] = true; out.push(e);
                            }
                        });
                    });
                    renderGrid(out);
                }

                function insertAtCursor(text) {
                    var el = messageBox;
                    var start = el.selectionStart != null ? el.selectionStart : el.value.length;
                    var end = el.selectionEnd != null ? el.selectionEnd : el.value.length;
                    el.value = el.value.slice(0, start) + text + el.value.slice(end);
                    var pos = start + text.length;
                    el.focus();
                    try { el.setSelectionRange(pos, pos); } catch (e) { /* ignore */ }
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }

                function open() {
                    activeCat = 0;
                    searchEl.value = '';
                    render();
                    panel.classList.add('open');
                    btn.classList.add('active');
                }
                function close() {
                    panel.classList.remove('open');
                    btn.classList.remove('active');
                }

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    panel.classList.contains('open') ? close() : open();
                });

                tabsEl.addEventListener('click', function (e) {
                    var t = e.target.closest('button[data-cat]');
                    if (!t) { return; }
                    activeCat = parseInt(t.getAttribute('data-cat'), 10) || 0;
                    searchEl.value = '';
                    render();
                });

                // mousedown (not click) so the textarea keeps its caret position.
                gridEl.addEventListener('mousedown', function (e) {
                    var t = e.target.closest('button[data-emoji]');
                    if (!t) { return; }
                    e.preventDefault();
                    var emoji = t.getAttribute('data-emoji');
                    insertAtCursor(emoji);
                    pushRecent(emoji);
                    if (e.shiftKey) { close(); } // Shift+click inserts and closes
                });

                searchEl.addEventListener('input', function () { search(searchEl.value); });
                searchEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        var first = gridEl.querySelector('button[data-emoji]');
                        if (first) { insertAtCursor(first.getAttribute('data-emoji')); pushRecent(first.getAttribute('data-emoji')); }
                    }
                });

                document.addEventListener('mousedown', function (e) {
                    if (panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) {
                        close();
                    }
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && panel.classList.contains('open')) { close(); messageBox.focus(); }
                });

                // Close after sending.
                sendBtn.addEventListener('click', close);
            })();

            // Ctrl/Cmd + Enter sends.
            messageBox.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    sendBtn.click();
                }
            });

            /* ================= Voice recording =================
             * WhatsApp accepts audio/ogg (Opus), audio/mpeg, audio/mp4, aac and
             * amr - NOT the audio/webm Chrome/Edge record by default. So:
             *  - Firefox: native MediaRecorder -> OGG/Opus (a true voice note);
             *  - everyone else: capture PCM via Web Audio and encode MP3 in the
             *    browser with lamejs (bundled locally in assets/js/vendor).
             */
            var rec = null;

            function loadLame() {
                if (window.lamejs) { return Promise.resolve(); }
                return new Promise(function (resolve, reject) {
                    var s = document.createElement('script');
                    s.src = cfg.lameJsUrl;
                    s.onload = function () { window.lamejs ? resolve() : reject(new Error('lamejs missing')); };
                    s.onerror = function () { reject(new Error('Could not load MP3 encoder')); };
                    document.head.appendChild(s);
                });
            }

            function fmtTime(sec) {
                var m = Math.floor(sec / 60), s = Math.floor(sec % 60);
                return m + ':' + (s < 10 ? '0' : '') + s;
            }

            function stopTracks(stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
            }

            function startRecording() {
                errorBox.style.display = 'none';

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showError(cfg.i18n.micDenied + ' (HTTPS is required.)');
                    return;
                }

                var useOgg = window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported('audio/ogg;codecs=opus');

                (useOgg ? Promise.resolve() : loadLame())
                    .then(function () {
                        return navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } });
                    })
                    .then(function (stream) {
                        rec = { stream: stream, started: Date.now(), cancelled: false };

                        if (useOgg) {
                            rec.kind = 'ogg';
                            rec.chunks = [];
                            rec.mr = new MediaRecorder(stream, { mimeType: 'audio/ogg;codecs=opus' });
                            rec.mr.ondataavailable = function (e) { if (e.data && e.data.size) { rec.chunks.push(e.data); } };
                            rec.mr.start(250);
                        } else {
                            rec.kind = 'mp3';
                            var AC = window.AudioContext || window.webkitAudioContext;
                            rec.ctx = new AC();
                            rec.source = rec.ctx.createMediaStreamSource(stream);
                            rec.proc = rec.ctx.createScriptProcessor(4096, 1, 1);
                            var rate = rec.ctx.sampleRate;
                            // MP3 only supports 32/44.1/48 kHz.
                            rec.rate = [32000, 44100, 48000].indexOf(rate) !== -1 ? rate : 44100;
                            rec.encoder = new lamejs.Mp3Encoder(1, rec.rate, 64);
                            rec.mp3 = [];
                            rec.proc.onaudioprocess = function (e) {
                                var input = e.inputBuffer.getChannelData(0);
                                var samples = new Int16Array(input.length);
                                for (var i = 0; i < input.length; i++) {
                                    var v = Math.max(-1, Math.min(1, input[i]));
                                    samples[i] = v < 0 ? v * 0x8000 : v * 0x7FFF;
                                }
                                var buf = rec.encoder.encodeBuffer(samples);
                                if (buf.length) { rec.mp3.push(new Int8Array(buf)); }
                            };
                            rec.source.connect(rec.proc);
                            rec.proc.connect(rec.ctx.destination);
                        }

                        recordBtn.classList.add('recording');
                        recorderBox.style.display = 'flex';
                        recTime.textContent = '0:00';
                        rec.timer = setInterval(function () {
                            var sec = (Date.now() - rec.started) / 1000;
                            recTime.textContent = fmtTime(sec);
                            if (sec >= 600) { finishRecording(true); } // 10-min safety cap
                        }, 250);
                    })
                    .catch(function (err) {
                        console.error(err);
                        showError(cfg.i18n.micDenied);
                        rec = null;
                    });
            }

            function finishRecording(send) {
                if (!rec) { return; }
                var r = rec;
                rec = null;

                clearInterval(r.timer);
                recordBtn.classList.remove('recording');
                recorderBox.style.display = 'none';

                var done = function (blob, ext, mime) {
                    stopTracks(r.stream);
                    if (!send || !blob || blob.size < 500) { return; }

                    var file = new File([blob], 'voice-' + Date.now() + '.' + ext, { type: mime });
                    setBusy(true);
                    sendFile(file, '', false)
                        .then(function (data) { afterSend(data); })
                        .catch(function () { setBusy(false); showError(cfg.i18n.netError); });
                };

                if (r.kind === 'ogg') {
                    r.mr.onstop = function () { done(new Blob(r.chunks, { type: 'audio/ogg' }), 'ogg', 'audio/ogg'); };
                    r.mr.stop();
                } else {
                    try { r.source.disconnect(); r.proc.disconnect(); } catch (e) { /* ignore */ }
                    var end = r.encoder.flush();
                    if (end.length) { r.mp3.push(new Int8Array(end)); }
                    r.ctx.close();
                    done(new Blob(r.mp3, { type: 'audio/mpeg' }), 'mp3', 'audio/mpeg');
                }
            }

            recordBtn.addEventListener('click', function () {
                if (busy) { return; }
                if (rec) { finishRecording(true); } else { startRecording(); }
            });
            recSend.addEventListener('click', function () { finishRecording(true); });
            recCancel.addEventListener('click', function () { finishRecording(false); });
        })();
    {/literal}
    </script>
{/block}
