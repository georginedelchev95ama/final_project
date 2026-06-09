    </main>
    <footer class="footer">
        <p>Maze Escape</p>
    </footer>

    <!-- AI Assistant Widget (available to all) -->
    <div id="ai-widget" class="ai-widget">
        <button id="ai-toggle" class="chat-toggle ai-toggle-btn" title="Maze Assistant">🤖</button>
        <div id="ai-panel" class="chat-panel" style="display:none">
            <div class="chat-panel-header">
                <span>🤖 Maze Assistant</span>
                <button id="ai-close" class="chat-back-btn" style="margin-left:auto">✕</button>
            </div>
            <div id="ai-messages" class="chat-messages">
                <div class="chat-msg theirs">👋 Hi! Ask me anything about Maze Escape gameplay!</div>
            </div>
            <form id="ai-form" class="chat-form">
                <input id="ai-input" type="text" placeholder="Ask about the game…" autocomplete="off" maxlength="300" />
                <button type="submit">Send</button>
            </form>
        </div>
    </div>

    <?php if (is_logged_in()): ?>
    <div id="chat-widget" class="chat-widget">
        <button id="chat-toggle" class="chat-toggle" title="Messages">
            💬
            <span id="chat-badge" class="chat-badge" style="display:none">0</span>
        </button>
        <div id="chat-panel" class="chat-panel" style="display:none">
            <div id="chat-conv-view" class="chat-conv-view">
                <div class="chat-panel-header">
                    <span>Messages</span>
                </div>
                <div id="chat-conv-list" class="chat-conv-list">
                    <p class="chat-empty">No conversations yet.</p>
                </div>
            </div>
            <div id="chat-thread-view" class="chat-thread-view" style="display:none">
                <div class="chat-panel-header">
                    <button id="chat-back" class="chat-back-btn">← Back</button>
                    <span id="chat-thread-name"></span>
                    <span id="chat-thread-online" class="online-dot" style="display:none" title="Online"></span>
                </div>
                <div id="chat-messages" class="chat-messages"></div>
                <form id="chat-form" class="chat-form">
                    <input id="chat-input" type="text" placeholder="Type a message…" autocomplete="off" maxlength="500" />
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
    </div>
    <script src="<?php echo esc(app_url('js/chat.js')); ?>"></script>
    <?php endif; ?>

    <script>
    (function () {
        const ML_URL = <?php echo json_encode(rtrim(getenv('ML_API_URL') ?: '', '/')); ?>;
        const toggle  = document.getElementById('ai-toggle');
        const panel   = document.getElementById('ai-panel');
        const close   = document.getElementById('ai-close');
        const form    = document.getElementById('ai-form');
        const input   = document.getElementById('ai-input');
        const msgs    = document.getElementById('ai-messages');
        const history = [];

        function isChatPanelOpen() {
            const chatPanel = document.getElementById('chat-panel');
            return chatPanel && chatPanel.style.display !== 'none';
        }

        function updateAiPanelPosition() {
            if (isChatPanelOpen()) {
                panel.classList.add('shift-left');
            } else {
                panel.classList.remove('shift-left');
            }
        }

        toggle.addEventListener('click', function () {
            const isOpen = panel.style.display !== 'none';
            panel.style.display = isOpen ? 'none' : 'flex';
            if (!isOpen) {
                updateAiPanelPosition();
                input.focus();
            }
        });

        close.addEventListener('click', function () {
            panel.style.display = 'none';
        });

        // Watch for messages panel open/close to reposition AI panel
        const chatToggle = document.getElementById('chat-toggle');
        if (chatToggle) {
            chatToggle.addEventListener('click', function () {
                setTimeout(updateAiPanelPosition, 50);
            });
        }

        function addMsg(text, type) {
            const div = document.createElement('div');
            div.className = 'chat-msg ' + type;
            div.textContent = text;
            msgs.appendChild(div);
            msgs.scrollTop = msgs.scrollHeight;
            return div;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const msg = input.value.trim();
            if (!msg) return;
            input.value = '';
            form.querySelector('button').disabled = true;

            addMsg(msg, 'mine');
            const typing = addMsg('Thinking…', 'theirs ai-msg-typing');

            try {
                const res = await fetch(ML_URL + '/api/chatbot', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: msg, history: history}),
                });
                const data = await res.json();
                typing.remove();
                const reply = data.reply || data.error || 'Something went wrong.';
                addMsg(reply, 'theirs');
                history.push({role: 'user', text: msg});
                history.push({role: 'model', text: reply});
                if (history.length > 20) history.splice(0, 2);
            } catch (e) {
                typing.remove();
                addMsg('Could not reach the assistant. Try again.', 'theirs');
            }

            form.querySelector('button').disabled = false;
            input.focus();
        });
    })();
    </script>
</div>
</body>
</html>
