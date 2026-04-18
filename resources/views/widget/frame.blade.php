<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $agent->company_name }} Chat</title>
    <style>
        :root { --bg:#0f0f10; --panel:#111112; --panel-soft:#3a3a3c; --panel-strong:#1a1a1c; --text:#f7f7f8; --text-soft:#9a9a9d; --border:#505052; --composer-border:#d9d9dc; --composer-border-idle:#77787c; --user-bubble:#ffffff; --user-text:#111112; --privacy:#c4c4c8; --tab:#2c2c2e; --tab-active:#f2f2f3; --tab-active-text:#111112; }
        body[data-theme="light"] { --bg:#f3f5f8; --panel:#ffffff; --panel-soft:#eef2f6; --panel-strong:#ffffff; --text:#18202c; --text-soft:#667085; --border:#dde3ea; --composer-border:#b8c2cf; --composer-border-idle:#d7dee8; --user-bubble:#18202c; --user-text:#ffffff; --privacy:#667085; --tab:#edf2f7; --tab-active:#18202c; --tab-active-text:#ffffff; }
        * { box-sizing:border-box; }
        html, body { margin:0; height:100%; font-family:"Segoe UI",sans-serif; background:var(--bg); color:var(--text); }
        body { overflow:hidden; }
        .widget { display:flex; flex-direction:column; height:100vh; background:var(--bg); overflow:hidden; }
        .header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:6px 10px 5px; border-bottom:1px solid var(--border); background:var(--panel); }
        .brand { display:flex; flex-direction:column; align-items:flex-start; gap:0; min-width:0; }
        .brand-logo { display:grid; place-items:center; width:72px; height:44px; margin:0 0 -3px; overflow:hidden; flex:0 0 auto; }
        .brand-logo img { width:100%; height:100%; object-fit:contain; }
        .brand-subtitle { margin:0; color:#fff; font-size:11px; line-height:1.05; font-weight:600; letter-spacing:.01em; white-space:nowrap; }
        .header-actions { position:relative; display:flex; align-items:center; gap:10px; flex:0 0 auto; padding-top:2px; }
        .action-button { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border:0; background:transparent; color:var(--text); padding:0; cursor:pointer; opacity:.92; }
        .action-button svg { width:20px; height:20px; display:block; }
        .menu { position:absolute; top:30px; right:26px; min-width:168px; padding:6px; border:1px solid var(--border); border-radius:12px; background:var(--panel); box-shadow:0 18px 40px rgba(0,0,0,.22); z-index:20; }
        .menu button { display:block; width:100%; padding:9px 10px; border:0; border-radius:8px; background:transparent; color:var(--text); font:inherit; font-size:12px; text-align:left; cursor:pointer; transition:background-color 140ms ease, color 140ms ease, transform 140ms ease; }
        .menu button:hover { background:color-mix(in srgb, var(--panel-soft) 72%, transparent); transform:translateY(-1px); }
        .body { position:relative; flex:1; min-height:0; overflow:hidden; }
        .panel { position:absolute; inset:0; height:100%; opacity:1; transform:translateX(0); transition:opacity 180ms ease, transform 180ms ease; }
        .panel.panel-hidden-left { opacity:0; transform:translateX(-16px); pointer-events:none; }
        .panel.panel-hidden-right { opacity:0; transform:translateX(16px); pointer-events:none; }
        .conversation, .space-panel { height:100%; overflow-y:auto; padding:10px 9px 0; scrollbar-color:#5e5e60 transparent; }
        .messages { display:flex; flex-direction:column; gap:18px; min-height:100%; padding:2px 0 18px; }
        .message-block { display:flex; flex-direction:column; gap:3px; max-width:76%; }
        .message-block.user { align-self:flex-end; align-items:flex-end; }
        .message-block.assistant { align-self:flex-start; align-items:flex-start; }
        .bubble { padding:12px 15px 13px; border-radius:11px; background:var(--panel-soft); color:var(--text); font-size:14px; line-height:1.32; white-space:pre-wrap; word-break:break-word; }
        .message-block.user .bubble { background:var(--user-bubble); color:var(--user-text); }
        .meta { padding:0 6px; font-size:10px; line-height:1.2; color:var(--text-soft); }
        .typing { display:inline-flex; align-items:center; gap:5px; min-width:70px; }
        .typing-dot { width:4px; height:4px; border-radius:999px; background:currentColor; opacity:.85; animation:pulse 1s infinite ease-in-out; }
        .typing-dot:nth-child(2){animation-delay:.15s}.typing-dot:nth-child(3){animation-delay:.3s}
        @keyframes pulse { 0%,80%,100%{transform:translateY(0);opacity:.35} 40%{transform:translateY(-1px);opacity:1} }
        .space-panel { padding-bottom:18px; }
        .space-list, .space-detail { display:grid; gap:10px; }
        .space-heading-block { margin:0 0 12px; padding:0 0 10px; border-bottom:1px solid var(--border); }
        .space-heading { margin:0; font-size:18px; line-height:1.2; font-weight:600; color:var(--text); }
        .space-card { width:100%; padding:12px; border:1px solid var(--border); border-radius:12px; background:var(--panel-strong); color:var(--text); text-align:left; cursor:pointer; transition:background-color 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease; }
        .space-title, .space-detail-title { margin:0; font-size:13px; line-height:1.35; font-weight:600; }
        .space-excerpt, .space-empty, .space-detail-meta, .space-detail-body { margin:6px 0 0; font-size:11px; line-height:1.55; color:var(--text-soft); }
        .space-detail-body { color:var(--text); white-space:pre-wrap; }
        .space-back { display:inline-flex; align-items:center; gap:6px; align-self:flex-start; padding:0; border:0; background:transparent; color:var(--text-soft); font:inherit; font-size:11px; cursor:pointer; transition:color 140ms ease, opacity 140ms ease; }
        .space-primary { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:12px; background:var(--panel); color:var(--text); font:inherit; font-size:12px; font-weight:600; text-align:left; cursor:pointer; transition:background-color 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease; }
        .space-card:hover, .space-primary:hover { background:color-mix(in srgb, var(--panel-soft) 46%, var(--panel-strong)); border-color:color-mix(in srgb, var(--composer-border) 42%, var(--border)); transform:translateY(-1px); box-shadow:0 10px 22px rgba(0,0,0,.10); }
        .space-back:hover { color:var(--text); }
        .footer { padding:8px 9px 10px; background:var(--bg); }
        .error { margin:0 3px 8px; font-size:11px; color:#ff7f8d; }
        .hidden { display:none !important; }
        .composer { position:relative; border:1px solid var(--composer-border-idle); border-radius:11px; padding:10px 11px 9px; background:transparent; transition:border-color 140ms ease, background-color 140ms ease, box-shadow 140ms ease; }
        .composer:hover, .composer:focus-within { border-color:var(--composer-border); }
        .composer-top { display:flex; align-items:flex-end; gap:10px; }
        .composer textarea { flex:1; min-height:30px; max-height:128px; padding:0; border:0; resize:none; background:transparent; color:var(--text); font:inherit; font-size:12px; line-height:1.3; }
        .composer textarea::placeholder { color:var(--text); opacity:.78; }
        .composer textarea:focus { outline:none; }
        .send-button { flex:0 0 auto; width:18px; height:18px; padding:0; border:0; background:transparent; color:var(--text); cursor:pointer; opacity:.92; }
        .send-button[disabled] { opacity:.35; cursor:default; }
        .composer-bottom { display:flex; align-items:center; gap:10px; margin-top:9px; }
        .composer-icon { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border:0; padding:0; background:transparent; color:var(--text); opacity:.8; cursor:pointer; }
        .emoji-picker { position:absolute; left:10px; bottom:58px; width:220px; padding:10px; border:1px solid var(--border); border-radius:14px; background:var(--panel); box-shadow:0 18px 38px rgba(0,0,0,.20); z-index:18; }
        .emoji-grid { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:6px; }
        .emoji-option { display:grid; place-items:center; width:100%; aspect-ratio:1; border:0; border-radius:10px; background:transparent; font-size:18px; cursor:pointer; transition:background-color 140ms ease, transform 140ms ease; }
        .emoji-option:hover { background:color-mix(in srgb, var(--panel-soft) 68%, transparent); transform:translateY(-1px); }
        .privacy { margin-top:3px; text-align:center; font-size:9px; line-height:1.25; color:var(--privacy); }
        .privacy a { color:inherit; }
    </style>
</head>
<body data-theme="dark">
<div class="widget">
    <header class="header">
        <div class="brand">
            <div class="brand-logo"><img id="logo" src="{{ $darkLogoUrl }}" alt="{{ $agent->company_name }} logo"></div>
            <p class="brand-subtitle">K-Labs Agent</p>
        </div>
        <div class="header-actions">
            <button class="action-button" id="theme-toggle" type="button" aria-label="Toggle theme">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><circle cx="5" cy="12" r="1.7"></circle><circle cx="12" cy="12" r="1.7"></circle><circle cx="19" cy="12" r="1.7"></circle></svg>
            </button>
            <button class="action-button" id="close-widget" type="button" aria-label="Close chat">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6 18 18"></path><path d="M18 6 6 18"></path></svg>
            </button>
        </div>
    </header>

    <div class="menu hidden" id="menu">
        <button type="button" id="menu-chats">Chats</button>
        <button type="button" id="menu-help">Help</button>
        <button type="button" id="menu-theme">Switch mode</button>
        <button type="button" id="menu-download">Download transcript</button>
    </div>

    <div class="body">
        <section class="panel" id="panel-messages">
            <div class="conversation" id="conversation"><div class="messages" id="messages"></div></div>
        </section>

        <section class="panel hidden" id="panel-chats">
            <div class="space-panel">
                <div class="space-list" id="chats-list"></div>
            </div>
        </section>

        <section class="panel hidden" id="panel-help">
            <div class="space-panel">
                <div class="space-heading-block">
                    <h2 class="space-heading">FAQs</h2>
                </div>
                <div class="space-list" id="help-list"></div>
                <div class="space-detail hidden" id="help-detail">
                    <button class="space-back" id="help-back" type="button">← Back to topics</button>
                    <h2 class="space-detail-title" id="help-detail-title"></h2>
                    <p class="space-detail-meta" id="help-detail-meta"></p>
                    <div class="space-detail-body" id="help-detail-body"></div>
                </div>
            </div>
        </section>
    </div>

    <footer class="footer" id="footer">
        <div class="error hidden" id="error-box"></div>
        <form class="composer" id="composer">
            <div class="emoji-picker hidden" id="emoji-picker">
                <div class="emoji-grid" id="emoji-grid"></div>
            </div>
            <div class="composer-top">
                <textarea id="input" rows="1" placeholder="Ask a question...."></textarea>
                <button class="send-button" id="send" type="submit" aria-label="Send message">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V6"></path><path d="M6.5 11.5 12 6l5.5 5.5"></path></svg>
                </button>
            </div>
            <div class="composer-bottom">
                <button class="composer-icon" id="emoji-toggle" type="button" aria-label="Open emoji picker"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 14.2c.9 1 2 1.5 3.5 1.5s2.6-.5 3.5-1.5"></path><path d="M9 10.3h.01"></path><path d="M15 10.3h.01"></path></svg></button>
                <span class="composer-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 4v10"></path><path d="M8.5 8.5V14a3.5 3.5 0 1 0 7 0V8.5"></path><path d="M6 11.5V14a6 6 0 1 0 12 0v-2.5"></path></svg></span>
            </div>
        </form>
        <div class="privacy">By chatting with us, you agree to our <a href="#" rel="nofollow">privacy policy</a></div>
    </footer>
</div>

<template id="typing-template">
    <div class="message-block assistant" data-kind="typing"><div class="bubble typing" aria-label="Assistant is typing"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div></div>
</template>

<script>
(() => {
    const widgetToken = @json($agent->widget_token);
    const bootstrapUrl = @json($bootstrapUrl);
    const createSessionUrl = @json($createSessionUrl);
    const sendMessageUrl = @json($sendMessageUrl);
    const storeLeadUrl = @json($storeLeadUrl);
    const lightLogoUrl = @json($lightLogoUrl);
    const darkLogoUrl = @json($darkLogoUrl);
    const agentName = @json($agent->name);
    const fallbackMessage = @json($agent->fallback_message ?: 'I do not have enough information to answer that yet.');
    const welcomeMessage = @json($agent->welcome_message ?: 'Hi there,\nHow may I help you?');
    const helpTopics = [
        {
            id: 'services',
            title: 'K-Labs Services',
            excerpt: 'Overview of the main service areas K-Labs offers to clients.',
            content: 'K-Labs provides digital product strategy, software design, custom application development, AI-assisted workflows, and long-term product support. The company helps clients move from idea validation to launch and post-launch iteration.',
        },
        {
            id: 'web',
            title: 'Website and Product Development',
            excerpt: 'What K-Labs builds for websites, apps, and customer-facing products.',
            content: 'K-Labs works on websites, internal tools, customer portals, SaaS products, and business workflow systems. Typical work includes UI implementation, backend development, integrations, admin dashboards, and production-ready deployment support.',
        },
        {
            id: 'ai',
            title: 'AI and Automation',
            excerpt: 'How K-Labs uses AI systems and automation in client projects.',
            content: 'K-Labs can design AI-powered chat experiences, retrieval systems, workflow assistants, lead capture flows, and business automations. The focus is on practical systems that improve operations, customer support, and internal team efficiency.',
        },
        {
            id: 'engagement',
            title: 'Working With K-Labs',
            excerpt: 'What clients can expect from discovery through delivery.',
            content: 'Projects usually begin with requirement clarification, scope planning, and design alignment. From there, K-Labs moves into implementation, review rounds, QA, and launch support. Engagements can be structured as one-time builds, phased product work, or ongoing support.',
        },
    ];
    const storageKey = `k-agent-widget:${widgetToken}`;
    const body = document.body;
    const conversation = document.getElementById('conversation');
    const messages = document.getElementById('messages');
    const composer = document.getElementById('composer');
    const footer = document.getElementById('footer');
    const input = document.getElementById('input');
    const send = document.getElementById('send');
    const errorBox = document.getElementById('error-box');
    const logo = document.getElementById('logo');
    const menu = document.getElementById('menu');
    const themeToggle = document.getElementById('theme-toggle');
    const emojiToggle = document.getElementById('emoji-toggle');
    const emojiPicker = document.getElementById('emoji-picker');
    const emojiGrid = document.getElementById('emoji-grid');
    const panelMessages = document.getElementById('panel-messages');
    const panelChats = document.getElementById('panel-chats');
    const panelHelp = document.getElementById('panel-help');
    const chatsList = document.getElementById('chats-list');
    const helpList = document.getElementById('help-list');
    const helpDetail = document.getElementById('help-detail');
    const helpDetailTitle = document.getElementById('help-detail-title');
    const helpDetailMeta = document.getElementById('help-detail-meta');
    const helpDetailBody = document.getElementById('help-detail-body');
    const helpBack = document.getElementById('help-back');
    const state = { sessionId:null, theme:'dark', sending:false, tab:'messages', helpLoaded:false, topicId:null, archives:[] };
    const emojis = ['😀', '😁', '😂', '🙂', '😉', '😍', '👍', '👏', '🙌', '🔥', '✨', '🎉', '💡', '🚀', '❤️', '🙏', '😊', '😎'];

    const read = () => { try { return JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch { return {}; } };
    const write = () => { try { localStorage.setItem(storageKey, JSON.stringify({ sessionId:state.sessionId, theme:state.theme, archives:state.archives })); } catch {} };
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const scrollToBottom = () => { conversation.scrollTop = conversation.scrollHeight; };

    function setTheme(theme) {
        state.theme = theme === 'light' ? 'light' : 'dark';
        body.dataset.theme = state.theme;
        logo.src = state.theme === 'light' ? lightLogoUrl : darkLogoUrl;
        write();
    }

    function showError(message) { errorBox.textContent = message; errorBox.classList.remove('hidden'); }
    function clearError() { errorBox.textContent = ''; errorBox.classList.add('hidden'); }
    function relativeTime(createdAt, role) {
        if (!createdAt) return role === 'assistant' ? `${agentName} . Just Now` : '';
        const then = new Date(createdAt).getTime();
        const diffMinutes = Math.max(0, Math.round((Date.now() - then) / 60000));
        if (diffMinutes <= 1) return role === 'assistant' ? `${agentName} . Just Now` : 'Just now';
        return role === 'assistant' ? `${agentName} . ${diffMinutes} min` : `${diffMinutes} min`;
    }
    function articleTime(createdAt) {
        if (!createdAt) return 'Updated recently';
        return `Updated ${new Date(createdAt).toLocaleDateString()}`;
    }
    function clearMessages() { messages.innerHTML = ''; }
    function closeMenu() { menu.classList.add('hidden'); }
    function toggleMenu() { menu.classList.toggle('hidden'); }
    function closeEmojiPicker() { emojiPicker.classList.add('hidden'); }
    function toggleEmojiPicker() { emojiPicker.classList.toggle('hidden'); closeMenu(); }

    function transitionPanels(nextTab) {
        const order = ['messages', 'chats', 'help'];
        const currentIndex = order.indexOf(state.tab);
        const nextIndex = order.indexOf(nextTab);
        const direction = nextIndex >= currentIndex ? 'forward' : 'backward';
        const panels = {
            messages: panelMessages,
            chats: panelChats,
            help: panelHelp,
        };

        Object.entries(panels).forEach(([key, panel]) => {
            panel.classList.remove('hidden', 'panel-hidden-left', 'panel-hidden-right');

            if (key === nextTab) {
                panel.classList.add(direction === 'forward' ? 'panel-hidden-right' : 'panel-hidden-left');
            } else if (key === state.tab) {
                panel.classList.add(direction === 'forward' ? 'panel-hidden-left' : 'panel-hidden-right');
            } else {
                panel.classList.add('hidden');
            }
        });

        requestAnimationFrame(() => {
            Object.entries(panels).forEach(([key, panel]) => {
                if (key === nextTab) {
                    panel.classList.remove('panel-hidden-left', 'panel-hidden-right');
                } else if (key === state.tab) {
                    panel.classList.add(direction === 'forward' ? 'panel-hidden-left' : 'panel-hidden-right');
                }
            });
        });

        setTimeout(() => {
            Object.entries(panels).forEach(([key, panel]) => {
                panel.classList.remove('panel-hidden-left', 'panel-hidden-right');
                panel.classList.toggle('hidden', key !== nextTab);
            });
        }, 190);
    }

    function setActiveTab(tab) {
        if (tab === state.tab) {
            closeMenu();
            return;
        }

        transitionPanels(tab);
        state.tab = tab;
        footer.classList.toggle('hidden', tab !== 'messages');
        clearError();
        if (tab === 'chats') renderChatsList();
        if (tab === 'help' && !state.helpLoaded) renderHelpTopics();
        closeMenu();
    }

    function createMessage(role, content, createdAt, isWelcome = false) {
        const block = document.createElement('div');
        block.className = `message-block ${role}`;
        block.dataset.role = role;
        block.dataset.content = content;
        block.dataset.createdAt = createdAt || '';
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = content;
        block.appendChild(bubble);
        if (role === 'assistant' || isWelcome) {
            const meta = document.createElement('div');
            meta.className = 'meta';
            meta.textContent = relativeTime(createdAt, 'assistant');
            block.appendChild(meta);
        }
        messages.appendChild(block);
        scrollToBottom();
    }

    function ensureWelcomeState() { if (messages.children.length === 0) createMessage('assistant', welcomeMessage, null, true); }
    function insertEmoji(emoji) {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        input.value = `${input.value.slice(0, start)}${emoji}${input.value.slice(end)}`;
        const caret = start + emoji.length;
        input.focus();
        input.setSelectionRange(caret, caret);
    }
    function renderEmojiPicker() {
        emojiGrid.innerHTML = '';
        emojis.forEach((emoji) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'emoji-option';
            button.textContent = emoji;
            button.addEventListener('click', () => {
                insertEmoji(emoji);
                closeEmojiPicker();
            });
            emojiGrid.appendChild(button);
        });
    }
    function addTypingIndicator() {
        const typingNode = document.getElementById('typing-template').content.firstElementChild.cloneNode(true);
        messages.appendChild(typingNode);
        scrollToBottom();
        return typingNode;
    }

    function downloadTranscript() {
        const rows = Array.from(messages.querySelectorAll('.message-block')).map((node) => {
            const role = node.dataset.role === 'user' ? 'Visitor' : agentName;
            const createdAt = node.dataset.createdAt ? new Date(node.dataset.createdAt).toLocaleString() : 'Now';
            return `[${createdAt}] ${role}: ${node.dataset.content || ''}`;
        });
        if (rows.length === 0) return showError('No transcript is available yet.');
        const blob = new Blob([rows.join('\n\n')], { type:'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'chat-transcript.txt';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    function messageRecords() {
        return Array.from(messages.querySelectorAll('.message-block')).map((node) => ({
            role: node.dataset.role,
            content: node.dataset.content || '',
            createdAt: node.dataset.createdAt || '',
        })).filter((message) => message.content.trim() !== '');
    }

    function archiveCurrentConversation() {
        const transcript = messageRecords().filter((message) => !(message.role === 'assistant' && message.content === welcomeMessage));

        if (!state.sessionId || transcript.length === 0) {
            return;
        }

        const firstVisitorMessage = transcript.find((message) => message.role === 'user');
        const preview = firstVisitorMessage?.content || transcript[0].content;
        const archivedAt = new Date().toISOString();

        state.archives = [
            {
                sessionId: state.sessionId,
                title: preview.length > 48 ? `${preview.slice(0, 47)}...` : preview,
                preview: transcript[transcript.length - 1].content,
                updatedAt: archivedAt,
            },
            ...state.archives.filter((chat) => chat.sessionId !== state.sessionId),
        ].slice(0, 12);

        write();
    }

    function renderChatsList() {
        chatsList.innerHTML = '';

        const newChatButton = document.createElement('button');
        newChatButton.type = 'button';
        newChatButton.className = 'space-primary';
        newChatButton.textContent = 'Start a new chat';
        newChatButton.addEventListener('click', () => {
            state.sessionId = null;
            write();
            clearMessages();
            ensureWelcomeState();
            setActiveTab('messages');
        });
        chatsList.appendChild(newChatButton);

        if (!state.archives.length) {
            const empty = document.createElement('div');
            empty.className = 'space-empty';
            empty.textContent = 'No previous chats yet. When the widget is closed, completed conversations will appear here.';
            chatsList.appendChild(empty);
            return;
        }

        state.archives.forEach((chat) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'space-card';
            button.innerHTML = '<h3 class="space-title"></h3><p class="space-excerpt"></p><p class="space-excerpt"></p>';
            button.querySelector('.space-title').textContent = chat.title || 'Previous chat';
            button.querySelectorAll('.space-excerpt')[0].textContent = chat.preview || 'Open chat';
            button.querySelectorAll('.space-excerpt')[1].textContent = articleTime(chat.updatedAt);
            button.addEventListener('click', async () => {
                state.sessionId = chat.sessionId;
                write();
                await hydrateConversation(chat.sessionId);
                setActiveTab('messages');
            });
            chatsList.appendChild(button);
        });
    }

    function renderHelpTopics() {
        state.helpLoaded = true;
        state.topicId = null;
        helpList.innerHTML = '';
        helpDetail.classList.add('hidden');

        helpTopics.forEach((topic) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'space-card';
            button.innerHTML = '<h3 class="space-title"></h3><p class="space-excerpt"></p>';
            button.querySelector('.space-title').textContent = topic.title;
            button.querySelector('.space-excerpt').textContent = topic.excerpt;
            button.addEventListener('click', () => openHelpTopic(topic.id));
            helpList.appendChild(button);
        });
    }

    async function requestJson(url, method = 'GET', payload = null) {
        const response = await fetch(url, {
            method,
            headers: { 'Accept':'application/json', ...(payload ? { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf() } : {}) },
            ...(payload ? { body:JSON.stringify(payload) } : {}),
        });
        if (!response.ok) throw new Error('Request failed.');
        return response.json();
    }

    async function ensureSession() {
        if (state.sessionId) return;
        const payload = await requestJson(createSessionUrl, 'POST', { widget_token:widgetToken });
        state.sessionId = payload.data.session_id;
        write();
    }

    async function storeLeadIfPossible(message) {
        const emailMatch = message.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
        if (!emailMatch || !state.sessionId) return;
        await requestJson(storeLeadUrl, 'POST', { widget_token:widgetToken, session_id:state.sessionId, name:'Website Visitor', email:emailMatch[0], notes:message });
    }

    function openHelpTopic(topicId) {
        const topic = helpTopics.find((item) => item.id === topicId);
        if (!topic) return;
        state.topicId = topic.id;
        helpList.innerHTML = '';
        helpDetailTitle.textContent = topic.title;
        helpDetailMeta.textContent = 'K-Labs Help Topic';
        helpDetailBody.textContent = topic.content;
        helpDetail.classList.remove('hidden');
    }

    async function hydrateConversation(sessionId = null) {
        clearMessages();
        ensureWelcomeState();
        const url = sessionId ? `${bootstrapUrl}?session_id=${encodeURIComponent(sessionId)}` : bootstrapUrl;
        const payload = await requestJson(url);
        if (!payload.data.session) return;
        state.sessionId = payload.data.session.session_id;
        write();
        const chatMessages = payload.data.session.messages || [];
        if (!chatMessages.length) return;
        clearMessages();
        ensureWelcomeState();
        chatMessages.forEach((message) => createMessage(message.role === 'user' ? 'user' : 'assistant', message.content, message.created_at));
    }

    async function hydrate() {
        const persisted = read();
        state.sessionId = persisted.sessionId || null;
        state.archives = Array.isArray(persisted.archives) ? persisted.archives : [];
        setTheme(persisted.theme || 'dark');
        await hydrateConversation(state.sessionId);
    }

    composer.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || state.sending) return;
        clearError();
        state.sending = true;
        send.disabled = true;
        createMessage('user', message, new Date().toISOString());
        input.value = '';
        const typingNode = addTypingIndicator();
        try {
            await ensureSession();
            await storeLeadIfPossible(message);
            const payload = await requestJson(sendMessageUrl, 'POST', { widget_token:widgetToken, session_id:state.sessionId, message });
            typingNode.remove();
            createMessage('assistant', payload.data.assistant_message?.content || fallbackMessage, payload.data.assistant_message?.created_at || new Date().toISOString());
        } catch {
            typingNode.remove();
            showError('The message could not be sent.');
        } finally {
            state.sending = false;
            send.disabled = false;
            input.focus();
        }
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            composer.requestSubmit();
        }
    });

    helpBack.addEventListener('click', () => renderHelpTopics());
    document.getElementById('menu-chats').addEventListener('click', () => setActiveTab('chats'));
    document.getElementById('menu-help').addEventListener('click', () => setActiveTab('help'));
    themeToggle.addEventListener('click', (event) => { event.stopPropagation(); toggleMenu(); });
    document.getElementById('menu-theme').addEventListener('click', () => { setTheme(state.theme === 'dark' ? 'light' : 'dark'); closeMenu(); });
    document.getElementById('menu-download').addEventListener('click', () => { downloadTranscript(); closeMenu(); });
    emojiToggle.addEventListener('click', (event) => { event.stopPropagation(); toggleEmojiPicker(); });
    document.getElementById('close-widget').addEventListener('click', () => {
        archiveCurrentConversation();
        state.sessionId = null;
        write();
        clearMessages();
        ensureWelcomeState();
        setActiveTab('messages');
        window.parent.postMessage({ source:'k-agent-widget', type:'close' }, '*');
    });
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target) && event.target !== themeToggle && !themeToggle.contains(event.target)) closeMenu();
        if (!emojiPicker.contains(event.target) && event.target !== emojiToggle && !emojiToggle.contains(event.target)) closeEmojiPicker();
    });

    renderEmojiPicker();
    hydrate().catch(() => {
        clearMessages();
        ensureWelcomeState();
        showError('The widget could not be loaded.');
    });
})();
</script>
</body>
</html>
