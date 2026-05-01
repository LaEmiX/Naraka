'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.initMessages = function () {
    const state = window.NarakaChat.state;
    const dom = window.NarakaChat.dom;

    if (!state.idChat || !state.idCharacter || !dom.messagesBox) {
        return;
    }

    /* =========================
       INIT LAST ID
    ========================= */

    document.querySelectorAll('#chat-messages .chat-message').forEach(function (message) {
        const currentId = parseInt(message.dataset.id, 10);

        if (!Number.isNaN(currentId) && currentId > state.lastId) {
            state.lastId = currentId;
        }
    });

    /* =========================
       CLEANUP
    ========================= */

    function cleanupOldMessages() {
        const limit = 3 * 60 * 60 * 1000;
        const now = Date.now();

        document.querySelectorAll('#chat-messages .chat-message').forEach(function (row) {
            const createdAt = row.dataset.createdAt;

            if (!createdAt) {
                return;
            }

            const messageTime = new Date(createdAt.replace(' ', 'T')).getTime();

            if (Number.isNaN(messageTime)) {
                return;
            }

            if (now - messageTime > limit) {
                row.remove();
            }
        });
    }

    /* =========================
       APPEND
    ========================= */

    function appendMessage(message) {
        if (document.querySelector('.chat-message[data-id="' + message.id_message + '"]')) {
            return;
        }

        const shouldScroll = window.NarakaChat.isNearBottom(dom.messagesBox);

        const row = document.createElement('div');
        row.className = 'chat-message chat-message-' + message.message_type;
        row.dataset.id = message.id_message;
        row.dataset.type = message.message_type;
        row.dataset.content = message.content || '';
        row.dataset.context = message.context || '';
        row.dataset.targetName = message.target_name || '';
        row.dataset.createdAt = message.created_at || '';
        row.innerHTML = message.html;

        dom.messagesBox.appendChild(row);

        if (message.id_message > state.lastId) {
            state.lastId = message.id_message;
        }

        if (shouldScroll) {
            window.NarakaChat.scrollToBottom(dom.messagesBox);
        }
    }

    /* =========================
       FETCH
    ========================= */

    function fetchMessages() {
        if (state.isFetching) {
            return;
        }

        state.isFetching = true;

        fetch('/actions/chat_fetch.php?id_chat=' + encodeURIComponent(state.idChat) + '&last_id=' + encodeURIComponent(state.lastId), {
            cache: 'no-store'
        })
        .then(window.NarakaChat.parseJsonResponse)
        .then(function (messages) {
            if (!Array.isArray(messages)) {
                return;
            }

            messages.forEach(function (message) {
                appendMessage(message);
            });

            cleanupOldMessages();
        })
        .catch(function () {})
        .finally(function () {
            state.isFetching = false;
        });
    }

    window.NarakaChat.fetchMessages = fetchMessages;

    /* =========================
       EDIT REFRESH
    ========================= */

    window.NarakaChat.refreshEditedRow = function (idMessage, content, context) {
        const row = document.querySelector('.chat-message[data-id="' + idMessage + '"]');

        if (!row) {
            return;
        }

        const type = row.dataset.type;

        row.dataset.content = content;
        row.dataset.context = context;

        if (type === 'azione') {
            let tag = row.querySelector('.chat-tag');
            const text = row.querySelector('.chat-text');

            if (context.trim() !== '') {
                if (!tag) {
                    tag = document.createElement('span');
                    tag.className = 'chat-tag';

                    if (text) {
                        row.insertBefore(tag, text);
                    } else {
                        row.appendChild(tag);
                    }
                }

                tag.textContent = '[' + context.trim() + ']';
            } else if (tag) {
                tag.remove();
            }

            if (text) {
                text.innerHTML = window.NarakaChat.formatClientChatText(content);
            }

            return;
        }

        if (type === 'master') {
            const text = row.querySelector('.chat-text');

            if (text) {
                text.innerHTML = window.NarakaChat.formatClientChatText(content);
            }

            return;
        }

        if (type === 'sussurro') {
            const text = row.querySelector('.chat-text');
            const targetName = row.dataset.targetName || '';

            if (text) {
                text.innerHTML = 'Sussurri a ' + window.NarakaChat.escapeHtml(targetName) + ': ' + window.NarakaChat.formatClientChatText(content);
            }

            return;
        }

        if (type === 'offgame') {
            const text = row.querySelector('.chat-text');

            if (text) {
                text.innerHTML = window.NarakaChat.formatClientChatText(content);
            }
        }
    };

    /* =========================
       START LOOP
    ========================= */

    fetchMessages();
    setInterval(fetchMessages, 1500);
    setInterval(cleanupOldMessages, 60000);
};

// by LaEmiX