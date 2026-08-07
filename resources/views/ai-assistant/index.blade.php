@extends('web.layout.master')

@section('title', $pageTitle ?? 'PureMed AI Assistant')

@section('content')
<style>
    .pm-chat {
        --pm-blue: #123b76;
        --pm-blue-light: #1d63c6;
        --pm-line: #e5eaf2;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 140px);
        min-height: 540px;
        max-width: 880px;
        margin: 24px auto 32px;
        background: #fff;
        border: 1px solid var(--pm-line);
        border-radius: 20px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .pm-chat-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid var(--pm-line);
        background: #fff;
        flex: 0 0 auto;
    }

    .pm-avatar {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--pm-blue) 0%, var(--pm-blue-light) 100%);
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 700;
        font-size: 14px;
        flex: 0 0 auto;
    }

    .pm-title { font-weight: 700; color: #17345f; line-height: 1.2; }
    .pm-subtitle { font-size: 12px; color: #7c8ba1; }
    .pm-header-spacer { margin-left: auto; }

    .pm-restart {
        border: 1px solid var(--pm-line);
        background: #fff;
        color: #64748b;
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 12px;
        cursor: pointer;
    }
    .pm-restart:hover { background: #f6f9ff; color: var(--pm-blue); }

    .pm-speaker {
        border: 1px solid var(--pm-line);
        background: #fff;
        color: #94a3b8;
        border-radius: 999px;
        width: 34px; height: 34px;
        display: grid; place-items: center;
        cursor: pointer; font-size: 15px;
        transition: all 0.15s ease;
    }
    .pm-speaker:hover { background: #f6f9ff; }
    .pm-speaker.on { background: #eef4ff; border-color: #c7dbff; color: var(--pm-blue); }
    .pm-speaker.speaking { animation: pmSpeak 1.1s infinite; }
    @keyframes pmSpeak {
        0%, 100% { box-shadow: 0 0 0 0 rgba(29, 99, 198, 0.35); }
        50% { box-shadow: 0 0 0 6px rgba(29, 99, 198, 0); }
    }

    .pm-stream {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 22px 20px 8px;
        background: linear-gradient(180deg, #fbfdff 0%, #f7fafd 100%);
        scroll-behavior: smooth;
    }

    .pm-row { display: flex; margin-bottom: 12px; }
    .pm-row.user { justify-content: flex-end; }

    /* Assistant identity: avatar on every message, name only on the first of a
       consecutive group so a multi-message turn does not repeat itself. */
    .pm-row.assistant { align-items: flex-start; gap: 9px; }

    .pm-sprite { position: absolute; width: 0; height: 0; overflow: hidden; }

    .pm-msg-avatar {
        width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg, var(--pm-blue) 0%, var(--pm-blue-light) 100%);
        display: grid; place-items: center;
        flex: 0 0 auto; margin-top: 2px;
        box-shadow: 0 2px 6px rgba(18, 59, 118, 0.18);
        /* The mark's eyes and smile follow this colour through <use>. */
        color: var(--pm-blue);
        cursor: default;          /* presentational, not a button */
        user-select: none;
    }
    .pm-msg-avatar svg { width: 21px; height: 21px; display: block; }

    .pm-msg-body { display: flex; flex-direction: column; min-width: 0; }
    .pm-row.assistant .pm-msg-body { max-width: min(78%, 560px); }
    .pm-row.assistant .pm-bubble { max-width: 100%; }

    .pm-msg-name {
        font-size: 11px; font-weight: 600; letter-spacing: 0.02em;
        color: #8a99ad; margin: 0 0 4px 2px;
    }

    /* Keep the narration and chips in the same column as the bubbles. */
    .pm-status, .pm-cards, .pm-summary { margin-left: 41px; }

    .pm-bubble {
        max-width: min(78%, 560px);
        padding: 12px 16px;
        border-radius: 18px;
        font-size: 15px;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
        animation: pmIn 0.22s ease-out;
    }

    .pm-row.assistant .pm-bubble {
        background: #fff;
        color: #1f3355;
        border: 1px solid var(--pm-line);
        border-bottom-left-radius: 6px;
    }

    .pm-row.user .pm-bubble {
        background: linear-gradient(135deg, var(--pm-blue) 0%, var(--pm-blue-light) 100%);
        color: #fff;
        border-bottom-right-radius: 6px;
    }

    .pm-row.assistant .pm-bubble.error {
        background: #fff5f5;
        border-color: #fecdd3;
        color: #9f1239;
    }

    @keyframes pmIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: none; }
    }

    .pm-typing { display: inline-flex; gap: 5px; align-items: center; padding: 4px 2px; }
    .pm-typing i {
        width: 7px; height: 7px; border-radius: 50%;
        background: #94a3b8; display: block;
        animation: pmPulse 1.1s infinite ease-in-out;
    }
    .pm-typing i:nth-child(2) { animation-delay: 0.15s; }
    .pm-typing i:nth-child(3) { animation-delay: 0.3s; }
    @keyframes pmPulse {
        0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
        40% { transform: translateY(-4px); opacity: 1; }
    }

    /* Quick reply chips - small, inline, and gone once used */
    .pm-cards {
        display: flex; flex-wrap: wrap; gap: 7px;
        margin: 2px 0 14px; animation: pmIn 0.22s ease-out;
    }

    .pm-card {
        border: 1px solid #cfe0f7;
        background: #fff;
        border-radius: 999px;
        padding: 7px 14px;
        cursor: pointer;
        text-align: left;
        transition: background 0.14s ease, border-color 0.14s ease;
        font-size: 13.5px;
        line-height: 1.3;
        color: var(--pm-blue);
        max-width: 100%;
    }
    .pm-card:hover:not(:disabled) { border-color: var(--pm-blue-light); background: #eef5ff; }
    .pm-card:disabled { opacity: 0.45; cursor: default; }
    .pm-card b { font-weight: 600; }
    .pm-card span { color: #94a3b8; font-size: 12px; margin-left: 6px; }

    .pm-card.more { border-style: dashed; color: #64748b; }

    /* Thinking / narration line */
    .pm-status {
        display: flex; align-items: center; gap: 9px;
        color: #64748b; font-size: 13.5px;
        padding: 4px 2px 4px 0; margin-bottom: 10px;
        animation: pmIn 0.22s ease-out;
    }
    .pm-status .pm-spin {
        width: 13px; height: 13px; flex: 0 0 auto;
        border: 2px solid #cbd5e1; border-top-color: var(--pm-blue-light);
        border-radius: 50%; animation: pmSpin 0.7s linear infinite;
    }
    .pm-status.done { color: #94a3b8; }
    .pm-status.done .pm-spin {
        border: 2px solid #86efac; border-top-color: #86efac; animation: none;
    }
    @keyframes pmSpin { to { transform: rotate(360deg); } }

    /* Summary + booked cards */
    .pm-summary {
        border: 1px solid #d8e4f5;
        background: #fff;
        border-radius: 16px;
        padding: 16px 18px;
        margin-bottom: 12px;
        max-width: min(78%, 560px);
    }
    .pm-summary.booked { border-color: #bbf7d0; background: #f6fffa; }
    .pm-summary-row {
        display: flex; justify-content: space-between; gap: 16px;
        padding: 7px 0; border-bottom: 1px dashed #e6edf7; font-size: 14px;
    }
    .pm-summary-row:last-of-type { border-bottom: 0; }
    .pm-summary-label { color: #7c8ba1; }
    .pm-summary-value { color: #17345f; font-weight: 600; text-align: right; }
    .pm-summary-head { font-weight: 700; color: #15803d; margin-bottom: 8px; }

    /* Composer */
    .pm-composer {
        flex: 0 0 auto;
        border-top: 1px solid var(--pm-line);
        background: #fff;
        padding: 14px 16px;
    }
    .pm-composer-inner {
        display: flex; align-items: flex-end; gap: 10px;
        border: 1px solid #d8e3f0; border-radius: 18px;
        padding: 6px 6px 6px 14px; background: #fff;
        transition: border-color 0.15s ease;
    }
    .pm-composer-inner:focus-within { border-color: var(--pm-blue-light); }

    .pm-input {
        flex: 1 1 auto; border: 0; outline: none; resize: none;
        font-size: 15px; line-height: 1.5; padding: 9px 0;
        max-height: 120px; background: transparent; color: #1f3355;
        font-family: inherit;
    }
    .pm-input:disabled { background: transparent; color: #94a3b8; }

    .pm-icon-btn {
        width: 40px; height: 40px; border-radius: 50%; border: 0;
        display: grid; place-items: center; cursor: pointer;
        flex: 0 0 auto; font-size: 16px; transition: all 0.15s ease;
    }
    .pm-mic { background: #eef4ff; color: var(--pm-blue); }
    .pm-mic:hover:not(:disabled) { background: #dfeaff; }
    .pm-mic.armed { background: #dbeafe; color: var(--pm-blue); box-shadow: inset 0 0 0 2px #93c5fd; }
    .pm-mic.listening { background: #e11d48; color: #fff; animation: pmMic 1.3s infinite; }
    @keyframes pmMic {
        0%, 100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(225, 29, 72, 0); }
    }
    .pm-send { background: linear-gradient(135deg, var(--pm-blue) 0%, var(--pm-blue-light) 100%); color: #fff; }
    .pm-send:disabled, .pm-mic:disabled { opacity: 0.4; cursor: default; }

    .pm-hint { font-size: 11px; color: #94a3b8; text-align: center; margin-top: 8px; }

    @media (max-width: 767.98px) {
        .pm-chat { height: calc(100vh - 90px); margin: 12px; border-radius: 16px; }
        .pm-bubble, .pm-summary { max-width: 88%; }
        .pm-row.assistant .pm-msg-body { max-width: 84%; }
        .pm-msg-avatar { width: 28px; height: 28px; }
        .pm-msg-avatar svg { width: 18px; height: 18px; }
        .pm-status, .pm-cards, .pm-summary { margin-left: 37px; }
    }
</style>

<div class="pm-chat">
    {{--
        PureMed assistant mark, defined once and referenced by every assistant
        message with <use>. Inline so there is no extra request and no asset
        path to break; white shapes on the badge, features in currentColor so
        they follow the PureMed blue set on .pm-msg-avatar.
    --}}
    <svg class="pm-sprite" aria-hidden="true" focusable="false" width="0" height="0">
        <symbol id="pmBotAvatar" viewBox="0 0 24 24">
            <circle cx="12" cy="3.1" r="1.5" fill="#fff"/>
            <rect x="11.35" y="4.1" width="1.3" height="2.4" rx="0.65" fill="#fff"/>
            <rect x="2.1" y="10.3" width="1.9" height="3.8" rx="0.95" fill="#fff"/>
            <rect x="20" y="10.3" width="1.9" height="3.8" rx="0.95" fill="#fff"/>
            <rect x="4.4" y="6.3" width="15.2" height="12.6" rx="4.3" fill="#fff"/>
            <circle cx="9" cy="11.7" r="1.55" fill="currentColor"/>
            <circle cx="15" cy="11.7" r="1.55" fill="currentColor"/>
            <path d="M9.3 15.1c1.6 1.45 3.8 1.45 5.4 0" fill="none" stroke="currentColor"
                  stroke-width="1.5" stroke-linecap="round"/>
        </symbol>
    </svg>

    <div class="pm-chat-header">
        <div class="pm-avatar">PM</div>
        <div>
            <div class="pm-title">PureMed Assistant</div>
            <div class="pm-subtitle" id="pmStatus">Online</div>
        </div>
        <div class="pm-header-spacer"></div>
        <button type="button" class="pm-speaker" id="pmSpeaker" title="Read replies aloud" aria-label="Read replies aloud">&#128264;</button>
        <button type="button" class="pm-restart" id="pmRestart" title="Clear the chat and begin as a new patient">New patient</button>
    </div>

    <div class="pm-stream" id="pmStream"></div>

    <div class="pm-composer">
        <div class="pm-composer-inner">
            <textarea id="pmInput" class="pm-input" rows="1" placeholder="Type your reply..." autocomplete="off"></textarea>
            <button type="button" class="pm-icon-btn pm-mic" id="pmMic" title="Speak" aria-label="Speak">&#127908;</button>
            <button type="button" class="pm-icon-btn pm-send" id="pmSend" title="Send" aria-label="Send">&#10148;</button>
        </div>
        <div class="pm-hint" id="pmHint">Type or tap the microphone to speak.</div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var ENDPOINT = @json(route('ai-assistant.message'));
    var RESET_ENDPOINT = @json(route('ai-assistant.reset'));
    var CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var stream = document.getElementById('pmStream');
    var input = document.getElementById('pmInput');
    var sendBtn = document.getElementById('pmSend');
    var micBtn = document.getElementById('pmMic');
    var hint = document.getElementById('pmHint');
    var status = document.getElementById('pmStatus');
    var restartBtn = document.getElementById('pmRestart');
    var speakerBtn = document.getElementById('pmSpeaker');

    var busy = false;
    var currentCards = null;
    // Status lines to narrate on the NEXT send, supplied by the last response.
    var pendingStatus = null;
    // Set when the pending message came from the microphone rather than typing.
    var spokenSubmission = false;

    /* ---------------- rendering ---------------- */

    /** The PureMed assistant mark, referencing the sprite defined in the markup. */
    function assistantAvatar() {
        var avatar = document.createElement('div');
        avatar.className = 'pm-msg-avatar';
        avatar.setAttribute('title', 'PureMed AI Assistant');
        avatar.setAttribute('role', 'img');
        avatar.setAttribute('aria-label', 'PureMed AI Assistant');
        avatar.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
            + '<use href="#pmBotAvatar" xlink:href="#pmBotAvatar"></use></svg>';
        return avatar;
    }

    /** True when the previous entry is not already an assistant message. */
    function startsAssistantGroup() {
        var last = stream.lastElementChild;

        return !(last && last.classList.contains('pm-row') && last.classList.contains('assistant'));
    }

    /** Wrap assistant content with the avatar, and the name on the first of a group. */
    function assistantRow(content) {
        var row = document.createElement('div');
        row.className = 'pm-row assistant';

        var body = document.createElement('div');
        body.className = 'pm-msg-body';

        if (startsAssistantGroup()) {
            var name = document.createElement('div');
            name.className = 'pm-msg-name';
            name.textContent = 'PureMed Assistant';
            body.appendChild(name);
        }

        body.appendChild(content);
        row.appendChild(assistantAvatar());
        row.appendChild(body);

        return row;
    }

    function addBubble(role, text, kind) {
        var bubble = document.createElement('div');
        bubble.className = 'pm-bubble' + (kind === 'error' ? ' error' : '');
        bubble.textContent = text;

        var row;

        if (role === 'assistant') {
            row = assistantRow(bubble);
        } else {
            // User messages are unchanged: right aligned, no avatar, no label.
            row = document.createElement('div');
            row.className = 'pm-row ' + role;
            row.appendChild(bubble);
        }

        stream.appendChild(row);
        scroll();
        return row;
    }

    function showTyping() {
        var bubble = document.createElement('div');
        bubble.className = 'pm-bubble';
        bubble.innerHTML = '<span class="pm-typing"><i></i><i></i><i></i></span>';

        var row = assistantRow(bubble);
        stream.appendChild(row);
        scroll();
        return row;
    }

    function clearCards() {
        if (currentCards) {
            // The choice becomes the user's own bubble, so the chips have done
            // their job - leaving them greyed out looks like a dead form.
            currentCards.remove();
            currentCards = null;
        }
    }

    function renderOptions(options) {
        if (!options) { return; }

        if (options.type === 'booked') {
            renderSummary(options.summary, true);
            return;
        }

        if (options.type === 'confirm') {
            renderSummary(options.summary, false);
        }

        if (!options.items || !options.items.length) { return; }

        var wrap = document.createElement('div');
        wrap.className = 'pm-cards' + (options.type === 'slot_time' ? ' times' : '');

        options.items.forEach(function (item) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'pm-card' + (item.value === '__more__' ? ' more' : '');

            var title = document.createElement('b');
            title.textContent = item.title;
            chip.appendChild(title);

            if (item.subtitle) {
                var sub = document.createElement('span');
                sub.textContent = item.subtitle;
                chip.appendChild(sub);
            }

            chip.addEventListener('click', function () {
                if (busy) { return; }
                // "Show more" is not something the patient said, so it does not
                // belong in the transcript as their message.
                if (item.value !== '__more__') { addBubble('user', item.title); }
                send({ choice: { type: options.type, value: String(item.value) } });
            });

            wrap.appendChild(chip);
        });

        stream.appendChild(wrap);
        currentCards = wrap;
        scroll();
    }

    function renderSummary(summary, booked) {
        if (!summary) { return; }

        var card = document.createElement('div');
        card.className = 'pm-summary' + (booked ? ' booked' : '');

        if (booked) {
            var head = document.createElement('div');
            head.className = 'pm-summary-head';
            head.textContent = 'Appointment confirmed';
            card.appendChild(head);
        }

        Object.keys(summary).forEach(function (key) {
            if (summary[key] === null || summary[key] === '' || key === 'id') { return; }

            var row = document.createElement('div');
            row.className = 'pm-summary-row';

            var label = document.createElement('div');
            label.className = 'pm-summary-label';
            label.textContent = prettyLabel(key);

            var value = document.createElement('div');
            value.className = 'pm-summary-value';
            value.textContent = summary[key];

            row.appendChild(label);
            row.appendChild(value);
            card.appendChild(row);
        });

        stream.appendChild(card);
        scroll();
    }

    function prettyLabel(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    function scroll() {
        window.requestAnimationFrame(function () {
            stream.scrollTop = stream.scrollHeight;
        });
    }

    /* ---------------- conversation ---------------- */

    function setBusy(value) {
        busy = value;
        sendBtn.disabled = value;
        micBtn.disabled = value || !speechSupported;
        status.textContent = value ? 'Typing...' : 'Online';
    }

    /**
     * Narrate what the assistant is doing while the server works.
     *
     * The server tells us, one turn ahead, which PureMed calls the next answer
     * will trigger. That is why these appear DURING the wait rather than after
     * it - the API calls finish before the next response exists.
     */
    function runStatus(lines) {
        var nodes = [];
        var timers = [];

        if (!lines || !lines.length) {
            var typing = showTyping();
            return { stop: function () { typing.remove(); } };
        }

        function show(index) {
            if (index > 0) { nodes[index - 1].classList.add('done'); }

            var row = document.createElement('div');
            row.className = 'pm-status';
            row.innerHTML = '<span class="pm-spin"></span>';
            var label = document.createElement('span');
            label.textContent = lines[index];
            row.appendChild(label);
            stream.appendChild(row);
            nodes.push(row);
            scroll();

            if (index + 1 < lines.length) {
                timers.push(window.setTimeout(function () { show(index + 1); }, 1100));
            }
        }

        show(0);

        return {
            stop: function () {
                timers.forEach(window.clearTimeout);
                nodes.forEach(function (n) { n.remove(); });
            }
        };
    }

    function send(payload) {
        if (busy) { return; }
        voiceOutput.stop();
        clearCards();
        setBusy(true);

        var typing = runStatus(pendingStatus);
        pendingStatus = null;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload || {})
        })
            .then(function (response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                return response.json();
            })
            .then(function (data) {
                typing.stop();
                paint(data);
            })
            .catch(function () {
                typing.stop();
                addBubble('assistant', 'Sorry, I lost the connection for a moment. Could you say that again?', 'error');
                setBusy(false);
            });
    }

    /**
     * Paint the assistant's turn one bubble at a time so it reads like a
     * person replying rather than a page of text appearing at once.
     */
    function paint(data) {
        var messages = data.messages || [];
        var index = 0;

        function next() {
            if (index >= messages.length) {
                renderOptions(data.options);
                applyInput(data.input);
                pendingStatus = data.pending || null;
                setBusy(false);
                resumeListening(data);
                return;
            }

            var message = messages[index++];
            addBubble('assistant', message.text, message.kind);
            voiceOutput.speak(message.text);

            // The assistant has asked for the keyboard, so stop listening -
            // reopening the mic here would just mishear the same thing again.
            if (message.kind === 'focus') {
                setHandsFree(false);
                window.setTimeout(function () { input.focus(); }, 50);
            }
            window.setTimeout(next, index < messages.length ? 320 : 0);
        }

        next();
    }

    function applyInput(config) {
        var enabled = !config || config.enabled !== false;
        input.disabled = !enabled;
        sendBtn.disabled = !enabled;
        micBtn.disabled = !enabled || !speechSupported;
        input.placeholder = (config && config.placeholder) || 'Type your reply...';

        if (enabled) { input.focus(); }
    }

    function submitTyped() {
        var text = input.value.trim();
        if (!text || busy) { return; }

        // The server treats a spoken answer more carefully than a typed one -
        // the patient can see what they typed, but not what was misheard.
        var source = spokenSubmission ? 'voice' : 'text';
        spokenSubmission = false;

        // "start over" and friends are handled by the server so every phrasing
        // behaves the same. The Start over button is the explicit reset.
        addBubble('user', text);
        input.value = '';
        autoGrow();
        send({ text: text, source: source });
    }

    function restart() {
        if (busy) { return; }
        voiceOutput.stop();
        clearCards();
        setBusy(true);

        fetch(RESET_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                stream.innerHTML = '';
                paint(data);
            })
            .catch(function () {
                addBubble('assistant', 'I could not restart just now. Please reload the page.', 'error');
                setBusy(false);
            });
    }

    /* ---------------- composer ---------------- */

    function autoGrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    input.addEventListener('input', autoGrow);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submitTyped();
        }
    });
    sendBtn.addEventListener('click', submitTyped);
    restartBtn.addEventListener('click', restart);

    /* ---------------- voice output ----------------
       Speaking is a pure output adapter: it reads whatever the assistant says
       and knows nothing about the booking flow. Turning it off changes only
       the sound, never the conversation.                                     */

    var voiceOutput = (function () {
        var supported = 'speechSynthesis' in window;
        var PREF_KEY = 'puremed_assistant_speak';
        var enabled = false;

        // On by default - the patient should hear the assistant. Browsers block
        // speech until the first user gesture, so the opening greeting may stay
        // silent; every reply after the patient's first action is spoken.
        try {
            enabled = supported && window.localStorage.getItem(PREF_KEY) !== '0';
        } catch (error) { enabled = supported; }

        function paintButton() {
            speakerBtn.classList.toggle('on', enabled);
            speakerBtn.innerHTML = enabled ? '&#128266;' : '&#128264;';
            speakerBtn.title = enabled ? 'Voice replies on - click to mute' : 'Voice replies off - click to hear replies';
        }

        function remember() {
            try { window.localStorage.setItem(PREF_KEY, enabled ? '1' : '0'); } catch (error) { /* private mode */ }
        }

        // Number of utterances still to be spoken, so hands-free listening can
        // wait until the assistant has actually stopped talking - otherwise the
        // microphone records the assistant's own voice.
        var queued = 0;
        var idleCallback = null;

        function settle() {
            queued = Math.max(0, queued - 1);
            if (queued === 0) {
                speakerBtn.classList.remove('speaking');
                var callback = idleCallback;
                idleCallback = null;
                if (callback) { callback(); }
            }
        }

        function stop() {
            queued = 0;
            idleCallback = null;
            if (!supported) { return; }
            try { window.speechSynthesis.cancel(); } catch (error) { /* ignore */ }
            speakerBtn.classList.remove('speaking');
        }

        function speak(text) {
            if (!enabled || !supported || !text) { return; }

            try {
                var utterance = new SpeechSynthesisUtterance(String(text));
                utterance.lang = 'en-US';
                utterance.rate = 1;
                utterance.pitch = 1;
                utterance.onstart = function () { speakerBtn.classList.add('speaking'); };
                utterance.onend = settle;
                utterance.onerror = settle;
                queued += 1;
                window.speechSynthesis.speak(utterance);
            } catch (error) { /* speech is optional - never break the chat */ }
        }

        /** Run once the assistant has finished speaking (immediately if muted). */
        function whenSilent(callback) {
            if (queued === 0) { callback(); return; }
            idleCallback = callback;

            // Safety net: browsers block speech until the first gesture, and a
            // blocked utterance never fires onend. Without this, hands-free
            // listening would wait for a voice that is never coming.
            window.setTimeout(function () {
                if (idleCallback !== callback) { return; }
                if (supported && window.speechSynthesis.speaking) { return; }
                queued = 0;
                idleCallback = null;
                callback();
            }, 1200);
        }

        if (!supported) {
            speakerBtn.disabled = true;
            speakerBtn.title = 'This browser cannot read replies aloud';
        }

        speakerBtn.addEventListener('click', function () {
            if (!supported) { return; }
            enabled = !enabled;
            if (!enabled) { stop(); }
            paintButton();
            remember();
            // The click is the user gesture browsers require before speaking,
            // so confirm out loud that sound is now on.
            if (enabled) { speak('Voice replies are on.'); }
        });

        paintButton();

        return {
            speak: speak,
            stop: stop,
            whenSilent: whenSilent,
            enable: function () {
                if (!supported || enabled) { return; }
                enabled = true;
                paintButton();
                remember();
            }
        };
    })();

    /* ---------------- voice input ----------------
       Voice is only an input method: it turns speech into text and hands it to
       the same send() the keyboard uses. No booking logic lives here.        */

    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition || null;
    var speechSupported = Boolean(SpeechRecognition);
    var recognition = null;
    var listening = false;

    // Hands-free: the mic stays armed for the whole conversation instead of
    // needing a click per sentence. It reopens itself once the assistant has
    // finished speaking, and closes when the patient stops talking.
    var handsFree = false;
    var unheardInARow = 0;

    if (!speechSupported) {
        micBtn.disabled = true;
        micBtn.title = 'Voice input is not supported in this browser';
    }

    function setHandsFree(on) {
        handsFree = on;
        unheardInARow = 0;
        micBtn.classList.toggle('armed', on && !listening);
        micBtn.title = on ? 'Hands-free listening on - tap to turn off' : 'Tap to talk';

        if (!on) {
            stopListening();
            hint.textContent = 'Type or tap the microphone to speak.';
        }
    }

    micBtn.addEventListener('click', function () {
        if (!speechSupported) { return; }

        if (handsFree) { setHandsFree(false); return; }

        // Speaking into the mic implies a spoken conversation, so switch replies
        // to voice too - and never talk over the patient.
        voiceOutput.stop();
        voiceOutput.enable();
        setHandsFree(true);

        if (!busy) { startListening(); }
    });

    /**
     * Re-open the microphone after the assistant's turn.
     *
     * Waits for speech to finish so the assistant is never transcribing itself,
     * and stands down if the patient has started typing instead.
     */
    function resumeListening(data) {
        if (!handsFree || !speechSupported) { return; }
        if (data && data.input && data.input.enabled === false) { return; }

        voiceOutput.whenSilent(function () {
            if (!handsFree || busy || listening) { return; }
            if (input.value.trim() !== '') { return; }   // they are typing

            window.setTimeout(function () {
                if (handsFree && !busy && !listening && input.value.trim() === '') {
                    startListening();
                }
            }, 350);
        });
    }

    function startListening() {
        recognition = new SpeechRecognition();
        recognition.lang = 'en-US';
        recognition.interimResults = true;
        recognition.continuous = false;
        recognition.maxAlternatives = 1;

        var finalText = '';
        listening = true;
        micBtn.classList.add('listening');
        hint.textContent = 'Listening...';

        recognition.onresult = function (event) {
            var interim = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                var transcript = event.results[i][0].transcript || '';
                if (event.results[i].isFinal) { finalText += transcript; } else { interim += transcript; }
            }
            input.value = (finalText + ' ' + interim).trim();
            autoGrow();
            hint.textContent = 'Listening... ' + (interim || finalText);
        };

        recognition.onerror = function (event) {
            var blocked = event.error === 'not-allowed';
            if (blocked) { setHandsFree(false); }
            stopListening();
            hint.textContent = blocked
                ? 'Microphone blocked. Allow mic access and try again.'
                : 'I could not hear that. Please try again.';
        };

        recognition.onend = function () {
            var spoken = input.value.trim();
            stopListening(true);

            if (spoken) {
                unheardInARow = 0;
                spokenSubmission = true;
                submitTyped();
                return;
            }

            if (!handsFree) { return; }

            // Silence. Reopen the mic, but give up after a few empty tries so a
            // muted or noisy mic does not spin forever.
            unheardInARow += 1;

            if (unheardInARow >= 3) {
                setHandsFree(false);
                hint.textContent = "I couldn't hear anything. Tap the microphone when you're ready.";
                return;
            }

            window.setTimeout(function () {
                if (handsFree && !busy && !listening) { startListening(); }
            }, 500);
        };

        try {
            recognition.start();
        } catch (error) {
            stopListening();
        }
    }

    function stopListening(keepHint) {
        listening = false;
        micBtn.classList.remove('listening');
        micBtn.classList.toggle('armed', handsFree);

        if (!keepHint) {
            hint.textContent = handsFree
                ? 'Hands-free listening is on.'
                : 'Type or tap the microphone to speak.';
        }

        if (recognition) {
            try {
                recognition.onresult = null;
                recognition.onerror = null;
                recognition.onend = null;
                recognition.stop();
            } catch (error) { /* already stopped */ }
            recognition = null;
        }
    }

    /* ---------------- boot ---------------- */

    send({ start: true });
})();
</script>
@endsection
