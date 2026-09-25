{extends "{$lkn_hn_layout_path}/layout/layout.tpl"}

{block "page_title"}
    {lkn_hn_lang text="WhatsApp Conversations"}
{/block}

{block "page_content"}
{literal}
<style>
/* =====================================================================
   DCTLAB WhatsApp Conversations - WhatsApp Web style inbox (5.14.0)
   Everything is scoped under .wa-app so WHMCS admin styles are untouched.
   ===================================================================== */
.wa-app {
    --wa-accent: #00a884; --wa-accent-dark: #008069; --wa-accent-soft: #d9fdd3;
    --wa-bg: #ffffff; --wa-panel: #f0f2f5; --wa-border: #e9edef; --wa-hover: #f5f6f6; --wa-active: #f0f2f5;
    --wa-text: #111b21; --wa-text-2: #54656f; --wa-text-3: #667781; --wa-icon: #54656f;
    --wa-thread-bg: #efeae2; --wa-pattern-op: .06;
    --wa-in: #ffffff; --wa-out: #d9fdd3; --wa-bubble-text: #111b21; --wa-meta: #667781;
    --wa-chip: #ffffff; --wa-chip-text: #54656f; --wa-input: #ffffff; --wa-read: #53bdeb;
    --wa-warn-bg: #fff5c4; --wa-warn-text: #54656f; --wa-danger: #ea0038; --wa-shadow: 0 1px .5px rgba(11,20,26,.13);
    --wa-panel-shadow: 0 2px 5px rgba(11,20,26,.26), 0 2px 10px rgba(11,20,26,.16);
    position: relative; display: flex; width: 100%; height: calc(100vh - 190px); min-height: 560px;
    background: var(--wa-bg); color: var(--wa-text); border: 1px solid var(--wa-border); border-radius: 8px; overflow: hidden;
    font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14.2px;
    transition: background-color .25s, color .25s;
}
.wa-app[data-theme="dark"] {
    --wa-accent: #00a884; --wa-accent-dark: #06cf9c; --wa-accent-soft: #005c4b;
    --wa-bg: #111b21; --wa-panel: #202c33; --wa-border: #222d34; --wa-hover: #202c33; --wa-active: #2a3942;
    --wa-text: #e9edef; --wa-text-2: #aebac1; --wa-text-3: #8696a0; --wa-icon: #aebac1;
    --wa-thread-bg: #0b141a; --wa-pattern-op: .05;
    --wa-in: #202c33; --wa-out: #005c4b; --wa-bubble-text: #e9edef; --wa-meta: rgba(233,237,239,.6);
    --wa-chip: #182229; --wa-chip-text: #8696a0; --wa-input: #2a3942;
    --wa-warn-bg: #2a3942; --wa-warn-text: #d1d7db; --wa-shadow: 0 1px .5px rgba(11,20,26,.4);
}
.wa-app *, .wa-app *::before, .wa-app *::after { box-sizing: border-box; }
.wa-app button { font-family: inherit; }
.wa-app.wa-fullscreen { position: fixed; inset: 0; z-index: 1060; height: 100vh; border-radius: 0; border: 0; }
.wa-icon-btn {
    width: 40px; height: 40px; border: 0; border-radius: 50%; background: transparent; color: var(--wa-icon);
    display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; flex: 0 0 auto;
    transition: background-color .15s;
}
.wa-icon-btn:hover { background: rgba(84,101,111,.1); }
.wa-icon-btn.on { color: var(--wa-accent); }
.wa-icon-btn svg { width: 22px; height: 22px; fill: currentColor; }
.wa-icon-btn[disabled] { opacity: .45; cursor: default; }

