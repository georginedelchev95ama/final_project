<?php
require_once __DIR__ . '/../core/functions.php';
$pageTitle = 'Help — Maze Escape';
require_once __DIR__ . '/../core/header.php';
?>

<section class="card" style="max-width:700px;margin:0 auto">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
        <span style="font-size:1.8rem">🤖</span>
        <div>
            <h1 style="margin:0;font-size:1.4rem">Maze Escape Assistant</h1>
            <p class="muted" style="margin:2px 0 0">Ask me anything about how to play!</p>
        </div>
    </div>

    <div id="chat-messages" style="
        height:420px;
        overflow-y:auto;
        border:1px solid rgba(255,255,255,.1);
        border-radius:10px;
        padding:16px;
        margin:20px 0 14px;
        display:flex;
        flex-direction:column;
        gap:12px;
        background:rgba(0,0,0,.25);
    ">
        <div class="chat-bubble bot">
            👋 Hi! I'm the Maze Escape assistant. Ask me anything about gameplay, levels, scoring, keys, achievements — I'm here to help!
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <input type="text" id="chat-input"
               placeholder="Ask a question about Maze Escape…"
               autocomplete="off"
               style="flex:1"
               class="form-control" />
        <button id="chat-send" class="btn btn-primary" style="white-space:nowrap">Send</button>
    </div>
    <p class="muted" style="font-size:.78rem;margin-top:8px">
        This assistant only answers questions about Maze Escape gameplay.
    </p>
</section>

<style>
.chat-bubble {
    max-width: 82%;
    padding: 10px 14px;
    border-radius: 16px;
    line-height: 1.5;
    font-size: .92rem;
    word-wrap: break-word;
}
.chat-bubble.bot {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.chat-bubble.user {
    background: rgba(0, 200, 255, .15);
    border: 1px solid rgba(0,200,255,.25);
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
.chat-bubble.error {
    background: rgba(255,80,80,.12);
    border: 1px solid rgba(255,80,80,.25);
    align-self: flex-start;
    color: #ff9090;
    border-bottom-left-radius: 4px;
}
.chat-bubble.typing {
    opacity: .6;
    font-style: italic;
}
</style>

<script>
(function () {
    const ML_URL = <?php echo json_encode(rtrim(getenv('ML_API_URL') ?: '', '/')); ?>;
    const input  = document.getElementById('chat-input');
    const send   = document.getElementById('chat-send');
    const box    = document.getElementById('chat-messages');
    const history = [];

    function scrollBottom() {
        box.scrollTop = box.scrollHeight;
    }

    function addBubble(text, type) {
        const div = document.createElement('div');
        div.className = 'chat-bubble ' + type;
        div.textContent = text;
        box.appendChild(div);
        scrollBottom();
        return div;
    }

    async function sendMessage() {
        const msg = input.value.trim();
        if (!msg) return;

        input.value = '';
        send.disabled = true;
        addBubble(msg, 'user');

        const typing = addBubble('Thinking…', 'bot typing');

        try {
            const res = await fetch(ML_URL + '/api/chatbot', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message: msg, history: history}),
            });
            const data = await res.json();

            typing.remove();

            if (data.reply) {
                addBubble(data.reply, 'bot');
                history.push({role: 'user', text: msg});
                history.push({role: 'model', text: data.reply});
                // keep history manageable
                if (history.length > 20) history.splice(0, 2);
            } else {
                addBubble(data.error || 'Something went wrong. Please try again.', 'error');
            }
        } catch (e) {
            typing.remove();
            addBubble('Could not reach the assistant. Please try again.', 'error');
        }

        send.disabled = false;
        input.focus();
    }

    send.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
})();
</script>

<?php require_once __DIR__ . '/../core/footer.php'; ?>
