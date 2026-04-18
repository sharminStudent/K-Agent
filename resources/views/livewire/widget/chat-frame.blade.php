<div
    x-data="kAgentWidgetFrame({
        widgetToken: @js($agent->widget_token),
        agentName: @js($agent->name),
        companyName: @js($agent->company_name),
        welcomeMessage: @js($agent->welcome_message ?: 'Hi there,\nHow may I help you?'),
        fallbackMessage: @js($agent->fallback_message ?: 'I do not have enough information to answer that yet.'),
        createSessionUrl: @js(url('/api/chat/session')),
        sendMessageUrl: @js(url('/api/chat/send-message')),
        storeLeadUrl: @js(url('/api/lead/store')),
        bootstrapUrl: @js($bootstrapUrl),
        lightLogoUrl: @js($lightLogoUrl),
        darkLogoUrl: @js($darkLogoUrl),
        reverbEnabled: @js(filled(config('broadcasting.connections.reverb.key'))),
        helpTopics: @js([
            [
                'id' => 'services',
                'title' => 'Our Services',
                'excerpt' => 'Overview of what K-Labs can build and support.',
                'content' => 'K-Labs works on business websites, custom software, AI-assisted tools, internal systems, dashboards, and product delivery support.',
            ],
            [
                'id' => 'process',
                'title' => 'How We Work',
                'excerpt' => 'What the process looks like from inquiry to delivery.',
                'content' => 'Projects usually start with requirements, planning, and scope review. Then we move into design, implementation, review rounds, QA, and launch support.',
            ],
            [
                'id' => 'pricing',
                'title' => 'Pricing and Quotes',
                'excerpt' => 'How to get a quote and what affects pricing.',
                'content' => 'Pricing depends on project scope, timeline, integrations, and complexity. The fastest way to get a quote is to share your requirements in the chat and leave your email.',
            ],
            [
                'id' => 'support',
                'title' => 'Support and Contact',
                'excerpt' => 'How to continue the conversation with the team.',
                'content' => 'You can use this chat to ask questions, share project details, and leave your contact information. A team member can follow up with you directly.',
            ],
        ]),
    })"
    x-init="init()"
    class="ka-widget"