/* ---------- side panel ---------- */
.wa-side { flex: 0 0 30%; min-width: 300px; max-width: 460px; display: flex; flex-direction: column; border-right: 1px solid var(--wa-border); background: var(--wa-bg); }
.wa-head { height: 59px; flex: 0 0 59px; display: flex; align-items: center; gap: 6px; padding: 0 12px 0 16px; background: var(--wa-panel); }
.wa-head .wa-title { flex: 1 1 auto; min-width: 0; }
.wa-head .wa-title strong { display: block; font-size: 16px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wa-head .wa-title small { display: block; color: var(--wa-text-3); font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wa-live { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #25d366; margin-right: 4px; vertical-align: middle; }
.wa-live.off { background: var(--wa-danger); }
.wa-avatar {
    flex: 0 0 auto; width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 600; font-size: 15px; text-transform: uppercase; user-select: none; position: relative;
}
.wa-avatar.lg { width: 49px; height: 49px; font-size: 17px; }
.wa-avatar svg { width: 60%; height: 60%; fill: rgba(255,255,255,.9); }
.wa-search { padding: 7px 12px; border-bottom: 1px solid var(--wa-border); }
.wa-search label { display: flex; align-items: center; gap: 10px; height: 35px; padding: 0 12px; margin: 0; background: var(--wa-panel); border-radius: 8px; font-weight: normal; }
.wa-search svg { width: 18px; height: 18px; fill: var(--wa-text-3); flex: 0 0 auto; }
.wa-search input { flex: 1 1 auto; border: 0; outline: 0; background: transparent; color: var(--wa-text); font-size: 14px; min-width: 0; height: 100%; padding: 0; box-shadow: none; }
.wa-search input::placeholder { color: var(--wa-text-3); }
.wa-filters { display: flex; gap: 8px; padding: 8px 12px; border-bottom: 1px solid var(--wa-border); flex-wrap: wrap; }
.wa-filters button {
    border: 0; border-radius: 16px; padding: 5px 12px; font-size: 13.5px; cursor: pointer;
    background: var(--wa-panel); color: var(--wa-text-2); transition: background-color .15s;
}
.wa-filters button.active { background: var(--wa-accent-soft); color: var(--wa-accent-dark); font-weight: 600; }
.wa-app[data-theme="dark"] .wa-filters button.active { color: #d9fdd3; }
.wa-filters .count { margin-left: 4px; }
.wa-list { flex: 1 1 auto; overflow-y: auto; }
.wa-item { display: flex; align-items: center; gap: 13px; padding: 0 0 0 13px; cursor: pointer; text-decoration: none !important; color: inherit !important; }
.wa-item:hover { background: var(--wa-hover); }
.wa-item.active { background: var(--wa-active); }
.wa-item .wa-item-body { flex: 1 1 auto; min-width: 0; padding: 12px 15px 12px 0; border-bottom: 1px solid var(--wa-border); }
.wa-item .row1, .wa-item .row2 { display: flex; align-items: center; gap: 6px; }
.wa-item .name { flex: 1 1 auto; min-width: 0; font-size: 16.5px; color: var(--wa-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wa-item .time { flex: 0 0 auto; font-size: 12px; color: var(--wa-text-3); }
.wa-item.unread .time { color: #25d366; font-weight: 600; }
.wa-item .row2 { margin-top: 2px; }
.wa-item .preview { flex: 1 1 auto; min-width: 0; font-size: 14px; color: var(--wa-text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 3px; }
.wa-item .preview .wa-tick { flex: 0 0 auto; }
.wa-item.unread .preview { color: var(--wa-text); font-weight: 500; }
.wa-badge { flex: 0 0 auto; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; background: #25d366; color: #fff; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; }
.wa-app[data-theme="dark"] .wa-badge { color: #111b21; }
.wa-tag { flex: 0 0 auto; font-size: 10.5px; padding: 1px 6px; border-radius: 8px; background: var(--wa-panel); color: var(--wa-text-3); }
.wa-list-empty { padding: 40px 24px; text-align: center; color: var(--wa-text-3); font-size: 14px; }
.wa-newchat .name { color: var(--wa-accent-dark); }

/* ---------- main panel ---------- */
.wa-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; position: relative; background: var(--wa-thread-bg); }
.wa-main-empty { flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px; background: var(--wa-panel); color: var(--wa-text-3); border-bottom: 6px solid var(--wa-accent); }
.wa-main-empty svg { width: 90px; height: 90px; fill: var(--wa-text-3); opacity: .35; margin-bottom: 20px; }
.wa-main-empty h2 { font-size: 30px; font-weight: 300; color: var(--wa-text); margin: 0 0 12px; }
.wa-main-empty p { max-width: 460px; font-size: 14px; line-height: 20px; margin: 0; }
.wa-chat { flex: 1 1 auto; display: none; flex-direction: column; min-height: 0; }
.wa-app.has-chat .wa-chat { display: flex; }
.wa-app.has-chat .wa-main-empty { display: none; }
.wa-chat-head { cursor: default; }
.wa-chat-head .wa-title a { color: inherit; }
.wa-back { display: none; }
.wa-window-chip { font-size: 11.5px; padding: 3px 8px; border-radius: 10px; background: var(--wa-accent-soft); color: var(--wa-accent-dark); white-space: nowrap; }
.wa-app[data-theme="dark"] .wa-window-chip { color: #d9fdd3; }
.wa-window-chip.closed { background: rgba(234,0,56,.1); color: var(--wa-danger); }
.wa-chat-search { display: none; padding: 7px 16px; background: var(--wa-panel); border-top: 1px solid var(--wa-border); }
.wa-chat-search.open { display: block; }
.wa-chat-search input { width: 100%; border: 0; outline: 0; border-radius: 8px; padding: 7px 12px; background: var(--wa-input); color: var(--wa-text); }
.wa-thread-wrap { flex: 1 1 auto; position: relative; min-height: 0; }
.wa-thread-wrap::before {
    content: ""; position: absolute; inset: 0; pointer-events: none; opacity: var(--wa-pattern-op);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.6' stroke-linecap='round'%3E%3Ccircle cx='18' cy='20' r='7'/%3E%3Cpath d='M70 12l8 8-8 8M52 20h26'/%3E%3Crect x='88' y='60' width='18' height='13' rx='3'/%3E%3Cpath d='M88 62l9 6 9-6'/%3E%3Cpath d='M20 70c4-8 14-8 18 0-4 8-14 8-18 0z'/%3E%3Ccircle cx='29' cy='70' r='2.5'/%3E%3Cpath d='M60 95l4-9 4 9-9-5.5h10z'/%3E%3Cpath d='M100 104a6 6 0 1 1-6-6'/%3E%3Cpath d='M44 44h12v10H44zM47 44v-3h6v3'/%3E%3C/g%3E%3C/svg%3E");
}
.wa-app[data-theme="dark"] .wa-thread-wrap::before { filter: invert(1); }
.wa-thread { position: absolute; inset: 0; overflow-y: auto; padding: 12px 7% 8px; display: flex; flex-direction: column; }
.wa-thread-inner { margin-top: auto; }
.wa-day { display: flex; justify-content: center; margin: 12px 0 10px; position: sticky; top: 4px; z-index: 2; }
.wa-day span { background: var(--wa-chip); color: var(--wa-chip-text); font-size: 12.5px; padding: 5px 12px 6px; border-radius: 7.5px; box-shadow: var(--wa-shadow); text-transform: uppercase; }
.wa-row { display: flex; margin-bottom: 2px; }
.wa-row.first { margin-top: 10px; }
.wa-row.outbound { justify-content: flex-end; }
.wa-bubble {
    position: relative; max-width: 65%; min-width: 90px; padding: 6px 7px 8px 9px; border-radius: 7.5px;
    background: var(--wa-in); color: var(--wa-bubble-text); box-shadow: var(--wa-shadow); word-wrap: break-word;
}
.wa-row.outbound .wa-bubble { background: var(--wa-out); }
.wa-row.first.inbound .wa-bubble { border-top-left-radius: 0; }
.wa-row.first.outbound .wa-bubble { border-top-right-radius: 0; }
.wa-row.first.inbound .wa-bubble::before, .wa-row.first.outbound .wa-bubble::before {
    content: ""; position: absolute; top: 0; width: 8px; height: 13px;
}
.wa-row.first.inbound .wa-bubble::before { left: -8px; background: var(--wa-in); clip-path: polygon(0 0, 100% 0, 100% 100%); }
.wa-row.first.outbound .wa-bubble::before { right: -8px; background: var(--wa-out); clip-path: polygon(0 0, 100% 0, 0 100%); }
.wa-text { white-space: pre-wrap; font-size: 14.2px; line-height: 19px; }
.wa-meta { float: right; margin: 6px 0 -6px 12px; position: relative; top: 2px; font-size: 11px; line-height: 15px; color: var(--wa-meta); display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
.wa-bubble > .wa-meta { float: none; display: flex; justify-content: flex-end; margin: 2px 0 -3px; top: 0; }
.wa-bubble.media-only .wa-meta { position: absolute; right: 10px; bottom: 8px; top: auto; float: none; margin: 0; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,.4); }
.wa-bubble.media-only .wa-meta .wa-tick { color: #fff; }
.wa-bubble.media-only .wa-meta .wa-tick.read { color: var(--wa-read); }
.wa-bubble.media-only.no-overlay .wa-meta { position: static; float: none; display: flex; justify-content: flex-end; margin: 2px 0 -3px; color: var(--wa-meta); text-shadow: none; }
.wa-bubble.media-only.no-overlay .wa-meta .wa-tick { color: var(--wa-meta); }
.wa-bubble.media-only.no-overlay .wa-meta .wa-tick.read { color: var(--wa-read); }
.wa-tick { display: inline-flex; width: 16px; height: 11px; color: var(--wa-meta); }
.wa-tick svg { width: 16px; height: 11px; fill: currentColor; }
.wa-tick.read { color: var(--wa-read); }
.wa-tick.failed { color: var(--wa-danger); width: 12px; }
.wa-tick.pending svg { width: 12px; height: 12px; }
.wa-media { margin: -3px -4px 4px -6px; }
.wa-media img { display: block; max-width: 330px; width: 100%; max-height: 340px; object-fit: cover; border-radius: 6px; cursor: zoom-in; background: rgba(0,0,0,.05); min-height: 60px; }
.wa-media img.sticker { max-width: 150px; max-height: 150px; object-fit: contain; background: transparent; cursor: default; }
.wa-bubble.sticker-bubble { background: transparent !important; box-shadow: none; }
.wa-bubble.sticker-bubble::before { display: none; }
.wa-media video { display: block; max-width: 330px; width: 100%; max-height: 340px; border-radius: 6px; background: #000; }
.wa-audio { display: flex; align-items: center; gap: 10px; min-width: 260px; padding: 4px 2px 2px; }
.wa-audio .wa-audio-icon { width: 44px; height: 44px; border-radius: 50%; background: var(--wa-accent); display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.wa-audio .wa-audio-icon svg { width: 22px; height: 22px; fill: #fff; }
.wa-audio audio { flex: 1 1 auto; height: 36px; min-width: 180px; max-width: 260px; }
.wa-doc { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin: 0 -2px 4px -4px; border-radius: 6px; background: rgba(0,0,0,.05); color: inherit !important; text-decoration: none !important; min-width: 240px; }
.wa-app[data-theme="dark"] .wa-doc { background: rgba(0,0,0,.2); }
.wa-doc:hover { background: rgba(0,0,0,.09); }
.wa-doc .ext { flex: 0 0 auto; width: 34px; height: 40px; border-radius: 4px; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px; background: #e25151; color: #fff; font-size: 9px; font-weight: 700; text-transform: uppercase; }
.wa-doc .info { min-width: 0; flex: 1 1 auto; }
.wa-doc .dname { display: block; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
.wa-doc .dmeta { display: block; font-size: 11.5px; color: var(--wa-meta); }
.wa-doc .dl { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--wa-meta); display: flex; align-items: center; justify-content: center; }
.wa-doc .dl svg { width: 16px; height: 16px; fill: var(--wa-meta); }
.wa-unavailable { font-style: italic; color: var(--wa-meta); display: flex; align-items: center; gap: 6px; }
.wa-unavailable svg { width: 16px; height: 16px; fill: currentColor; }
.wa-hl { background: #ffd279; color: #111b21; border-radius: 2px; }
.wa-bubble.dim { opacity: .35; }
.wa-scroll-down {
    position: absolute; right: 18px; bottom: 16px; width: 42px; height: 42px; border-radius: 50%; border: 0; cursor: pointer;
    background: var(--wa-chip); color: var(--wa-icon); box-shadow: var(--wa-panel-shadow); display: none; align-items: center; justify-content: center; z-index: 3;
}
.wa-scroll-down.show { display: flex; }
.wa-scroll-down svg { width: 20px; height: 20px; fill: currentColor; }
.wa-scroll-down .wa-badge { position: absolute; top: -6px; right: -4px; }

/* ---------- banners, preview, composer ---------- */
.wa-banner { display: none; align-items: center; gap: 10px; padding: 8px 16px; font-size: 13px; background: var(--wa-warn-bg); color: var(--wa-warn-text); border-top: 1px solid var(--wa-border); }
.wa-banner.show { display: flex; }
.wa-banner svg { width: 18px; height: 18px; fill: #e9a100; flex: 0 0 auto; }
.wa-banner.error svg { fill: var(--wa-danger); }
.wa-banner .close { margin-left: auto; cursor: pointer; background: none; border: 0; color: inherit; font-size: 18px; line-height: 1; }
.wa-preview { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: var(--wa-panel); border-top: 1px solid var(--wa-border); }
.wa-preview.show { display: flex; }
.wa-preview .thumb { width: 64px; height: 64px; flex: 0 0 auto; border-radius: 6px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--wa-bg); }
.wa-preview .thumb img, .wa-preview .thumb video { width: 100%; height: 100%; object-fit: cover; }
.wa-preview .thumb .ext { font-size: 11px; font-weight: 700; color: #fff; background: #e25151; padding: 12px 6px 4px; border-radius: 4px; text-transform: uppercase; }
.wa-preview .pinfo { flex: 1 1 auto; min-width: 0; }
.wa-preview .pname { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wa-preview .pmeta { font-size: 12px; color: var(--wa-text-3); }
.wa-preview label { font-weight: normal; font-size: 12.5px; color: var(--wa-text-2); margin: 4px 0 0; display: inline-flex; gap: 6px; align-items: center; cursor: pointer; }
.wa-preview audio { width: 100%; max-width: 360px; height: 36px; }
.wa-composer { flex: 0 0 auto; min-height: 62px; display: flex; align-items: flex-end; gap: 4px; padding: 10px 16px; background: var(--wa-panel); position: relative; }
.wa-input-wrap { flex: 1 1 auto; min-width: 0; background: var(--wa-input); border-radius: 8px; padding: 9px 12px; margin: 0 4px; display: flex; }
.wa-input-wrap textarea {
    flex: 1 1 auto; border: 0; outline: 0; resize: none; background: transparent; color: var(--wa-text); font-size: 15px; line-height: 20px;
    max-height: 120px; min-height: 20px; height: 20px; padding: 0; margin: 0; overflow-y: auto; box-shadow: none; font-family: inherit;
}
.wa-input-wrap textarea::placeholder { color: var(--wa-text-3); }
.wa-send { background: var(--wa-accent) !important; color: #fff !important; width: 42px; height: 42px; }
.wa-send:hover { background: var(--wa-accent-dark) !important; }
.wa-send svg { width: 20px; height: 20px; }
.wa-composer .wa-icon-btn { margin-bottom: 1px; }
.wa-recorder { display: none; flex: 1 1 auto; align-items: center; justify-content: flex-end; gap: 14px; padding: 0 8px; min-height: 42px; }
.wa-app.recording .wa-recorder { display: flex; }
.wa-app.recording .wa-input-wrap, .wa-app.recording #wa-emoji-btn, .wa-app.recording #wa-attach-btn { display: none; }
.wa-recorder .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--wa-danger); animation: waPulse 1s infinite; }
.wa-recorder .rtime { font-size: 18px; color: var(--wa-text); min-width: 50px; font-variant-numeric: tabular-nums; }
.wa-recorder .bars { display: flex; align-items: center; gap: 2px; height: 26px; }
.wa-recorder .bars i { display: block; width: 3px; border-radius: 2px; background: var(--wa-text-3); height: 4px; transition: height .08s; }
@keyframes waPulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
.wa-dropzone { position: absolute; inset: 0; z-index: 20; display: none; align-items: center; justify-content: center; background: rgba(0,168,132,.12); border: 3px dashed var(--wa-accent); color: var(--wa-accent-dark); font-size: 20px; font-weight: 600; pointer-events: none; }
.wa-main.dragging .wa-dropzone { display: flex; }

/* ---------- emoji panel ---------- */
.wa-emoji-panel { display: none; position: absolute; left: 12px; bottom: 66px; z-index: 30; width: 360px; max-width: calc(100% - 24px); background: var(--wa-bg); border-radius: 12px; box-shadow: var(--wa-panel-shadow); overflow: hidden; }
.wa-emoji-panel.open { display: block; }
.wa-emoji-panel .etabs { display: flex; border-bottom: 1px solid var(--wa-border); }
.wa-emoji-panel .etabs button { flex: 1 1 0; border: 0; background: none; padding: 8px 0; font-size: 19px; cursor: pointer; border-bottom: 3px solid transparent; opacity: .55; }
.wa-emoji-panel .etabs button.active { border-bottom-color: var(--wa-accent); opacity: 1; }
.wa-emoji-panel .esearch { padding: 8px; }
.wa-emoji-panel .esearch input { width: 100%; border: 0; outline: 0; border-radius: 8px; padding: 7px 12px; background: var(--wa-panel); color: var(--wa-text); }
.wa-emoji-panel .egrid { display: grid; grid-template-columns: repeat(9, 1fr); padding: 4px 8px 8px; height: 230px; overflow-y: auto; align-content: start; }
.wa-emoji-panel .egrid button { border: 0; background: none; font-size: 24px; line-height: 1; padding: 5px 0; border-radius: 6px; cursor: pointer; font-family: "Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif; }
.wa-emoji-panel .egrid button:hover { background: var(--wa-panel); }
.wa-emoji-panel .eempty { grid-column: 1 / -1; text-align: center; color: var(--wa-text-3); padding: 30px 0; font-size: 13px; }

/* ---------- menu / lightbox / toast ---------- */
.wa-menu { display: none; position: absolute; top: 52px; right: 12px; z-index: 40; min-width: 220px; padding: 9px 0; background: var(--wa-bg); border-radius: 3px; box-shadow: var(--wa-panel-shadow); }
.wa-menu.open { display: block; }
.wa-menu a, .wa-menu button { display: block; width: 100%; text-align: left; padding: 10px 24px; border: 0; background: none; color: var(--wa-text) !important; font-size: 14.5px; cursor: pointer; text-decoration: none !important; }
.wa-menu a:hover, .wa-menu button:hover { background: var(--wa-hover); }
.wa-lightbox { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(11,20,26,.94); flex-direction: column; }
.wa-lightbox.open { display: flex; }
.wa-lightbox .lb-head { display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #e9edef; }
.wa-lightbox .lb-head .lb-title { flex: 1 1 auto; font-size: 15px; }
.wa-lightbox .lb-head .wa-icon-btn { color: #aebac1; }
.wa-lightbox .lb-body { flex: 1 1 auto; display: flex; align-items: center; justify-content: center; padding: 10px 40px 40px; min-height: 0; }
.wa-lightbox img { max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 10px 40px rgba(0,0,0,.5); }
.wa-toast { position: absolute; left: 50%; bottom: 80px; transform: translateX(-50%); z-index: 50; background: #323739; color: #fff; padding: 10px 18px; border-radius: 8px; font-size: 13.5px; display: none; box-shadow: var(--wa-panel-shadow); }
.wa-toast.show { display: block; }

/* ---------- responsive ---------- */
@media (max-width: 900px) {
    .wa-app { height: calc(100vh - 150px); }
    .wa-side { flex: 1 1 100%; max-width: none; min-width: 0; }
    .wa-main { display: none; }
    .wa-app.has-chat.mobile-thread .wa-side { display: none; }
    .wa-app.has-chat.mobile-thread .wa-main { display: flex; }
    .wa-back { display: inline-flex; }
    .wa-bubble { max-width: 85%; }
    .wa-thread { padding: 10px 12px; }
    .wa-window-chip { display: none; }
}
</style>
{/literal}

<div class="wa-app" id="wa-app" data-theme="light">
    <!-- ================= Sidebar ================= -->
    <aside class="wa-side">
        <header class="wa-head">
            <span class="wa-avatar" id="wa-me-avatar" style="background:#00a884;"></span>
            <div class="wa-title">
                <strong id="wa-me-name"></strong>
                <small><span class="wa-live" id="wa-live"></span><span id="wa-live-text">Live</span></small>
            </div>
            <button type="button" class="wa-icon-btn" id="wa-notify-btn" title="Desktop notifications"></button>
            <button type="button" class="wa-icon-btn" id="wa-theme-btn" title="Light / dark theme"></button>
            <button type="button" class="wa-icon-btn" id="wa-full-btn" title="Full screen"></button>
            <button type="button" class="wa-icon-btn" id="wa-menu-btn" title="Menu"></button>
            <div class="wa-menu" id="wa-menu">
                <button type="button" data-act="mark-all">Mark all as read</button>
                <button type="button" data-act="sound">Sound: on</button>
                <a href="{$lkn_hn_base_endpoint|escape}&amp;page=notification-conversations">Conversations table &amp; analytics</a>
                <a href="{$lkn_hn_base_endpoint|escape}&amp;page=notification-reports">Notification reports</a>
            </div>
        </header>
        <div class="wa-search">
            <label>
                <span id="wa-search-icon"></span>
                <input type="text" id="wa-search" placeholder="Search or start a new chat (phone number)" autocomplete="off">
            </label>
        </div>
        <div class="wa-filters" id="wa-filters">
            <button type="button" data-f="all" class="active">All</button>
            <button type="button" data-f="unread">Unread<span class="count" id="wa-unread-count"></span></button>
            <button type="button" data-f="clients">Clients</button>
            <button type="button" data-f="unknown">Unknown</button>
        </div>
        <div class="wa-list" id="wa-list"></div>
    </aside>

    <!-- ================= Main ================= -->
    <section class="wa-main" id="wa-main">
        <div class="wa-main-empty">
            <span id="wa-empty-icon"></span>
            <h2>DCTLAB WhatsApp</h2>
            <p>Send and receive WhatsApp messages with your clients in real time.<br>Select a conversation, or type a phone number in the search box to start one.</p>
        </div>

        <div class="wa-chat" id="wa-chat">
            <header class="wa-head wa-chat-head">
                <button type="button" class="wa-icon-btn wa-back" id="wa-back" title="Back"></button>
                <span class="wa-avatar" id="wa-contact-avatar"></span>
                <div class="wa-title">
                    <strong id="wa-contact-name"></strong>
                    <small id="wa-contact-sub"></small>
                </div>
                <span class="wa-window-chip" id="wa-window-chip"></span>
                <a class="wa-icon-btn" id="wa-client-link" href="#" target="_blank" title="Open WHMCS client profile" style="display:none;"></a>
                <button type="button" class="wa-icon-btn" id="wa-chat-search-btn" title="Search messages"></button>
            </header>
            <div class="wa-chat-search" id="wa-chat-search">
                <input type="text" id="wa-chat-search-input" placeholder="Search messages in this chat..." autocomplete="off">
            </div>

            <div class="wa-thread-wrap">
                <div class="wa-thread" id="wa-thread"><div class="wa-thread-inner" id="wa-thread-inner"></div></div>
                <button type="button" class="wa-scroll-down" id="wa-scroll-down" title="Scroll to latest"></button>
            </div>

            <div class="wa-banner" id="wa-window-banner">
                <span class="wi"></span>
                <span id="wa-window-banner-text"></span>
            </div>
            <div class="wa-banner error" id="wa-error">
                <span class="wi"></span>
                <span id="wa-error-text"></span>
                <button type="button" class="close" id="wa-error-close">&times;</button>
            </div>

            <div class="wa-preview" id="wa-preview">
                <div class="thumb" id="wa-preview-thumb"></div>
                <div class="pinfo">
                    <div class="pname" id="wa-preview-name"></div>
                    <div class="pmeta" id="wa-preview-meta"></div>
                    <label id="wa-preview-doc-wrap"><input type="checkbox" id="wa-preview-as-doc"> Send as document (original quality)</label>
                </div>
                <button type="button" class="wa-icon-btn" id="wa-preview-remove" title="Remove"></button>
            </div>

            <footer class="wa-composer" id="wa-composer">
                <div class="wa-emoji-panel" id="wa-emoji-panel"></div>
                <button type="button" class="wa-icon-btn" id="wa-emoji-btn" title="Emoji"></button>
                <button type="button" class="wa-icon-btn" id="wa-attach-btn" title="Attach photo, video, audio or document"></button>
                <input type="file" id="wa-file" style="display:none;">
                <div class="wa-input-wrap">
                    <textarea id="wa-input" rows="1" placeholder="Type a message"></textarea>
                </div>
                <div class="wa-recorder" id="wa-recorder">
                    <button type="button" class="wa-icon-btn" id="wa-rec-cancel" title="Delete recording"></button>
                    <span class="dot"></span>
                    <span class="rtime" id="wa-rec-time">0:00</span>
                    <span class="bars" id="wa-rec-bars"></span>
                    <button type="button" class="wa-icon-btn" id="wa-rec-stop" title="Stop and listen"></button>
                </div>
                <button type="button" class="wa-icon-btn wa-send" id="wa-send-btn" title="Record voice message"></button>
            </footer>
            <div class="wa-toast" id="wa-toast"></div>
        </div>
        <div class="wa-dropzone">Drop file to send</div>
    </section>

    <div class="wa-lightbox" id="wa-lightbox">
        <div class="lb-head">
            <span class="lb-title" id="wa-lb-title"></span>
            <a class="wa-icon-btn" id="wa-lb-download" href="#" title="Download"></a>
            <button type="button" class="wa-icon-btn" id="wa-lb-close" title="Close"></button>
        </div>
        <div class="lb-body"><img id="wa-lb-img" alt=""></div>
    </div>
</div>

<script type="application/json" id="wa-initial-thread">{$page_params.thread_json}</script>
<script type="application/json" id="wa-initial-conversations">{$page_params.conversations_json}</script>
<script type="application/json" id="wa-initial-contact">{$page_params.contact_json}</script>
<script>
    window.DCT_CHAT_CONFIG = {
        baseEndpoint: '{$lkn_hn_base_endpoint|escape:"javascript"}',
        apiBaseUrl: '{$lkn_hn_api_base_url|escape:"javascript"}',
        token: '{$page_params.chat_token|escape:"javascript"}',
        lameJsUrl: '{$page_params.lame_js_url|escape:"javascript"}',
        uploadMaxBytes: {$page_params.upload_max_bytes|default:0},
        selectedPhone: '{$page_params.selected_phone|default:""|escape:"javascript"}',
        adminName: '{$page_params.admin_name|default:"Admin"|escape:"javascript"}',
        serverTime: '{$page_params.server_time|escape:"javascript"}'
    };
</script>
<script>
{literal}
(function () {
    'use strict';

    var cfg = window.DCT_CHAT_CONFIG || {};
    var app = document.getElementById('wa-app');
    if (!app) { return; }

    /* ---------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------- */
    var $ = function (id) { return document.getElementById(id); };
    var ICONS = {
        search: 'M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z',
        more: 'M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z',
        bell: 'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.360 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z',
        bellOff: 'M20 18.69L7.84 6.14 5.27 3.49 4 4.76l2.8 2.8v.01c-.52.99-.8 2.16-.8 3.42v5l-2 2v1h13.73l2 2L21 19.72l-1-1.03zM12 22c1.11 0 2-.89 2-2h-4c0 1.11.89 2 2 2zm6-7.32V11c0-3.08-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68c-.15.03-.29.08-.42.12-.1.03-.2.07-.3.11h-.01c-.01 0-.01 0-.02.01-.23.09-.46.2-.68.31 0 0-.01 0-.01.01L18 14.68z',
        moon: 'M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z',
        sun: 'M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 0 0 0-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z',
        full: 'M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z',
        fullExit: 'M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z',
        back: 'M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z',
        smile: 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z',
        clip: 'M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z',
        mic: 'M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z',
        send: 'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z',
        del: 'M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z',
        stop: 'M6 6h12v12H6z',
        close: 'M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z',
        download: 'M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z',
        down: 'M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z',
        person: 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
        warn: 'M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z',
        chat: 'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z',
        headset: 'M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h3c1.66 0 3-1.34 3-3v-7c0-4.97-4.03-9-9-9z',
        hidden: 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.804 11.804 0 0 0 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.730 22 21 20.73 3.27 3 2 4.27z',
        video: 'M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z',
        photo: 'M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z',
        doc: 'M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z'
    };
    function svg(name, style) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"' + (style ? ' style="' + style + '"' : '') + '><path d="' + ICONS[name] + '"/></svg>';
    }
    var TICK_ONE = '<svg viewBox="0 0 12 11"><path d="M11.1.6 4.4 8.2 1.6 5.4.5 6.5l3.9 3.9L12.2 1.7z"/></svg>';
    var TICK_TWO = '<svg viewBox="0 0 17 11"><path d="M11.1.6 4.4 8.2 1.6 5.4.5 6.5l3.9 3.9L12.2 1.7zM16 .6 9.3 8.2l-.9-.9-1.1 1.2 2 2L17.1 1.7z"/></svg>';
    var TICK_CLOCK = '<svg viewBox="0 0 12 12"><path d="M6 .8a5.2 5.2 0 1 0 0 10.4A5.2 5.2 0 0 0 6 .8zm0 1.3a3.9 3.9 0 1 1 0 7.8 3.9 3.9 0 0 1 0-7.8zM5.4 3.3v3.2l2.5 1.5.6-1-2-1.2V3.3z"/></svg>';
    var TICK_FAIL = '<svg viewBox="0 0 12 12"><path d="M6 .8a5.2 5.2 0 1 0 0 10.4A5.2 5.2 0 0 0 6 .8zm.7 8.2H5.3V7.7h1.4zm0-2.3H5.3V3h1.4z"/></svg>';

    // WhatsApp-style delivery ticks: sent = 1 grey, delivered = 2 grey, read = 2 blue.
    function tickHtml(status) {
        switch (String(status || 'sent').toLowerCase()) {
            case 'pending': return '<span class="wa-tick pending" title="Sending">' + TICK_CLOCK + '</span>';
            case 'failed': return '<span class="wa-tick failed" title="Not delivered">' + TICK_FAIL + '</span>';
            case 'read': return '<span class="wa-tick read" title="Read">' + TICK_TWO + '</span>';
            case 'delivered': return '<span class="wa-tick" title="Delivered">' + TICK_TWO + '</span>';
            default: return '<span class="wa-tick" title="Sent">' + TICK_ONE + '</span>';
        }
    }

    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function store(key, val) {
        try {
            if (val === undefined) { return window.localStorage.getItem('dct_hn_' + key); }
            window.localStorage.setItem('dct_hn_' + key, val);
        } catch (e) { /* storage unavailable (private mode etc.) */ }
        return null;
    }
    function parseDate(s) {
        if (!s) { return null; }
        var d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }
    // Timestamps come from the WHMCS server clock; keep today/yesterday
    // labels right even if the admin's browser is in another timezone.
    var skewMs = 0;
    (function () { var st = parseDate(cfg.serverTime); if (st) { skewMs = st.getTime() - Date.now(); } })();
    function serverNow() { return new Date(Date.now() + skewMs); }
    function sameDay(a, b) { return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
    function yesterday() { var y = serverNow(); y.setDate(y.getDate() - 1); return y; }
    function fmtTime(d) { return d ? d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : ''; }
    function dayLabel(d) {
        var now = serverNow();
        if (sameDay(d, now)) { return 'Today'; }
        if (sameDay(d, yesterday())) { return 'Yesterday'; }
        if ((now - d) < 6 * 86400000) { return d.toLocaleDateString([], { weekday: 'long' }); }
        return d.toLocaleDateString([], { day: 'numeric', month: 'long', year: 'numeric' });
    }
    function listTime(s) {
        var d = parseDate(s);
        if (!d) { return ''; }
        var now = serverNow();
        if (sameDay(d, now)) { return fmtTime(d); }
        if (sameDay(d, yesterday())) { return 'Yesterday'; }
        if ((now - d) < 6 * 86400000) { return d.toLocaleDateString([], { weekday: 'long' }); }
        return d.toLocaleDateString();
    }
    function relTime(s) {
        var d = parseDate(s);
        if (!d) { return ''; }
        if (sameDay(d, serverNow())) { return 'today at ' + fmtTime(d); }
        if (sameDay(d, yesterday())) { return 'yesterday at ' + fmtTime(d); }
        return d.toLocaleDateString() + ' at ' + fmtTime(d);
    }
    function fmtBytes(b) {
        if (!b && b !== 0) { return ''; }
        if (b < 1024) { return b + ' B'; }
        if (b < 1048576) { return Math.round(b / 1024) + ' kB'; }
        return (b / 1048576).toFixed(1) + ' MB';
    }
    function nowStamp() {
        var d = serverNow();
        var p = function (n) { return (n < 10 ? '0' : '') + n; };
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    }
    var AVATAR_COLORS = ['#00a884', '#25a2d8', '#8e6ccf', '#e5735c', '#d1567d', '#e09a00', '#34a871', '#5f7fdc', '#c95f9f', '#4aa3a2'];
    function avatarParts(name, key) {
        var h = 0;
        key = String(key || name || '');
        for (var i = 0; i < key.length; i++) { h = (h * 31 + key.charCodeAt(i)) >>> 0; }
        var initials = '';
        if (name) {
            var parts = String(name).trim().split(/\s+/);
            initials = (parts[0] || '').charAt(0) + (parts.length > 1 ? parts[parts.length - 1].charAt(0) : '');
        }
        return { color: AVATAR_COLORS[h % AVATAR_COLORS.length], inner: initials ? esc(initials) : svg('person') };
    }
    function avatarHtml(name, key) {
        var a = avatarParts(name, key);
        return '<span class="wa-avatar lg" style="background:' + a.color + '">' + a.inner + '</span>';
    }
    function setAvatar(el, name, key) {
        var a = avatarParts(name, key);
        el.style.background = a.color;
        el.innerHTML = a.inner;
    }
    function displayName(c) { return c.client_name || ('+' + c.phone_number); }
    function mediaUrl(id, dl) { return cfg.apiBaseUrl + '?endpoint=chat/media&id=' + encodeURIComponent(id) + (dl ? '&download=1' : ''); }
    function extOf(name, mime) {
        var m = /\.([a-z0-9]{1,5})$/i.exec(name || '');
        if (m) { return m[1].toLowerCase(); }
        return (String(mime || '').split('/')[1] || 'file').slice(0, 4);
    }
    function previewHtml(c) {
        var p = c.last_message_preview || '';
        var icon = '';
        var ic = function (n) { return '<span class="wa-tick" style="width:16px;height:16px;">' + svg(n, 'width:16px;height:16px') + '</span>'; };
        if (/^\[Image\]/.test(p)) { icon = ic('photo'); p = p.replace(/^\[Image\]\s*/, '') || 'Photo'; }
        else if (/^\[(Voice message|Audio)\]/.test(p)) { icon = ic('mic'); p = p.replace(/^\[(Voice message|Audio)\]\s*/, '') || 'Voice message'; }
        else if (/^\[Video\]/.test(p)) { icon = ic('video'); p = p.replace(/^\[Video\]\s*/, '') || 'Video'; }
        else if (/^\[Document/.test(p)) { icon = ic('doc'); p = p.replace(/^\[Document:?\s*/, '').replace(/\]$/, '') || 'Document'; }
        return icon + '<span style="overflow:hidden;text-overflow:ellipsis;">' + esc(p) + '</span>';
    }

    /* ---------------------------------------------------------------
     * State
     * ------------------------------------------------------------- */
    var state = {
        phone: null, contact: null, conversations: [], filter: 'all', query: '',
        lastTs: null, rendered: {}, lastRow: null, seq: 0,
        unreadSnapshot: null, newBelow: 0, pollTimer: null, polling: false,
        busy: false, pendingFile: null, recorder: null, recorded: null, userScrolled: false
    };
    var baseTitle = document.title;
    var soundOn = store('chat_sound') !== '0';

    /* ---------------------------------------------------------------
     * Static icons
     * ------------------------------------------------------------- */
    var staticIcons = {
        'wa-search-icon': 'search', 'wa-empty-icon': 'chat', 'wa-menu-btn': 'more', 'wa-back': 'back',
        'wa-chat-search-btn': 'search', 'wa-client-link': 'person', 'wa-emoji-btn': 'smile', 'wa-attach-btn': 'clip',
        'wa-rec-cancel': 'del', 'wa-rec-stop': 'stop', 'wa-preview-remove': 'close', 'wa-scroll-down': 'down',
        'wa-lb-download': 'download', 'wa-lb-close': 'close'
    };
    Object.keys(staticIcons).forEach(function (id) { if ($(id)) { $(id).innerHTML = svg(staticIcons[id]); } });
    document.querySelectorAll('#wa-window-banner .wi, #wa-error .wi').forEach(function (el) { el.innerHTML = svg('warn'); });
    $('wa-me-name').textContent = cfg.adminName || 'Admin';
    setAvatar($('wa-me-avatar'), cfg.adminName || 'Admin', 'me');
    $('wa-me-avatar').style.background = '#00a884';
    $('wa-rec-bars').innerHTML = new Array(25).join('<i></i>');

    /* ---------------------------------------------------------------
     * Theme / full screen / menu / notifications
     * ------------------------------------------------------------- */
    function applyTheme(t) {
        app.setAttribute('data-theme', t);
        $('wa-theme-btn').innerHTML = svg(t === 'dark' ? 'sun' : 'moon');
        $('wa-theme-btn').title = t === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
    }
    var savedTheme = store('chat_theme') ||
        ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light');
    applyTheme(savedTheme);
    $('wa-theme-btn').addEventListener('click', function () {
        var t = app.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(t);
        store('chat_theme', t);
    });

    function applyFull(on) {
        app.classList.toggle('wa-fullscreen', on);
        document.body.style.overflow = on ? 'hidden' : '';
        $('wa-full-btn').innerHTML = svg(on ? 'fullExit' : 'full');
        $('wa-full-btn').title = on ? 'Exit full screen' : 'Full screen';
    }
    applyFull(store('chat_full') === '1');
    $('wa-full-btn').addEventListener('click', function () {
        var on = !app.classList.contains('wa-fullscreen');
        applyFull(on);
        store('chat_full', on ? '1' : '0');
    });

    var menu = $('wa-menu');
    function refreshMenu() { menu.querySelector('[data-act="sound"]').textContent = 'Notification sound: ' + (soundOn ? 'on' : 'off'); }
    refreshMenu();
    $('wa-menu-btn').addEventListener('click', function (e) { e.stopPropagation(); menu.classList.toggle('open'); });
    menu.addEventListener('click', function (e) {
        var act = e.target.getAttribute('data-act');
        if (act === 'sound') {
            soundOn = !soundOn;
            store('chat_sound', soundOn ? '1' : '0');
            refreshMenu();
            if (soundOn) { beep(); }
        } else if (act === 'mark-all') {
            markAllRead();
        }
        if (act) { menu.classList.remove('open'); }
    });
    document.addEventListener('click', function (e) { if (!menu.contains(e.target)) { menu.classList.remove('open'); } });

    function notifySupported() { return 'Notification' in window; }
    function notifyOn() { return notifySupported() && Notification.permission === 'granted' && store('chat_notify') !== '0'; }
    function refreshNotifyBtn() {
        var btn = $('wa-notify-btn');
        if (!notifySupported()) { btn.style.display = 'none'; return; }
        var on = notifyOn();
        btn.innerHTML = svg(on ? 'bell' : 'bellOff');
        btn.classList.toggle('on', on);
        btn.title = Notification.permission === 'denied'
            ? 'Desktop notifications are blocked in your browser settings'
            : (on ? 'Desktop notifications on (click to turn off)' : 'Turn on desktop notifications');
    }
    refreshNotifyBtn();
    $('wa-notify-btn').addEventListener('click', function () {
        if (!notifySupported()) { return; }
        if (Notification.permission === 'granted') {
            store('chat_notify', notifyOn() ? '0' : '1');
            refreshNotifyBtn();
            toast(notifyOn() ? 'Desktop notifications turned on' : 'Desktop notifications turned off');
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(function () {
                store('chat_notify', '1');
                refreshNotifyBtn();
                if (notifyOn()) { toast('Desktop notifications turned on'); }
            });
        } else {
            toast('Notifications are blocked - allow them for this site in your browser settings.');
        }
    });

    var audioCtx = null;
    function beep() {
        if (!soundOn) { return; }
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            var t = audioCtx.currentTime;
            [[880, 0], [1320, 0.12]].forEach(function (n) {
                var o = audioCtx.createOscillator(), g = audioCtx.createGain();
                o.type = 'sine';
                o.frequency.value = n[0];
                g.gain.setValueAtTime(0.0001, t + n[1]);
                g.gain.exponentialRampToValueAtTime(0.15, t + n[1] + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + n[1] + 0.25);
                o.connect(g);
                g.connect(audioCtx.destination);
                o.start(t + n[1]);
                o.stop(t + n[1] + 0.3);
            });
        } catch (e) { /* audio unavailable */ }
    }

    function notifyNew(conv, count) {
        beep();
        if (!notifyOn()) { return; }
        try {
            var n = new Notification(displayName(conv), {
                body: count > 1 ? count + ' new messages' : (conv.last_message_preview || 'New message'),
                tag: 'dct-wa-' + conv.phone_number,
                renotify: true
            });
            n.onclick = function () { window.focus(); openChat(conv.phone_number); n.close(); };
        } catch (e) { /* ignore */ }
    }

    var toastTimer = null;
    function toast(msg) {
        var t = $('wa-toast');
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { t.classList.remove('show'); }, 3200);
    }

    /* ---------------------------------------------------------------
     * Conversation list
     * ------------------------------------------------------------- */
    function renderList() {
        var list = $('wa-list');
        var q = state.query.trim().toLowerCase();
        var qDigits = q.replace(/\D/g, '');
        var html = '';
        var shown = 0;
        var unreadChats = 0;

        state.conversations.forEach(function (c) {
            if (c.unread_count > 0) { unreadChats++; }
            if (state.filter === 'unread' && !(c.unread_count > 0) && c.phone_number !== state.phone) { return; }
            if (state.filter === 'clients' && !c.client_id) { return; }
            if (state.filter === 'unknown' && c.client_id) { return; }
            if (q) {
                var hay = (displayName(c) + ' ' + c.phone_number + ' ' + (c.last_message_preview || '')).toLowerCase();
                if (hay.indexOf(q) === -1 && !(qDigits.length >= 3 && c.phone_number.indexOf(qDigits) !== -1)) { return; }
            }
            shown++;
            var unread = c.unread_count > 0;
            html += '<a class="wa-item' + (c.phone_number === state.phone ? ' active' : '') + (unread ? ' unread' : '') + '" href="#" data-phone="' + esc(c.phone_number) + '">' +
                avatarHtml(c.client_name, c.phone_number) +
                '<div class="wa-item-body">' +
                    '<div class="row1"><span class="name">' + esc(displayName(c)) + '</span><span class="time">' + esc(listTime(c.last_message_at)) + '</span></div>' +
                    '<div class="row2"><span class="preview">' +
                        (c.last_message_direction === 'outbound' ? tickHtml('sent') : '') + previewHtml(c) +
                    '</span>' +
                    (c.client_id ? '' : '<span class="wa-tag">Unknown</span>') +
                    (unread ? '<span class="wa-badge">' + (c.unread_count > 99 ? '99+' : c.unread_count) + '</span>' : '') +
                    '</div>' +
                '</div></a>';
        });

        // Typing a phone number that isn't in the list offers a new chat.
        if (qDigits.length >= 8 && !state.conversations.some(function (c) { return c.phone_number === qDigits; })) {
            html = '<a class="wa-item wa-newchat" href="#" data-phone="' + esc(qDigits) + '">' + avatarHtml('', qDigits) +
                '<div class="wa-item-body"><div class="row1"><span class="name">Start chat with +' + esc(qDigits) + '</span></div>' +
                '<div class="row2"><span class="preview">Free-form messages need the customer to have written in the last 24h</span></div></div></a>' + html;
            shown++;
        }

        if (!shown) {
            html = '<div class="wa-list-empty">' + (q ? 'No chats found' :
                (state.filter === 'unread' ? 'No unread chats' : 'No conversations yet. Messages exchanged through WhatsApp will appear here.')) + '</div>';
        }

        list.innerHTML = html;
        $('wa-unread-count').textContent = unreadChats ? ' ' + unreadChats : '';
    }

    $('wa-list').addEventListener('click', function (e) {
        var a = e.target.closest('.wa-item');
        if (!a) { return; }
        e.preventDefault();
        if (a.classList.contains('wa-newchat')) {
            $('wa-search').value = '';
            state.query = '';
        }
        openChat(a.getAttribute('data-phone'));
    });
    $('wa-search').addEventListener('input', function () { state.query = this.value; renderList(); });
    $('wa-search').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = $('wa-list').querySelector('.wa-item');
            if (first) { first.click(); }
        }
    });
    $('wa-filters').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-f]');
        if (!b) { return; }
        state.filter = b.getAttribute('data-f');
        this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('active', x === b); });
        renderList();
    });

    function setConversations(list) {
        var prev = state.unreadSnapshot;
        state.conversations = list || [];
        var snap = {};
        var total = 0;
        state.conversations.forEach(function (c) {
            var n = c.unread_count || 0;
            snap[c.phone_number] = n;
            total += n;
            // Notify about new inbound messages (never on the first load).
            if (prev && n > (prev[c.phone_number] || 0)) {
                if (!(c.phone_number === state.phone && !document.hidden)) { notifyNew(c, n - (prev[c.phone_number] || 0)); }
            }
        });
        state.unreadSnapshot = snap;
        document.title = (total > 0 ? '(' + total + ') ' : '') + baseTitle;
        renderList();
    }

    function markAllRead() {
        var phones = state.conversations.filter(function (c) { return c.unread_count > 0; }).map(function (c) { return c.phone_number; });
        if (!phones.length) { toast('No unread chats'); return; }
        Promise.all(phones.map(function (p) {
            return fetch(cfg.apiBaseUrl + '?endpoint=chat/poll&visible=1&since=2999-01-01&phone=' + encodeURIComponent(p), { credentials: 'same-origin' });
        })).then(function () { poll(); toast('All chats marked as read'); });
    }

    /* ---------------------------------------------------------------
     * Chat header / contact ("last seen" = last message from customer)
     * ------------------------------------------------------------- */
    function setContact(info) {
        state.contact = info;
        var phone = state.phone;
        var conv = state.conversations.filter(function (c) { return c.phone_number === phone; })[0] || {};
        var client = info && info.client;
        var name = client ? client.name : (conv.client_name || null);

        $('wa-contact-name').textContent = name || ('+' + phone);
        setAvatar($('wa-contact-avatar'), name, phone);

        var sub = [];
        if (name) { sub.push('+' + phone); }
        if (client && client.company) { sub.push(client.company); }
        if (info) {
            sub.push(info.last_inbound_at ? 'last message ' + relTime(info.last_inbound_at) : 'has not messaged you yet');
        }
        $('wa-contact-sub').textContent = sub.join(' · ');

        var link = $('wa-client-link');
        if (client) {
            link.href = 'clientssummary.php?userid=' + encodeURIComponent(client.id);
            link.title = 'Open WHMCS client profile #' + client.id;
            link.style.display = '';
        } else {
            link.style.display = 'none';
        }

        var chip = $('wa-window-chip');
        var banner = $('wa-window-banner');
        if (!info) { chip.textContent = ''; banner.classList.remove('show'); return; }
        if (info.window_open) {
            var left = (parseDate(info.window_expires_at) - serverNow()) / 3600000;
            chip.textContent = 'Reply window: ' + (left >= 1 ? Math.floor(left) + 'h' : Math.max(1, Math.round(left * 60)) + 'm') + ' left';
            chip.className = 'wa-window-chip';
            chip.title = 'Free-form messages can be sent until ' + relTime(info.window_expires_at);
            banner.classList.remove('show');
        } else {
            chip.textContent = '24h window closed';
            chip.className = 'wa-window-chip closed';
            chip.title = 'The customer has not written in the last 24 hours';
            $('wa-window-banner-text').textContent = info.last_inbound_at
                ? 'The 24-hour reply window is closed (customer last wrote ' + relTime(info.last_inbound_at) + '). WhatsApp only delivers approved template messages until they write again.'
                : 'This contact has not messaged you yet. WhatsApp only delivers approved template messages until they write first.';
            banner.classList.add('show');
        }
    }

    /* ---------------------------------------------------------------
     * Thread rendering
     * ------------------------------------------------------------- */
    var thread = $('wa-thread');
    var inner = $('wa-thread-inner');

    function mediaHtml(m) {
        var type = m.type || 'text';
        if (m.media_unavailable) {
            var label = { image: 'Photo', audio: 'Voice message', video: 'Video', document: 'Document', sticker: 'Sticker' }[type] || type;
            return '<div class="wa-unavailable">' + svg('hidden') + esc(label) + ' no longer available</div>';
        }
        var isTmp = String(m.id).indexOf('tmp') === 0;
        if (!(m.has_media && m.id && !isTmp) && !m.local_url) { return ''; }
        var src = m.local_url || mediaUrl(m.id, false);
        var dl = m.local_url || mediaUrl(m.id, true);
        switch (type) {
            case 'image':
                return '<div class="wa-media"><img loading="lazy" src="' + esc(src) + '" data-full="' + esc(src) + '" data-dl="' + esc(dl) + '" alt="Photo"></div>';
            case 'sticker':
                return '<div class="wa-media"><img class="sticker" loading="lazy" src="' + esc(src) + '" alt="Sticker"></div>';
            case 'video':
                return '<div class="wa-media"><video controls preload="metadata" src="' + esc(src) + '"></video></div>';
            case 'audio':
                return '<div class="wa-audio"><span class="wa-audio-icon">' + svg('headset') + '</span><audio controls preload="' + (isTmp ? 'auto' : 'none') + '" src="' + esc(src) + '"></audio></div>';
            default:
                var name = m.media_filename || ('document-' + m.id);
                var ext = extOf(name, m.media_mime);
                return '<a class="wa-doc" href="' + esc(dl) + '"' + (m.local_url ? ' download="' + esc(name) + '"' : '') + '>' +
                    '<span class="ext">' + esc(ext) + '</span>' +
                    '<span class="info"><span class="dname">' + esc(name) + '</span>' +
                    '<span class="dmeta">' + esc([ext.toUpperCase(), fmtBytes(m.media_size)].filter(Boolean).join(' · ')) + '</span></span>' +
                    '<span class="dl">' + svg('download') + '</span></a>';
        }
    }

    function bubbleInner(m) {
        var d = parseDate(m.sent_at);
        var type = m.type || 'text';
        var meta = '<span class="wa-meta">' + esc(fmtTime(d)) + (m.direction === 'outbound' ? tickHtml(m.status) : '') + '</span>';
        var media = mediaHtml(m);
        var body = m.body || (type === 'text' ? '—' : '');
        if (body) { return media + '<div class="wa-text">' + esc(body) + meta + '</div>'; }
        return media + meta;
    }

    function bubbleClasses(m) {
        var type = m.type || 'text';
        var cls = 'wa-bubble';
        if (!m.body && type !== 'text' && !m.media_unavailable) {
            cls += ' media-only';
            if (type === 'audio' || type === 'document') { cls += ' no-overlay'; }
        }
        if (type === 'sticker' && (m.has_media || m.local_url)) { cls += ' sticker-bubble'; }
        return cls;
    }

    function appendMessage(m, opts) {
        opts = opts || {};
        if (m.id && state.rendered[m.id]) { return false; }
        var d = parseDate(m.sent_at) || serverNow();
        var dayKey = d.toDateString();

        if (!state.lastRow || state.lastRow.day !== dayKey) {
            var sep = document.createElement('div');
            sep.className = 'wa-day';
            sep.innerHTML = '<span>' + esc(dayLabel(d)) + '</span>';
            inner.appendChild(sep);
        }

        var row = document.createElement('div');
        var first = !state.lastRow || state.lastRow.day !== dayKey || state.lastRow.direction !== m.direction;
        row.className = 'wa-row ' + m.direction + (first ? ' first' : '');
        row.innerHTML = '<div class="' + bubbleClasses(m) + '">' + bubbleInner(m) + '</div>';
        row._msg = m;
        inner.appendChild(row);

        if (m.id) { state.rendered[m.id] = row; }
        state.lastRow = { day: dayKey, direction: m.direction };
        if (!opts.noTs && m.sent_at && (!state.lastTs || m.sent_at > state.lastTs)) { state.lastTs = m.sent_at; }
        applyChatSearchTo(row);
        return true;
    }

    function updateStatuses(statuses) {
        if (!statuses) { return; }
        Object.keys(statuses).forEach(function (id) {
            var row = state.rendered[id];
            if (!row || !row._msg || row._msg.status === statuses[id]) { return; }
            row._msg.status = statuses[id];
            var t = row.querySelector('.wa-meta .wa-tick');
            if (t) { t.outerHTML = tickHtml(statuses[id]); }
        });
    }

    function atBottom() { return (thread.scrollHeight - thread.scrollTop - thread.clientHeight) < 80; }
    function updateScrollBtn() {
        var btn = $('wa-scroll-down');
        btn.classList.toggle('show', !atBottom());
        var badge = btn.querySelector('.wa-badge');
        if (state.newBelow > 0) {
            if (!badge) { badge = document.createElement('span'); badge.className = 'wa-badge'; btn.appendChild(badge); }
            badge.textContent = state.newBelow;
        } else if (badge) {
            badge.remove();
        }
    }
    function scrollBottom(smooth) {
        thread.scrollTo({ top: thread.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
        state.newBelow = 0;
        state.userScrolled = false;
        updateScrollBtn();
    }
    thread.addEventListener('scroll', function () {
        if (atBottom()) { state.newBelow = 0; state.userScrolled = false; }
        updateScrollBtn();
    });
    thread.addEventListener('wheel', function () { state.userScrolled = !atBottom(); }, { passive: true });
    $('wa-scroll-down').addEventListener('click', function () { scrollBottom(true); });
    // Keep pinned to the bottom while images/videos load.
    thread.addEventListener('load', function (e) {
        if ((e.target.tagName === 'IMG' || e.target.tagName === 'VIDEO') && !state.userScrolled) { thread.scrollTop = thread.scrollHeight; }
    }, true);
    thread.addEventListener('loadedmetadata', function () { if (!state.userScrolled) { thread.scrollTop = thread.scrollHeight; } }, true);

    /* Lightbox */
    inner.addEventListener('click', function (e) {
        var img = e.target.closest('.wa-media img:not(.sticker)');
        if (!img) { return; }
        var row = img.closest('.wa-row');
        var m = row && row._msg;
        $('wa-lb-img').src = img.getAttribute('data-full');
        $('wa-lb-download').href = img.getAttribute('data-dl');
        $('wa-lb-title').textContent = (m && m.direction === 'outbound' ? 'You' : $('wa-contact-name').textContent) +
            (m && parseDate(m.sent_at) ? ' · ' + dayLabel(parseDate(m.sent_at)) + ' ' + fmtTime(parseDate(m.sent_at)) : '');
        $('wa-lightbox').classList.add('open');
    });
    function closeLightbox() { $('wa-lightbox').classList.remove('open'); $('wa-lb-img').removeAttribute('src'); }
    $('wa-lb-close').addEventListener('click', closeLightbox);
    $('wa-lightbox').addEventListener('click', function (e) { if (e.target.classList.contains('lb-body')) { closeLightbox(); } });

    /* In-chat search */
    $('wa-chat-search-btn').addEventListener('click', function () {
        var box = $('wa-chat-search');
        box.classList.toggle('open');
        if (box.classList.contains('open')) { $('wa-chat-search-input').focus(); }
        else { $('wa-chat-search-input').value = ''; applyChatSearch(); }
    });
    $('wa-chat-search-input').addEventListener('input', applyChatSearch);
    function applyChatSearchTo(row) {
        var q = ($('wa-chat-search-input').value || '').trim().toLowerCase();
        var bubble = row.querySelector('.wa-bubble');
        var m = row._msg || {};
        var hit = !q || ((m.body || '') + ' ' + (m.media_filename || '')).toLowerCase().indexOf(q) !== -1;
        if (bubble) { bubble.classList.toggle('dim', !hit); }
        return hit;
    }
    function applyChatSearch() {
        var q = ($('wa-chat-search-input').value || '').trim();
        var firstHit = null;
        inner.querySelectorAll('.wa-row').forEach(function (row) {
            if (applyChatSearchTo(row) && q && !firstHit) { firstHit = row; }
        });
        if (firstHit) { firstHit.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
    }

    /* ---------------------------------------------------------------
     * Opening chats + polling (near real-time: 3s visible, 12s hidden)
     * ------------------------------------------------------------- */
    function resetThread() {
        inner.innerHTML = '';
        state.rendered = {};
        state.lastRow = null;
        state.lastTs = null;
        state.newBelow = 0;
        state.userScrolled = false;
        $('wa-chat-search-input').value = '';
        $('wa-chat-search').classList.remove('open');
    }

    function openChat(phone, initial) {
        phone = String(phone || '').replace(/\D/g, '');
        if (!phone) { return; }
        var changed = phone !== state.phone;
        state.phone = phone;
        app.classList.add('has-chat', 'mobile-thread');
        hideError();

        if (changed) {
            resetThread();
            clearAttachment();
            clearRecorded();
            cancelRecording();
            input.value = store('draft_' + phone) || '';
            autoGrow();
            updateSendBtn();
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('phone', phone);
                window.history.replaceState(null, '', url.toString());
            } catch (e) { /* ignore */ }
        }

        if (initial) {
            (initial.messages || []).forEach(function (m) { appendMessage(m); });
            setContact(initial.contact || null);
            scrollBottom(false);
            poll();
        } else if (changed) {
            setContact(null);
            $('wa-contact-name').textContent = '+' + phone;
            $('wa-contact-sub').textContent = 'loading...';
            poll(true);
        }
        renderList();
        setTimeout(function () { input.focus(); }, 50);
    }

    $('wa-back').addEventListener('click', function () { app.classList.remove('mobile-thread'); });

    function setLive(ok, text) {
        $('wa-live').classList.toggle('off', !ok);
        $('wa-live-text').textContent = text || (ok ? 'Live' : 'Reconnecting...');
    }

    function poll(full) {
        if (state.polling && !full) { return Promise.resolve(); }
        state.polling = true;
        var seq = ++state.seq;
        var phone = state.phone;
        var params = [];
        if (phone) {
            params.push('phone=' + encodeURIComponent(phone));
            if (!full && state.lastTs) { params.push('since=' + encodeURIComponent(state.lastTs)); }
            if (!document.hidden) { params.push('visible=1'); }
        }

        return fetch(cfg.apiBaseUrl + '?endpoint=chat/poll' + (params.length ? '&' + params.join('&') : ''), { credentials: 'same-origin' })
            .then(function (res) {
                if (res.status === 403) { throw new Error('auth'); }
                return res.json();
            })
            .then(function (data) {
                state.polling = false;
                setLive(true);
                if (seq !== state.seq) { return; } // a newer request (e.g. chat switch) superseded this one
                var st = parseDate(data.server_time);
                if (st) { skewMs = st.getTime() - Date.now(); }

                if (phone && phone === state.phone && data.messages) {
                    var wasBottom = atBottom();
                    var added = 0;
                    var gotOutbound = data.messages.some(function (m) { return m.direction === 'outbound' && !state.rendered[m.id]; });
                    // Replace optimistic "sending" bubbles once the real rows arrive.
                    if (gotOutbound) {
                        inner.querySelectorAll('.wa-row.tmp').forEach(function (r) {
                            if (r._msg.status !== 'failed') { delete state.rendered[r._msg.id]; r.remove(); }
                        });
                        rebuildLastRow();
                    }
                    data.messages.forEach(function (m) { if (appendMessage(m)) { added++; } });
                    updateStatuses(data.statuses);
                    if (data.contact) { setContact(data.contact); }
                    if (added) {
                        if (full || wasBottom || gotOutbound) { scrollBottom(!full); }
                        else { state.newBelow += added; updateScrollBtn(); }
                    }
                    if (full && !added && !inner.children.length) {
                        inner.innerHTML = '';
                    }
                }
                setConversations(data.conversations);
            })
            .catch(function (err) {
                state.polling = false;
                if (err && err.message === 'auth') { setLive(false, 'Session expired - reload the page'); }
                else { setLive(false); }
            });
    }

    function rebuildLastRow() {
        var rows = inner.querySelectorAll('.wa-row');
        var last = rows[rows.length - 1];
        if (last && last._msg) {
            var d = parseDate(last._msg.sent_at) || serverNow();
            state.lastRow = { day: d.toDateString(), direction: last._msg.direction };
        } else {
            state.lastRow = null;
        }
        // Remove a now-empty trailing day separator.
        var tail = inner.lastElementChild;
        if (tail && tail.classList.contains('wa-day')) { tail.remove(); rebuildLastRow(); }
    }

    function schedulePoll() {
        clearTimeout(state.pollTimer);
        state.pollTimer = setTimeout(function () {
            poll().then(schedulePoll, schedulePoll);
        }, document.hidden ? 12000 : 3000);
    }
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { poll(); }
        schedulePoll();
    });

    /* ---------------------------------------------------------------
     * Composer
     * ------------------------------------------------------------- */
    var input = $('wa-input');
    var sendBtn = $('wa-send-btn');

    function autoGrow() {
        input.style.height = '20px';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }
    function hasContent() { return !!(input.value.trim() || state.pendingFile || state.recorded); }
    function updateSendBtn() {
        var send = hasContent() || !!state.recorder;
        sendBtn.innerHTML = svg(send ? 'send' : 'mic');
        sendBtn.title = send ? 'Send' : 'Record voice message';
    }
    input.addEventListener('input', function () {
        autoGrow();
        updateSendBtn();
        if (state.phone) { store('draft_' + state.phone, input.value); }
    });
    // Enter sends, Shift+Enter adds a new line (like WhatsApp Web).
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            doSend();
        }
    });

    function showError(msg) { $('wa-error-text').textContent = msg; $('wa-error').classList.add('show'); }
    function hideError() { $('wa-error').classList.remove('show'); }
    $('wa-error-close').addEventListener('click', hideError);

    function setBusy(b) {
        state.busy = b;
        sendBtn.disabled = b;
        $('wa-attach-btn').disabled = b;
    }

    function addTempBubble(m) {
        m.id = 'tmp' + Date.now() + Math.random().toString(16).slice(2);
        m.direction = 'outbound';
        m.status = 'pending';
        m.sent_at = nowStamp();
        appendMessage(m, { noTs: true });
        var row = state.rendered[m.id];
        row.classList.add('tmp');
        scrollBottom(true);
        return row;
    }
    function failTemp(row, msg) {
        if (row && row._msg) {
            row._msg.status = 'failed';
            var t = row.querySelector('.wa-meta .wa-tick');
            if (t) { t.outerHTML = tickHtml('failed'); }
        }
        showError(msg);
    }

    function parseResponse(res) {
        return res.text().then(function (text) {
            try { return JSON.parse(text); }
            catch (e) { return { success: false, error: 'Unexpected server response (HTTP ' + res.status + ').' }; }
        });
    }
    function sendText(phone, text) {
        return fetch(cfg.apiBaseUrl + '?endpoint=chat/send&phone=' + encodeURIComponent(phone), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-DCT-Chat-Token': cfg.token },
            body: JSON.stringify({ message: text })
        }).then(parseResponse);
    }
    function sendFile(phone, file, caption, asDoc) {
        var fd = new FormData();
        fd.append('file', file, file.name || 'file');
        if (caption) { fd.append('caption', caption); }
        if (asDoc) { fd.append('as_document', '1'); }
        fd.append('dct_chat_token', cfg.token);
        return fetch(cfg.apiBaseUrl + '?endpoint=chat/send-media&phone=' + encodeURIComponent(phone), {
            method: 'POST', credentials: 'same-origin', headers: { 'X-DCT-Chat-Token': cfg.token }, body: fd
        }).then(parseResponse);
    }
    function typeForFile(f, asDoc) {
        var t = f.type || '';
        if (asDoc) { return 'document'; }
        if (/^image\/(jpeg|png)$/.test(t)) { return 'image'; }
        if (/^video\/(mp4|3gpp)$/.test(t)) { return 'video'; }
        if (/^audio\//.test(t)) { return 'audio'; }
        return 'document';
    }

    function clearInput() {
        input.value = '';
        if (state.phone) { store('draft_' + state.phone, ''); }
        autoGrow();
        updateSendBtn();
    }

    function doSend() {
        if (state.busy || !state.phone) { return; }
        if (state.recorder) { stopRecording(true); return; }
        hideError();
        var text = input.value.trim();

        if (state.recorded) {
            var rec = state.recorded;
            clearRecorded();
            sendMediaFile(rec.file, '', false, text);
            clearInput();
            return;
        }

        if (state.pendingFile) {
            var f = state.pendingFile;
            var asDoc = $('wa-preview-as-doc').checked;
            var isAudio = /^audio\//.test(f.type || '') && !asDoc;
            clearAttachment();
            clearInput();
            // Audio can't carry a caption on WhatsApp - typed text follows as its own message.
            sendMediaFile(f, isAudio ? '' : text, asDoc, isAudio ? text : '');
            return;
        }

        if (!text) { startRecording(); return; }

        clearInput();
        var phone = state.phone;
        var row = addTempBubble({ type: 'text', body: text });
        setBusy(true);
        sendText(phone, text)
            .then(function (data) {
                setBusy(false);
                if (!data.success) { failTemp(row, data.error || 'Failed to send message.'); return; }
                poll();
            })
            .catch(function () { setBusy(false); failTemp(row, 'Network error - message not sent.'); });
    }

    function sendMediaFile(file, caption, asDoc, followText) {
        var phone = state.phone;
        var row = addTempBubble({
            type: typeForFile(file, asDoc), body: caption || null, has_media: true, local_url: URL.createObjectURL(file),
            media_filename: file.name, media_size: file.size, media_mime: file.type
        });
        var followRow = followText ? addTempBubble({ type: 'text', body: followText }) : null;
        setBusy(true);
        sendFile(phone, file, caption, asDoc)
            .then(function (data) {
                if (!data.success) { return data; }
                if (followText) {
                    return sendText(phone, followText).then(function (d2) {
                        if (!d2.success) { failTemp(followRow, d2.error || 'Failed to send message.'); }
                        return { success: true };
                    });
                }
                return data;
            })
            .then(function (data) {
                setBusy(false);
                if (!data.success) {
                    failTemp(row, data.error || 'Failed to send file.');
                    if (followRow) { failTemp(followRow, data.error || 'Not sent.'); }
                    return;
                }
                poll();
            })
            .catch(function () { setBusy(false); failTemp(row, 'Network error - file not sent.'); });
    }

    sendBtn.addEventListener('click', doSend);

    /* Attachments: button, paste, drag & drop */
    function clearAttachment() {
        state.pendingFile = null;
        $('wa-file').value = '';
        if (!state.recorded) {
            $('wa-preview').classList.remove('show');
            $('wa-preview-thumb').innerHTML = '';
        }
        $('wa-preview-as-doc').checked = false;
        input.placeholder = 'Type a message';
        updateSendBtn();
    }
    function setAttachment(file) {
        hideError();
        if (cfg.uploadMaxBytes && file.size > cfg.uploadMaxBytes) {
            showError('This file (' + fmtBytes(file.size) + ') is larger than the server upload limit (' + fmtBytes(cfg.uploadMaxBytes) + ').');
            return;
        }
        clearRecorded();
        state.pendingFile = file;
        var t = file.type || '';
        var thumb = $('wa-preview-thumb');
        if (/^image\//.test(t)) {
            thumb.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="">';
        } else if (/^video\//.test(t)) {
            thumb.innerHTML = '<video src="' + URL.createObjectURL(file) + '" muted></video>';
        } else {
            thumb.innerHTML = '<span class="ext">' + esc(extOf(file.name, t)) + '</span>';
        }
        $('wa-preview-name').textContent = file.name || 'file';
        $('wa-preview-meta').textContent = [fmtBytes(file.size), t].filter(Boolean).join(' · ');
        $('wa-preview-doc-wrap').style.display = /^(image|video)\//.test(t) ? 'inline-flex' : 'none';
        $('wa-preview').classList.add('show');
        input.placeholder = /^audio\//.test(t) ? 'Type a message (sent separately)' : 'Add a caption';
        updateSendBtn();
        input.focus();
    }
    $('wa-attach-btn').addEventListener('click', function () { $('wa-file').click(); });
    $('wa-file').addEventListener('change', function () { if (this.files && this.files[0]) { setAttachment(this.files[0]); } });
    $('wa-preview-remove').addEventListener('click', function () { clearRecorded(); clearAttachment(); });
    input.addEventListener('paste', function (e) {
        var files = e.clipboardData && e.clipboardData.files;
        if (files && files.length) {
            e.preventDefault();
            var f = files[0];
            if (!f.name || f.name === 'image.png') {
                f = new File([f], 'pasted-' + Date.now() + '.' + ((f.type.split('/')[1] || 'png').replace('jpeg', 'jpg')), { type: f.type });
            }
            setAttachment(f);
        }
    });
    var main = $('wa-main');
    var dragDepth = 0;
    main.addEventListener('dragenter', function (e) { if (!state.phone) { return; } e.preventDefault(); dragDepth++; main.classList.add('dragging'); });
    main.addEventListener('dragover', function (e) { if (state.phone) { e.preventDefault(); } });
    main.addEventListener('dragleave', function () { dragDepth = Math.max(0, dragDepth - 1); if (!dragDepth) { main.classList.remove('dragging'); } });
    main.addEventListener('drop', function (e) {
        e.preventDefault();
        dragDepth = 0;
        main.classList.remove('dragging');
        if (state.phone && e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) { setAttachment(e.dataTransfer.files[0]); }
    });

    /* ---------------------------------------------------------------
     * Voice notes: record -> (stop to listen first, or send directly)
     * Firefox records OGG/Opus natively (a real WhatsApp voice note);
     * other browsers are encoded to MP3 with the bundled lamejs, because
     * WhatsApp rejects the WebM they record by default.
     * ------------------------------------------------------------- */
    function loadLame() {
        if (window.lamejs) { return Promise.resolve(); }
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = cfg.lameJsUrl;
            s.onload = function () { if (window.lamejs) { resolve(); } else { reject(new Error('encoder')); } };
            s.onerror = function () { reject(new Error('encoder')); };
            document.head.appendChild(s);
        });
    }
    function fmtDur(sec) { var m = Math.floor(sec / 60), s = Math.floor(sec % 60); return m + ':' + (s < 10 ? '0' : '') + s; }

    function startRecording() {
        if (state.recorder || state.busy) { return; }
        hideError();
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Voice recording needs a secure (HTTPS) page and a browser with microphone support.');
            return;
        }
        var useOgg = !!(window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported('audio/ogg;codecs=opus'));
        (useOgg ? Promise.resolve() : loadLame())
            .then(function () {
                return navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
            })
            .then(function (stream) {
                var r = { stream: stream, started: Date.now(), kind: useOgg ? 'ogg' : 'mp3' };
                var AC = window.AudioContext || window.webkitAudioContext;
                r.ctx = new AC();
                r.source = r.ctx.createMediaStreamSource(stream);
                r.analyser = r.ctx.createAnalyser();
                r.analyser.fftSize = 64;
                r.source.connect(r.analyser);

                if (useOgg) {
                    r.chunks = [];
                    r.mr = new MediaRecorder(stream, { mimeType: 'audio/ogg;codecs=opus', audioBitsPerSecond: 64000 });
                    r.mr.ondataavailable = function (e) { if (e.data && e.data.size) { r.chunks.push(e.data); } };
                    r.mr.start(250);
                } else {
                    r.proc = r.ctx.createScriptProcessor(4096, 1, 1);
                    var rate = r.ctx.sampleRate;
                    r.rate = [32000, 44100, 48000].indexOf(rate) !== -1 ? rate : 44100;
                    r.encoder = new lamejs.Mp3Encoder(1, r.rate, 96);
                    r.mp3 = [];
                    r.proc.onaudioprocess = function (e) {
                        var data = e.inputBuffer.getChannelData(0);
                        var pcm = new Int16Array(data.length);
                        for (var i = 0; i < data.length; i++) {
                            var v = Math.max(-1, Math.min(1, data[i]));
                            pcm[i] = v < 0 ? v * 0x8000 : v * 0x7FFF;
                        }
                        var buf = r.encoder.encodeBuffer(pcm);
                        if (buf.length) { r.mp3.push(new Int8Array(buf)); }
                    };
                    r.source.connect(r.proc);
                    r.proc.connect(r.ctx.destination);
                }

                var barEls = $('wa-rec-bars').children;
                var freq = new Uint8Array(r.analyser.frequencyBinCount);
                r.timer = setInterval(function () {
                    var sec = (Date.now() - r.started) / 1000;
                    $('wa-rec-time').textContent = fmtDur(sec);
                    r.analyser.getByteFrequencyData(freq);
                    var level = 0;
                    for (var i = 0; i < freq.length; i++) { level += freq[i]; }
                    level = level / freq.length / 255;
                    for (var j = barEls.length - 1; j > 0; j--) { barEls[j].style.height = barEls[j - 1].style.height || '4px'; }
                    if (barEls[0]) { barEls[0].style.height = Math.max(4, Math.min(26, level * 70)) + 'px'; }
                    if (sec >= 900) { stopRecording(true); } // 15-minute cap
                }, 100);

                state.recorder = r;
                app.classList.add('recording');
                $('wa-rec-time').textContent = '0:00';
                updateSendBtn();
            })
            .catch(function (err) {
                if (window.console) { console.error(err); }
                showError(err && err.message === 'encoder'
                    ? 'Could not load the MP3 encoder (assets/js/vendor/lame.min.js).'
                    : 'Microphone access was denied or no microphone is available.');
            });
    }

    // send=true: send right away; send=false: stop and show a playback preview.
    function stopRecording(send) {
        var r = state.recorder;
        if (!r) { return; }
        state.recorder = null;
        clearInterval(r.timer);
        app.classList.remove('recording');

        var finish = function (blob, ext, mime) {
            r.stream.getTracks().forEach(function (t) { t.stop(); });
            try { r.ctx.close(); } catch (e) { /* ignore */ }
            updateSendBtn();
            if (!blob || blob.size < 800) { toast('Recording too short'); return; }
            var file = new File([blob], 'voice-' + Date.now() + '.' + ext, { type: mime });
            if (send) { sendMediaFile(file, '', false, ''); } else { showRecorded(file); }
        };

        if (r.kind === 'ogg') {
            r.mr.onstop = function () { finish(new Blob(r.chunks, { type: 'audio/ogg' }), 'ogg', 'audio/ogg'); };
            r.mr.stop();
        } else {
            try { r.source.disconnect(); r.proc.disconnect(); } catch (e) { /* ignore */ }
            var end = r.encoder.flush();
            if (end.length) { r.mp3.push(new Int8Array(end)); }
            finish(new Blob(r.mp3, { type: 'audio/mpeg' }), 'mp3', 'audio/mpeg');
        }
    }
    function cancelRecording() {
        var r = state.recorder;
        if (!r) { return; }
        state.recorder = null;
        clearInterval(r.timer);
        app.classList.remove('recording');
        try { if (r.mr && r.mr.state !== 'inactive') { r.mr.ondataavailable = null; r.mr.stop(); } } catch (e) { /* ignore */ }
        try { r.source.disconnect(); if (r.proc) { r.proc.disconnect(); } } catch (e) { /* ignore */ }
        r.stream.getTracks().forEach(function (t) { t.stop(); });
        try { r.ctx.close(); } catch (e) { /* ignore */ }
        updateSendBtn();
    }
    function showRecorded(file) {
        clearAttachment();
        state.recorded = { file: file, url: URL.createObjectURL(file) };
        $('wa-preview-thumb').innerHTML = '<span style="width:44px;height:44px;border-radius:50%;background:var(--wa-accent);display:flex;align-items:center;justify-content:center;">' + svg('mic', 'width:22px;height:22px;fill:#fff') + '</span>';
        $('wa-preview-name').innerHTML = '<audio controls src="' + state.recorded.url + '"></audio>';
        $('wa-preview-meta').textContent = 'Voice message · ' + fmtBytes(file.size) + ' · listen, then send - or remove to discard';
        $('wa-preview-doc-wrap').style.display = 'none';
        $('wa-preview').classList.add('show');
        updateSendBtn();
    }
    function clearRecorded() {
        if (!state.recorded) { return; }
        state.recorded = null;
        $('wa-preview').classList.remove('show');
        $('wa-preview-name').textContent = '';
        $('wa-preview-thumb').innerHTML = '';
        updateSendBtn();
    }
    $('wa-rec-cancel').addEventListener('click', cancelRecording);
    $('wa-rec-stop').addEventListener('click', function () { stopRecording(false); });

    /* ---------------------------------------------------------------
     * Emoji picker
     * ------------------------------------------------------------- */
    var closeEmoji = (function () {
        var btn = $('wa-emoji-btn');
        var panel = $('wa-emoji-panel');
        var CATS = [
            ['😀', 'Smileys', '😀 😃 😄 😁 😆 😅 🤣 😂 🙂 🙃 😉 😊 😇 🥰 😍 🤩 😘 😗 😚 😙 😋 😛 😜 🤪 😝 🤑 🤗 🤭 🤫 🤔 🤐 🤨 😐 😑 😶 😏 😒 🙄 😬 😌 😔 😪 🤤 😴 😷 🤒 🤕 🤢 🤮 🥵 🥶 🥴 😵 🤯 🤠 🥳 😎 🤓 🧐 😕 😟 🙁 😮 😯 😲 😳 🥺 😦 😧 😨 😰 😥 😢 😭 😱 😖 😣 😞 😓 😩 😫 🥱 😤 😡 😠 🤬'],
            ['👍', 'Gestures', '👍 👎 👌 🤌 ✌️ 🤞 🤟 🤘 🤙 👈 👉 👆 👇 ☝️ ✋ 🤚 🖐️ 🖖 👋 👏 🙌 👐 🤲 🤝 🙏 ✍️ 💪 🫡 🫶 🙋 🙆 🙅 🤷 🤦 💁'],
            ['❤️', 'Hearts', '❤️ 🧡 💛 💚 💙 💜 🖤 🤍 🤎 💔 ❣️ 💕 💞 💓 💗 💖 💘 💝 💟 ✨ ⭐ 🌟 💫 🔥 🎉 🎊 🎁 🎈 🏆 🥇 🎯 💯'],
            ['💼', 'Business', '💼 📧 📨 📩 📞 📱 💻 🖥️ ⌨️ 🖨️ 🌐 🔒 🔓 🔑 🛡️ ⚙️ 🛠️ 🔧 🧾 📄 📃 📑 📊 📈 📉 📅 📆 🗓️ ⏰ ⏳ ⌛ 💰 💵 💳 🏦 🧮 📦 🚚 🚀 💡 📌 📎 🔗 ✏️ 📝 🔔 📢'],
            ['✅', 'Symbols', '✅ ☑️ ✔️ ❌ ❎ ⚠️ 🚫 ⛔ ❗ ❓ ❕ ❔ ‼️ ⁉️ ℹ️ 🆗 🆕 🆓 🔴 🟠 🟡 🟢 🔵 🟣 ⚫ ⚪ ➡️ ⬅️ ⬆️ ⬇️ 🔄 🔁 ▶️ ⏸️ ⏹️ 🔝 🔜 💤 ♻️ 🕐 🌙 ☀️ ⛅ 🌧️']
        ];
        var KW = {
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
        var active = 0;
        function recent() { try { return JSON.parse(store('recent_emojis') || '[]'); } catch (e) { return []; } }
        function pushRecent(e) { var l = recent().filter(function (x) { return x !== e; }); l.unshift(e); store('recent_emojis', JSON.stringify(l.slice(0, 27))); }
        function cats() { var r = recent(); return r.length ? [['🕘', 'Recent', r.join(' ')]].concat(CATS) : CATS; }

        panel.innerHTML = '<div class="etabs" id="wa-etabs"></div><div class="esearch"><input type="text" id="wa-esearch" placeholder="Search emoji" autocomplete="off"></div><div class="egrid" id="wa-egrid"></div>';
        var tabs = $('wa-etabs'), grid = $('wa-egrid'), search = $('wa-esearch');

        function fill(list) {
            grid.innerHTML = list.length
                ? list.map(function (e) { return '<button type="button" data-e="' + e + '">' + e + '</button>'; }).join('')
                : '<div class="eempty">No emoji found</div>';
        }
        function render() {
            var c = cats();
            if (active >= c.length) { active = 0; }
            tabs.innerHTML = c.map(function (x, i) { return '<button type="button" data-c="' + i + '" title="' + x[1] + '" class="' + (i === active ? 'active' : '') + '">' + x[0] + '</button>'; }).join('');
            fill(c[active][2].split(' ').filter(Boolean));
        }
        function doSearch(q) {
            q = q.trim().toLowerCase();
            if (!q) { render(); return; }
            var seen = {}, out = [];
            CATS.forEach(function (c) {
                c[2].split(' ').forEach(function (e) {
                    if (e && !seen[e] && ((KW[e] || '').indexOf(q) !== -1 || c[1].toLowerCase().indexOf(q) === 0)) { seen[e] = 1; out.push(e); }
                });
            });
            fill(out);
        }
        function insert(t) {
            var s = input.selectionStart != null ? input.selectionStart : input.value.length;
            var e = input.selectionEnd != null ? input.selectionEnd : input.value.length;
            input.value = input.value.slice(0, s) + t + input.value.slice(e);
            input.focus();
            try { input.setSelectionRange(s + t.length, s + t.length); } catch (x) { /* ignore */ }
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        function open() { active = 0; search.value = ''; render(); panel.classList.add('open'); btn.classList.add('on'); }
        function close() { panel.classList.remove('open'); btn.classList.remove('on'); }

        btn.addEventListener('click', function (e) { e.stopPropagation(); if (panel.classList.contains('open')) { close(); } else { open(); } });
        tabs.addEventListener('click', function (e) { var t = e.target.closest('button[data-c]'); if (t) { active = +t.getAttribute('data-c'); search.value = ''; render(); } });
        grid.addEventListener('mousedown', function (e) {
            var t = e.target.closest('button[data-e]');
            if (!t) { return; }
            e.preventDefault();
            insert(t.getAttribute('data-e'));
            pushRecent(t.getAttribute('data-e'));
        });
        search.addEventListener('input', function () { doSearch(search.value); });
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var f = grid.querySelector('button[data-e]');
                if (f) { insert(f.getAttribute('data-e')); pushRecent(f.getAttribute('data-e')); }
            }
        });
        document.addEventListener('mousedown', function (e) {
            if (panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) { close(); }
        });
        sendBtn.addEventListener('click', close);
        return close;
    })();

    /* Global keys */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        if ($('wa-lightbox').classList.contains('open')) { closeLightbox(); return; }
        closeEmoji();
        if (state.recorder) { cancelRecording(); }
    });

    /* ---------------------------------------------------------------
     * Boot
     * ------------------------------------------------------------- */
    function readJson(id, fallback) {
        try {
            var el = $(id);
            var v = el ? JSON.parse(el.textContent || 'null') : null;
            return v == null ? fallback : v;
        } catch (e) {
            return fallback;
        }
    }
    setConversations(readJson('wa-initial-conversations', []));
    state.unreadSnapshot = null; // the first live poll sets the notification baseline
    updateSendBtn();

    var explicit = /[?&]phone=/.test(window.location.search);
    if (cfg.selectedPhone) {
        openChat(cfg.selectedPhone, { messages: readJson('wa-initial-thread', []), contact: readJson('wa-initial-contact', null) });
        if (!explicit) { app.classList.remove('mobile-thread'); }
    } else {
        poll();
    }
    schedulePoll();
})();
{/literal}
</script>
{/block}
