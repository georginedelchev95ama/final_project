    </main>
    <footer class="footer">
        <p>Maze Escape</p>
    </footer>

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
</div>
</body>
</html>
