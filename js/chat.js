(function () {
    'use strict';

    if (!window.CURRENT_USERNAME) return;

    const ROOT        = window.APP_ROOT_URL || '';
    const ME          = window.CURRENT_USERNAME;

    const widget      = document.getElementById('chat-widget');
    const toggleBtn   = document.getElementById('chat-toggle');
    const badge       = document.getElementById('chat-badge');
    const panel       = document.getElementById('chat-panel');

    const convView    = document.getElementById('chat-conv-view');
    const convList    = document.getElementById('chat-conv-list');

    const threadView  = document.getElementById('chat-thread-view');
    const threadName  = document.getElementById('chat-thread-name');
    const threadOnline= document.getElementById('chat-thread-online');
    const msgContainer= document.getElementById('chat-messages');
    const chatForm    = document.getElementById('chat-form');
    const chatInput   = document.getElementById('chat-input');
    const backBtn     = document.getElementById('chat-back');

    let panelOpen     = false;
    let activeThread  = null;
    let lastMsgId     = 0;
    let pollTimer     = null;
    let bgPollTimer   = null;

    // ── Helpers ──────────────────────────────────────────────────

    function fmt(ts) {
        const d = new Date(ts.replace(' ', 'T'));
        const now = new Date();
        const diff = now - d;
        if (diff < 60000)   return 'just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000)return d.getHours() + ':' + String(d.getMinutes()).padStart(2,'0');
        return d.getDate() + ' ' + d.toLocaleString('default',{month:'short'});
    }

    function get(url) {
        return fetch(url).then(r => r.json()).catch(() => null);
    }

    function post(url, data) {
        const body = new FormData();
        Object.entries(data).forEach(([k, v]) => body.append(k, v));
        return fetch(url, { method: 'POST', body }).then(r => r.json()).catch(() => null);
    }

    // ── Panel toggle ─────────────────────────────────────────────

    toggleBtn.addEventListener('click', function () {
        panelOpen = !panelOpen;
        panel.style.display = panelOpen ? 'flex' : 'none';
        if (panelOpen) {
            showConvView();
            loadConversations();
        } else {
            stopThreadPoll();
        }
    });

    // ── Conversation list ─────────────────────────────────────────

    function showConvView() {
        convView.style.display = 'flex';
        threadView.style.display = 'none';
        stopThreadPoll();
        activeThread = null;
    }

    function showThreadView(username) {
        convView.style.display = 'none';
        threadView.style.display = 'flex';
        threadName.textContent = username;
        msgContainer.innerHTML = '';
        lastMsgId = 0;
        activeThread = username;
        fetchMessages();
        startThreadPoll();
    }

    backBtn.addEventListener('click', function () {
        showConvView();
        loadConversations();
    });

    function loadConversations() {
        get(ROOT + '/game/get_conversations.php').then(data => {
            if (!data) return;
            renderConversations(data);
            updateBadge(data.reduce((s, c) => s + c.unread_count, 0));
        });
    }

    function renderConversations(list) {
        if (!list.length) {
            convList.innerHTML = '<p class="chat-empty">No conversations yet.</p>';
            return;
        }
        convList.innerHTML = list.map(c => `
            <div class="chat-conv-item" data-user="${esc(c.username)}">
                <div style="flex:1;min-width:0">
                    <div class="chat-conv-name">
                        ${c.online ? '<span class="online-dot" style="margin-left:0;margin-right:2px"></span>' : ''}
                        ${esc(c.username)}
                    </div>
                    <div class="chat-conv-preview">${esc(c.last_message)}</div>
                </div>
                ${c.unread_count > 0
                    ? `<span class="chat-unread-badge">${c.unread_count}</span>`
                    : ''}
            </div>
        `).join('');

        convList.querySelectorAll('.chat-conv-item').forEach(el => {
            el.addEventListener('click', function () {
                showThreadView(this.dataset.user);
            });
        });
    }

    function esc(str) {
        return String(str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

    // ── Message thread ────────────────────────────────────────────

    function fetchMessages() {
        if (!activeThread) return;
        get(ROOT + '/game/get_messages.php?with=' + encodeURIComponent(activeThread) + '&after=' + lastMsgId)
            .then(msgs => {
                if (!msgs || !msgs.length) return;
                const atBottom = msgContainer.scrollHeight - msgContainer.scrollTop <= msgContainer.clientHeight + 40;
                msgs.forEach(m => {
                    lastMsgId = Math.max(lastMsgId, m.id);
                    appendMessage(m);
                });
                if (atBottom) msgContainer.scrollTop = msgContainer.scrollHeight;

                // update online dot
                get(ROOT + '/game/search_users.php?q=' + encodeURIComponent(activeThread)).then(users => {
                    if (users && users.includes(activeThread)) {
                        get(ROOT + '/game/get_conversations.php').then(convs => {
                            if (!convs) return;
                            const c = convs.find(x => x.username === activeThread);
                            if (c) {
                                threadOnline.style.display = c.online ? 'inline-block' : 'none';
                            }
                        });
                    }
                });
            });
    }

    function appendMessage(m) {
        const div = document.createElement('div');
        div.className = 'chat-msg ' + (m.sender === ME ? 'mine' : 'theirs');
        div.innerHTML = `<div>${esc(m.body)}</div><div class="chat-msg-meta">${fmt(m.created_at)}</div>`;
        msgContainer.appendChild(div);
    }

    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const body = chatInput.value.trim();
        if (!body || !activeThread) return;
        chatInput.value = '';
        post(ROOT + '/game/send_message.php', { to: activeThread, body }).then(res => {
            if (res && res.ok) {
                lastMsgId = Math.max(lastMsgId, res.id);
                appendMessage({ id: res.id, sender: ME, body, created_at: new Date().toISOString().replace('T',' ').slice(0,19) });
                msgContainer.scrollTop = msgContainer.scrollHeight;
            }
        });
    });

    // ── Polling ───────────────────────────────────────────────────

    function startThreadPoll() {
        stopThreadPoll();
        pollTimer = setInterval(fetchMessages, 4000);
    }

    function stopThreadPoll() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function startBgPoll() {
        bgPollTimer = setInterval(function () {
            if (panelOpen) return;
            get(ROOT + '/game/get_conversations.php').then(data => {
                if (!data) return;
                const total = data.reduce((s, c) => s + c.unread_count, 0);
                updateBadge(total);
            });
        }, 10000);
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // ── Public API (used by Message button on profile) ────────────

    window.chatOpenWith = function (username) {
        panelOpen = true;
        panel.style.display = 'flex';
        showThreadView(username);
    };

    // ── Boot ──────────────────────────────────────────────────────

    startBgPoll();

})();