>
    <style>
        .ka-widget{display:flex;flex-direction:column;width:100%;height:100%;background:transparent;color:#f5f5f5;font-family:Inter,ui-sans-serif,system-ui,sans-serif;--ka-shell-bg:#0d0d0f;--ka-header-bg:#111114;--ka-border:#45454a;--ka-bubble:#3a3a3d;--ka-bubble-text:#f8f8f9;--ka-user-bubble:#ffffff;--ka-user-text:#111114;--ka-body-text:#f5f5f5;--ka-meta:#909097;--ka-composer-bg:#111114;--ka-composer-border:#8b8b91;--ka-shell-border:rgba(255,255,255,.06);--ka-shadow:0 32px 80px rgba(0,0,0,.42),0 12px 28px rgba(0,0,0,.28);--ka-note:#d6d6da;--ka-menu-bg:#151519;--ka-menu-hover:#222228}
        .ka-shell[data-theme='light']{--ka-shell-bg:#f8f8fb;--ka-header-bg:#ffffff;--ka-border:#d9dde5;--ka-bubble:#eceef3;--ka-bubble-text:#16181d;--ka-user-bubble:#16181d;--ka-user-text:#ffffff;--ka-body-text:#16181d;--ka-meta:#6b7280;--ka-composer-bg:#ffffff;--ka-composer-border:#c7ccd6;--ka-shell-border:rgba(17,24,39,.08);--ka-shadow:0 26px 70px rgba(15,23,42,.16),0 8px 20px rgba(15,23,42,.08);--ka-note:#4b5563;--ka-menu-bg:#ffffff;--ka-menu-hover:#f3f4f6}
        .ka-widget *{box-sizing:border-box}.ka-widget [x-cloak]{display:none!important}
        .ka-shell{display:flex;flex-direction:column;width:100%;height:100%;background:var(--ka-shell-bg);border-radius:16px;overflow:hidden;border:1px solid var(--ka-shell-border);box-shadow:var(--ka-shadow)}
        .ka-header{position:relative;display:flex;align-items:flex-start;justify-content:space-between;padding:8px 10px 7px;border-bottom:1px solid var(--ka-border);background:var(--ka-header-bg)}
        .ka-brand h1,.ka-brand p,.ka-msg p{margin:0}
        .ka-brand{display:flex;flex-direction:column;gap:3px;max-width:calc(100% - 48px)}
        .ka-brand img{display:block;max-width:132px;height:22px;object-fit:contain;object-position:left center}
        .ka-brand p{margin-top:1px;font-size:10px;font-weight:600;line-height:1.2;color:var(--ka-body-text)}
        .ka-actions{display:flex;align-items:center;gap:10px;padding-top:1px}
        .ka-actions button{display:grid;place-items:center;width:16px;height:16px;padding:0;border:0;background:transparent;color:var(--ka-body-text);cursor:pointer;transition:transform 180ms ease,opacity 180ms ease,color 180ms ease}
        .ka-actions button:hover{opacity:.82;transform:scale(1.06)}
        .ka-actions button:active{transform:scale(.94)}
        .ka-menu{position:absolute;top:34px;right:34px;display:grid;gap:4px;width:180px;padding:6px;border:1px solid var(--ka-border);border-radius:12px;background:var(--ka-menu-bg);box-shadow:0 18px 38px rgba(0,0,0,.24);z-index:20;transform-origin:top right}
        .ka-menu button{display:flex;align-items:center;justify-content:space-between;width:100%;padding:9px 10px;border:0;border-radius:8px;background:transparent;color:var(--ka-body-text);font:inherit;font-size:12px;text-align:left;cursor:pointer;transition:background-color 180ms ease,color 180ms ease,transform 180ms ease}
        .ka-menu button:hover{background:var(--ka-menu-hover);transform:translateY(-1px)}
        .ka-menu button:active{transform:translateY(0)}
        .ka-panels{position:relative;flex:1;min-height:0}
        .ka-body{position:absolute;inset:0;overflow:auto;padding:10px 9px 12px;background:var(--ka-shell-bg);color:var(--ka-body-text);scrollbar-width:thin;scrollbar-color:#5b5b60 transparent}
        .ka-space-head{display:grid;gap:4px;margin-bottom:12px;padding:0 2px}
        .ka-space-title{margin:0;font-size:15px;font-weight:600;line-height:1.2;color:var(--ka-body-text)}
        .ka-space-copy{margin:0;font-size:11px;line-height:1.45;color:var(--ka-meta)}
        .ka-stack{display:flex;flex-direction:column;gap:9px;min-height:100%}
        .ka-msg{display:flex;flex-direction:column;gap:3px;max-width:82%}
        .ka-msg.assistant{align-self:flex-start}
        .ka-msg.user{align-self:flex-end;align-items:flex-end}
        .ka-bubble{padding:11px 13px;border-radius:11px;background:var(--ka-bubble);color:var(--ka-bubble-text);font-size:12px;line-height:1.28;white-space:pre-wrap;word-break:break-word}
        .ka-msg.assistant .ka-bubble{border-top-left-radius:4px}
        .ka-msg.user .ka-bubble{background:var(--ka-user-bubble);color:var(--ka-user-text);border-top-right-radius:4px}
        .ka-meta{padding:0 4px;font-size:9px;line-height:1.15;color:var(--ka-meta)}
        .ka-spacer{flex:1 1 auto;min-height:18px}
        .ka-typing{display:flex;align-items:center;gap:6px;min-width:72px}
        .ka-typing span{display:block;width:6px;height:6px;border-radius:999px;background:currentColor;animation:ka-bounce 1.2s infinite ease-in-out}
        .ka-typing span:nth-child(2){animation-delay:.15s}
        .ka-typing span:nth-child(3){animation-delay:.3s}
        .ka-help-list,.ka-help-detail,.ka-archive-list{display:grid;gap:10px}
        .ka-help-card,.ka-help-back{width:100%;padding:12px;border:1px solid var(--ka-border);border-radius:14px;background:var(--ka-header-bg);color:var(--ka-body-text);font:inherit;text-align:left;cursor:pointer;transition:border-color 180ms ease,background-color 180ms ease,transform 180ms ease,box-shadow 180ms ease}
        .ka-help-card h3,.ka-help-detail h3{margin:0 0 6px;font-size:13px}
        .ka-help-card p,.ka-help-detail p{margin:0;font-size:11px;line-height:1.5;color:var(--ka-meta);white-space:pre-wrap}
        .ka-help-back{width:auto;padding:0;border:0;background:transparent}
        .ka-help-card:hover{transform:translateY(-1px);box-shadow:0 14px 28px rgba(0,0,0,.16);border-color:color-mix(in srgb,var(--ka-border) 42%, #ffffff 34%);background:color-mix(in srgb,var(--ka-header-bg) 88%, #ffffff 12%)}
        .ka-archive-card{width:100%;padding:12px;border:1px solid var(--ka-border);border-radius:14px;background:var(--ka-header-bg);color:var(--ka-body-text);font:inherit;text-align:left;cursor:pointer;transition:border-color 180ms ease,background-color 180ms ease,transform 180ms ease,box-shadow 180ms ease}
        .ka-archive-card:hover{transform:translateY(-1px);box-shadow:0 14px 28px rgba(0,0,0,.16);border-color:color-mix(in srgb,var(--ka-border) 42%, #ffffff 34%);background:color-mix(in srgb,var(--ka-header-bg) 88%, #ffffff 12%)}
        .ka-archive-card.is-primary{border-style:dashed}
        .ka-archive-title{margin:0 0 5px;font-size:13px;font-weight:600;line-height:1.35}
        .ka-archive-copy,.ka-archive-time,.ka-empty-copy{margin:0;font-size:11px;line-height:1.5;color:var(--ka-meta)}
        .ka-footer{padding:0 9px 10px;background:var(--ka-shell-bg)}
        .ka-error{margin:0 0 8px;font-size:11px;color:#fca5a5}
        .ka-compose-shell{position:relative;border:1px solid var(--ka-composer-border);border-radius:12px;background:var(--ka-composer-bg);padding:9px 10px 8px;transition:border-color 180ms ease,box-shadow 180ms ease,transform 180ms ease,background-color 180ms ease}
        .ka-compose-shell:hover{border-color:#c9cbd3;box-shadow:0 0 0 1px rgba(255,255,255,.07),0 12px 24px rgba(0,0,0,.14);background:color-mix(in srgb,var(--ka-composer-bg) 92%, #ffffff 8%)}
        .ka-shell[data-theme='light'] .ka-compose-shell:hover{border-color:#8b96a8;box-shadow:0 0 0 1px rgba(15,23,42,.06),0 12px 24px rgba(15,23,42,.10)}
        .ka-compose-shell:focus-within{border-color:#f3f4f6;box-shadow:0 0 0 3px rgba(255,255,255,.10),0 12px 28px rgba(0,0,0,.18);transform:translateY(-1px)}
        .ka-shell[data-theme='light'] .ka-compose-shell:focus-within{border-color:#5f6b7a;box-shadow:0 0 0 3px rgba(59,130,246,.10),0 12px 28px rgba(15,23,42,.12)}
        .ka-compose{display:flex;align-items:flex-end;gap:8px}
        .ka-compose textarea{width:100%;min-height:18px;max-height:120px;resize:none;border:0;outline:0;background:transparent;color:var(--ka-body-text);font:inherit;font-size:12px;line-height:1.4;padding:0}
        .ka-compose textarea::placeholder{color:var(--ka-meta)}
        .ka-emoji-picker{position:absolute;left:10px;bottom:58px;display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:6px;width:212px;padding:10px;border:1px solid var(--ka-border);border-radius:14px;background:var(--ka-menu-bg);box-shadow:0 18px 38px rgba(0,0,0,.24);z-index:20}
        .ka-emoji-option{display:grid;place-items:center;width:100%;aspect-ratio:1;border:0;border-radius:10px;background:transparent;color:var(--ka-body-text);font-size:18px;cursor:pointer;transition:background-color 180ms ease,transform 180ms ease}
        .ka-emoji-option:hover{background:var(--ka-menu-hover);transform:translateY(-1px)}
        .ka-toolbar{display:flex;align-items:center;justify-content:space-between;margin-top:7px}
        .ka-tools{display:flex;align-items:center;gap:10px}
        .ka-icon-btn,.ka-send{display:grid;place-items:center;width:18px;height:18px;padding:0;border:0;background:transparent;color:var(--ka-body-text);cursor:pointer;transition:transform 180ms ease,opacity 180ms ease,color 180ms ease}
        .ka-icon-btn:hover,.ka-send:hover{opacity:.82;transform:scale(1.08)}
        .ka-icon-btn:active,.ka-send:active{transform:scale(.94)}
        .ka-send[disabled],.ka-icon-btn[disabled]{opacity:.45;cursor:default}
        .ka-note{margin:4px 0 0;font-size:10px;line-height:1.2;text-align:center;color:var(--ka-note)}
        .ka-note a{color:var(--ka-note);text-decoration:underline}
        @keyframes ka-bounce{0%,80%,100%{transform:translateY(0);opacity:.45}40%{transform:translateY(-4px);opacity:1}}
    </style>

    <div class="ka-shell" :data-theme="theme">
        <div class="ka-header">
            <div class="ka-brand">
                <img :src="currentLogoUrl()" :alt="companyName + ' logo'">
                <p x-text="agentName"></p>
            </div>

            <div class="ka-actions">
                <button type="button" @click="menuOpen = !menuOpen" aria-label="More actions">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <circle cx="3" cy="8" r="1" fill="currentColor"/>
                        <circle cx="8" cy="8" r="1" fill="currentColor"/>
                        <circle cx="13" cy="8" r="1" fill="currentColor"/>
                    </svg>
                </button>

                <button type="button" @click="closeWidget()" aria-label="Close chat">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                        <path d="M3 3L11 11M11 3L3 11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div
                class="ka-menu"
                x-cloak
                x-show="menuOpen"
                x-transition.opacity.scale.origin.top.right.duration.180ms
                @click.outside="menuOpen = false"
            >
                <button type="button" @click="toggleTheme()">
                    <span x-text="theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'"></span>
                </button>
                <button type="button" @click="switchView('help')">Help</button>
                <button type="button" @click="switchView('archives')">Messages</button>
                <button type="button" @click="downloadTranscript()">Download Transcript</button>
            </div>
        </div>

        <div class="ka-panels">
            <div
                class="ka-body"
                x-ref="scroller"
                x-show="view === 'conversation'"
                x-transition.opacity.duration.220ms
            >
                <div class="ka-stack">
                    <template x-for="message in messages" :key="message.message_id">
                        <div class="ka-msg" :class="message.role">
                            <div class="ka-bubble" x-text="message.content"></div>
                            <p class="ka-meta" x-show="message.role === 'assistant'" x-text="`${agentName} . ${relativeTime(message.created_at)}`"></p>
                        </div>
                    </template>

                    <div class="ka-msg assistant" x-cloak x-show="typing">
                        <div class="ka-bubble ka-typing" aria-label="Assistant is typing">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                    <div class="ka-spacer"></div>
                </div>
            </div>

            <div
                class="ka-body"
                x-cloak
                x-show="view === 'help'"
                x-transition.opacity.duration.220ms
            >
                <div class="ka-space-head">
                    <h2 class="ka-space-title">Help</h2>
                    <p class="ka-space-copy">Browse quick answers before starting a new question.</p>
                </div>

                <div class="ka-help-list" x-show="!helpArticle">
                    <template x-for="article in helpTopics" :key="article.id">
                        <button class="ka-help-card" type="button" @click="openHelpArticle(article.id)">
                            <h3 x-text="article.title"></h3>
                            <p x-text="article.excerpt"></p>
                        </button>
                    </template>
                </div>

                <div class="ka-help-detail" x-cloak x-show="helpArticle">
                    <button class="ka-help-back" type="button" @click="helpArticle = null">Back to topics</button>
                    <h3 x-text="helpArticle?.title"></h3>
                    <p x-text="helpArticle?.content"></p>
                </div>
            </div>

            <div
                class="ka-body"
                x-cloak
                x-show="view === 'archives'"
                x-transition.opacity.duration.220ms
            >
                <div class="ka-space-head">
                    <h2 class="ka-space-title">Messages</h2>
                    <p class="ka-space-copy">Saved chats appear here after the visitor closes the widget.</p>
                </div>

                <div class="ka-archive-list">
                    <button class="ka-archive-card is-primary" type="button" @click="startNewChat()">
                        <h3 class="ka-archive-title">Start new chat</h3>
                        <p class="ka-archive-copy">Open a fresh conversation without loading a saved one.</p>
                    </button>

                    <template x-for="chat in archives" :key="chat.sessionId">
                        <button class="ka-archive-card" type="button" @click="openArchivedChat(chat.sessionId)">
                            <h3 class="ka-archive-title" x-text="chat.title || 'Previous chat'"></h3>
                            <p class="ka-archive-copy" x-text="chat.preview || 'Open conversation'"></p>
                            <p class="ka-archive-time" x-text="archiveTime(chat.updatedAt)"></p>
                        </button>
                    </template>
                </div>

                <p class="ka-empty-copy" x-show="archives.length === 0">No saved chats yet. Closed conversations will appear here.</p>
            </div>
        </div>

        <div class="ka-footer" x-show="view === 'conversation'">
            <p class="ka-error" x-cloak x-show="error" x-text="error"></p>

            <form class="ka-compose-shell" @submit.prevent="submitMessage()">
                <div class="ka-emoji-picker" x-cloak x-show="emojiOpen" @click.outside="emojiOpen = false">
                    <template x-for="emoji in emojiOptions" :key="emoji">
                        <button class="ka-emoji-option" type="button" @click="appendEmoji(emoji)" x-text="emoji"></button>
                    </template>
                </div>

                <div class="ka-compose">
                    <textarea
                        x-ref="composer"
                        x-model="draft"
                        rows="1"
                        placeholder="Ask a question...."
                        @input="autoResize($event)"
                        @keydown.enter.prevent="handleComposerEnter($event)"
                    ></textarea>
                </div>

                <div class="ka-toolbar">
                    <div class="ka-tools">
                        <button class="ka-icon-btn" type="button" aria-label="Emoji" @click="toggleEmojiPicker()">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <circle cx="8" cy="8" r="6.2" stroke="currentColor" stroke-width="1"/>
                                <circle cx="5.75" cy="6.5" r=".8" fill="currentColor"/>
                                <circle cx="10.25" cy="6.5" r=".8" fill="currentColor"/>
                                <path d="M5.3 9.3C5.95 10.25 6.86 10.7 8 10.7C9.14 10.7 10.05 10.25 10.7 9.3" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                            </svg>
                        </button>

                        <button class="ka-icon-btn" type="button" aria-label="Voice input">
                            <svg width="13" height="17" viewBox="0 0 13 17" fill="none" aria-hidden="true">
                                <rect x="4" y="1" width="5" height="9" rx="2.5" stroke="currentColor" stroke-width="1"/>
                                <path d="M2 7.5C2 10.09 3.91 12 6.5 12C9.09 12 11 10.09 11 7.5" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                                <path d="M6.5 12V15.5M4.25 15.5H8.75" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <button class="ka-send" type="submit" :disabled="sending || !draft.trim()" aria-label="Send message">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                            <path d="M9 15.25V3.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                            <path d="M4.5 7.75L9 3.25L13.5 7.75" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </form>

            <p class="ka-note">By chatting with us, you agree to our <a href="#" @click.prevent>privacy policy</a></p>
        </div>
    </div>

    <script>
        function kAgentWidgetFrame(config) {
            return {
                widgetToken: config.widgetToken,
                agentName: config.agentName,
                companyName: config.companyName,
                welcomeMessage: config.welcomeMessage,
                fallbackMessage: config.fallbackMessage,
                createSessionUrl: config.createSessionUrl,
                sendMessageUrl: config.sendMessageUrl,
                storeLeadUrl: config.storeLeadUrl,
                bootstrapUrl: config.bootstrapUrl,
                lightLogoUrl: config.lightLogoUrl,
                darkLogoUrl: config.darkLogoUrl,
                reverbEnabled: config.reverbEnabled,
                helpTopics: config.helpTopics,
                storageKey: `k-agent-widget:${config.widgetToken}`,
                theme: 'dark',
                view: 'conversation',
                menuOpen: false,
                emojiOpen: false,
                emojiOptions: ['😀','😁','😂','😊','😍','😉','👍','👏','🙌','🔥','✨','🎉','💡','🙏','❤️','😎','🤝','🚀'],
                draft: '',
                error: '',
                sending: false,
                typing: false,
                sessionId: null,
                messages: [],
                archives: [],
                helpArticle: null,
                channelName: null,
                seenMessageIds: new Set(),

                async init() {
                    const persisted = this.readState();
                    this.theme = persisted.theme || 'dark';
                    this.archives = Array.isArray(persisted.archives) ? persisted.archives : [];
                    this.ensureWelcome();
                    this.$nextTick(() => this.scrollToBottom());
                },

                readState() {
                    try { return JSON.parse(localStorage.getItem(this.storageKey) || '{}'); } catch { return {}; }
                },

                saveState() {
                    try {
                        localStorage.setItem(this.storageKey, JSON.stringify({
                            archives: this.archives,
                            theme: this.theme,
                        }));
                    } catch {}
                },

                ensureWelcome() {
                    if (this.messages.length > 0) return;
                    this.messages = [{
                        message_id: 'welcome',
                        role: 'assistant',
                        content: this.welcomeMessage,
                        created_at: new Date().toISOString(),
                    }];
                },

                ensureWelcomeAtTop() {
                    const firstMessage = this.messages[0] || null;

                    if (firstMessage && firstMessage.message_id === 'welcome') {
                        firstMessage.content = this.welcomeMessage;
                        return;
                    }

                    this.messages = [{
                        message_id: 'welcome',
                        role: 'assistant',
                        content: this.welcomeMessage,
                        created_at: new Date().toISOString(),
                    }, ...this.messages.filter((message) => message.message_id !== 'welcome')];
                },

                async hydrateConversation(sessionId = null) {
                    const url = sessionId ? `${this.bootstrapUrl}?session_id=${encodeURIComponent(sessionId)}` : this.bootstrapUrl;
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const payload = await response.json();
                    if (payload.data?.agent?.welcome_message) {
                        this.welcomeMessage = payload.data.agent.welcome_message;
                    }
                    if (!payload.data?.session) return;

                    this.sessionId = payload.data.session.session_id;
                    this.messages = (payload.data.session.messages || []).map((message) => ({
                        message_id: message.message_id,
                        role: message.role,
                        content: message.content,
                        created_at: message.created_at,
                    }));

                    if (this.messages.length === 0) {
                        this.ensureWelcome();
                    } else {
                        this.ensureWelcomeAtTop();
                    }
                    this.messages.forEach((message) => this.seenMessageIds.add(message.message_id));
                    this.connectEcho();
                    this.saveState();
                    this.$nextTick(() => this.scrollToBottom());
                },

                async ensureSession() {
                    if (this.sessionId) return;

                    const payload = await this.postJson(this.createSessionUrl, {
                        widget_token: this.widgetToken,
                    });

                    this.sessionId = payload.data.session_id;
                    this.connectEcho();
                    this.saveState();
                },

                connectEcho() {
                    if (!this.reverbEnabled || !this.sessionId || !window.Echo) return;
                    const nextChannel = `widget-chat.${this.sessionId}`;
                    if (this.channelName === nextChannel) return;
                    if (this.channelName) window.Echo.leave(this.channelName);
                    this.channelName = nextChannel;
                    window.Echo.channel(this.channelName)
                        .listen('.widget.assistant-message', (payload) => {
                            this.typing = false;
                            this.appendMessage(payload.assistant_message);
                        });
                },

                appendMessage(message) {
                    if (!message || this.seenMessageIds.has(message.message_id)) return;
                    this.messages.push(message);
                    this.seenMessageIds.add(message.message_id);
                    this.$nextTick(() => this.scrollToBottom());
                },

                async submitMessage() {
                    const content = this.draft.trim();
                    if (!content || this.sending) return;

                    this.error = '';
                    this.sending = true;

                    try {
                        this.view = 'conversation';
                        this.menuOpen = false;
                        this.emojiOpen = false;
                        await this.ensureSession();

                        const localId = `local-${Date.now()}`;
                        this.messages.push({
                            message_id: localId,
                            role: 'user',
                            content,
                            created_at: new Date().toISOString(),
                        });
                        this.seenMessageIds.add(localId);
                        this.typing = true;
                        this.draft = '';
                        this.$nextTick(() => this.scrollToBottom());

                        await this.captureLeadFromMessage(content);

                        const payload = await this.postJson(this.sendMessageUrl, {
                            widget_token: this.widgetToken,
                            session_id: this.sessionId,
                            message: content,
                        });

                        const assistantMessage = payload.data.assistant_message;

                        setTimeout(() => {
                            if (!this.seenMessageIds.has(assistantMessage.message_id)) {
                                this.typing = false;
                                this.appendMessage(assistantMessage);
                            }
                        }, 1200);
                    } catch {
                        this.typing = false;
                        this.error = 'The message could not be sent.';
                    } finally {
                        this.sending = false;
                    }
                },

                closeWidget() {
                    if (this.sessionId) {
                        const preview = this.messages.find((message) => message.role === 'user')?.content || 'Previous chat';
                        this.archives = [{
                            sessionId: this.sessionId,
                            title: preview.slice(0, 48),
                            preview: this.messages.at(-1)?.content || '',
                            updatedAt: new Date().toISOString(),
                            transcript: this.messages.map((message) => ({
                                message_id: message.message_id,
                                role: message.role,
                                content: message.content,
                                created_at: message.created_at,
                            })),
                        }, ...this.archives.filter((chat) => chat.sessionId !== this.sessionId)].slice(0, 12);
                    }

                    this.sessionId = null;
                    this.messages = [];
                    this.ensureWelcome();
                    this.saveState();
                    window.parent.postMessage({ source: 'k-agent-widget', type: 'close' }, '*');
                },

                relativeTime(value) {
                    if (!value) return 'Just Now';
                    const minutes = Math.max(0, Math.round((Date.now() - new Date(value).getTime()) / 60000));
                    return minutes <= 1 ? 'Just Now' : `${minutes} min`;
                },

                async postJson(url, payload) {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        throw new Error(`Request failed with status ${response.status}`);
                    }

                    return await response.json();
                },

                async captureLeadFromMessage(message) {
                    const emailMatch = message.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);

                    if (!emailMatch || !this.sessionId) {
                        return;
                    }

                    try {
                        await this.postJson(this.storeLeadUrl, {
                            widget_token: this.widgetToken,
                            session_id: this.sessionId,
                            name: 'Website Visitor',
                            email: emailMatch[0],
                            notes: message,
                        });
                    } catch {}
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    this.menuOpen = false;
                    this.saveState();
                },

                toggleEmojiPicker() {
                    this.emojiOpen = !this.emojiOpen;
                },

                appendEmoji(emoji) {
                    this.draft = `${this.draft}${emoji}`;
                    this.emojiOpen = false;
                    this.$nextTick(() => {
                        const composer = this.$refs.composer;
                        if (!composer) return;
                        composer.focus();
                        composer.style.height = 'auto';
                        composer.style.height = `${Math.min(composer.scrollHeight, 120)}px`;
                    });
                },

                currentLogoUrl() {
                    return this.theme === 'light' ? this.lightLogoUrl : this.darkLogoUrl;
                },

                async switchView(nextView) {
                    this.view = nextView;
                    this.menuOpen = false;

                    if (nextView === 'conversation') {
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                async openHelpArticle(articleId) {
                    this.helpArticle = this.helpTopics.find((article) => article.id === articleId) || null;
                },

                openArchivedChat(sessionId) {
                    const archive = this.archives.find((chat) => chat.sessionId === sessionId);

                    if (!archive) {
                        return;
                    }

                    this.messages = (archive.transcript || []).map((message) => ({
                        message_id: message.message_id,
                        role: message.role,
                        content: message.content,
                        created_at: message.created_at,
                    }));
                    this.ensureWelcomeAtTop();
                    this.view = 'conversation';
                    this.$nextTick(() => this.scrollToBottom());
                },

                startNewChat() {
                    this.sessionId = null;
                    this.messages = [];
                    this.seenMessageIds = new Set();
                    this.typing = false;
                    this.error = '';
                    this.helpArticle = null;
                    if (this.channelName && window.Echo) {
                        window.Echo.leave(this.channelName);
                    }
                    this.channelName = null;
                    this.ensureWelcome();
                    this.view = 'conversation';
                    this.saveState();
                    this.$nextTick(() => this.scrollToBottom());
                },

                downloadTranscript() {
                    this.menuOpen = false;

                    const rows = this.messages
                        .filter((message) => message.message_id !== 'welcome')
                        .map((message) => {
                            const author = message.role === 'assistant' ? this.agentName : 'Visitor';
                            const stamp = message.created_at ? new Date(message.created_at).toLocaleString() : 'Now';
                            return `[${stamp}] ${author}: ${message.content}`;
                        });

                    if (rows.length === 0) {
                        this.error = 'No transcript is available yet.';
                        return;
                    }

                    const blob = new Blob([rows.join('\r\n\r\n')], { type: 'text/plain;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'chat-transcript.txt';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);
                },

                archiveTime(value) {
                    if (!value) return 'Saved recently';
                    return `Saved ${new Date(value).toLocaleDateString()}`;
                },

                handleComposerEnter(event) {
                    if (event.shiftKey) return;
                    this.submitMessage();
                },

                autoResize(event) {
                    const field = event.target;
                    field.style.height = 'auto';
                    field.style.height = `${Math.min(field.scrollHeight, 120)}px`;
                },

                scrollToBottom() {
                    const scroller = this.$refs.scroller;
                    if (!scroller) return;
                    scroller.scrollTop = scroller.scrollHeight;
                },
            };
        }
    </script>
</div>
