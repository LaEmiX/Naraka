'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const config = window.NarakaChatConfig || {};

    const idChat = config.idChat || 0;
    const idCharacter = config.idCharacter || 0;
    const isStaff = config.isStaff || false;
    let isInSceneHere = config.isInSceneHere || false;
    let isLockedElsewhere = config.isLockedElsewhere || false;

    const draftKey = 'naraka_chat_draft_' + idChat + '_' + idCharacter;

    const messagesBox = document.getElementById('chat-messages');

    const writeModal = document.getElementById('chat-write-modal');
    const writeContent = document.getElementById('chat-write-content');
    const writeDragHandle = document.getElementById('chat-write-drag-handle');
    const editModal = document.getElementById('chat-edit-modal');
    const infoModal = document.getElementById('chat-info-modal');
    const invitesModal = document.getElementById('chat-invites-modal');

    const btnSceneEnter = document.getElementById('btn-scene-enter');
    const btnSceneLeave = document.getElementById('btn-scene-leave');
    const btnWrite = document.getElementById('btn-write');
    const btnInfo = document.getElementById('btn-info');
    const btnInvites = document.getElementById('btn-invites');

    const writeClose = document.getElementById('chat-write-close');
    const writeMinimize = document.getElementById('chat-write-minimize');
    const writeRestore = document.getElementById('chat-write-restore');
    const editClose = document.getElementById('chat-edit-close');
    const infoClose = document.getElementById('chat-info-close');
    const invitesClose = document.getElementById('chat-invites-close');

    const typeSelect = document.getElementById('chat-type');
    const tagRow = document.getElementById('chat-tag-row');
    const whisperRow = document.getElementById('chat-whisper-row');

    const textarea = document.getElementById('chat-text');
    const counter = document.getElementById('chat-counter');
    const sendButton = document.getElementById('chat-send');

    const editIdInput = document.getElementById('chat-edit-id');
    const editTypeInput = document.getElementById('chat-edit-type');
    const editTagRow = document.getElementById('chat-edit-tag-row');
    const editTagInput = document.getElementById('chat-edit-tag-input');
    const editTextarea = document.getElementById('chat-edit-text');
    const editCounter = document.getElementById('chat-edit-counter');
    const editSaveButton = document.getElementById('chat-edit-save');

    const inviteNameInput = document.getElementById('chat-invite-name');
    const inviteAddButton = document.getElementById('chat-invite-add');
    const inviteFeedback = document.getElementById('chat-invite-feedback');
    const invitesList = document.getElementById('chat-invites-list');

    if (!idChat || !idCharacter || !messagesBox) {
        return;
    }

    const minAction = parseInt(textarea.dataset.minAction, 10);
    const maxAction = parseInt(textarea.dataset.maxAction, 10);

    let lastId = 0;
    let isFetching = false;
    let isDraggingWriteModal = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let modalStartLeft = 0;
    let modalStartTop = 0;
    let hasWriteModalPosition = false;

    document.querySelectorAll('#chat-messages .chat-message').forEach(function (message) {
        const currentId = parseInt(message.dataset.id, 10);

        if (!Number.isNaN(currentId) && currentId > lastId) {
            lastId = currentId;
        }
    });

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseJsonResponse(response) {
        return response.text().then(function (text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                throw new Error(text);
            }
        });
    }

    function formatClientChatText(value) {
        let text = escapeHtml(value);

        text = text.replace(/\[(.*?)\]/g, '&lt;$1&gt;');
        text = text.replace(/\&lt;(.*?)\&gt;/g, '<span class="chat-action-text">&lt;$1&gt;</span>');
        text = text.replace(/\n/g, '<br>');

        return text;
    }

    function isNearBottom() {
        return messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight < 80;
    }

    function scrollToBottom() {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function openModal(modal) {
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function hideWriteRestore() {
        writeRestore.classList.remove('is-visible');
    }

    function showWriteRestore() {
        writeRestore.classList.add('is-visible');
    }

    function updateSceneButtons() {
        if (btnSceneEnter) {
            btnSceneEnter.disabled = isInSceneHere || isLockedElsewhere;
        }

        if (btnSceneLeave) {
            btnSceneLeave.disabled = !isInSceneHere;
        }

        if (btnWrite) {
            btnWrite.disabled = !isStaff && (!isInSceneHere || isLockedElsewhere);
        }
    }

    function removeCurrentEditButtons() {
        document.querySelectorAll('#chat-messages .chat-edit-btn').forEach(function (button) {
            button.remove();
        });
    }

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

    function lockWriteModalPosition() {
        if (!writeContent || hasWriteModalPosition) {
            return;
        }

        const rect = writeContent.getBoundingClientRect();

        writeContent.style.position = 'fixed';
        writeContent.style.left = rect.left + 'px';
        writeContent.style.top = rect.top + 'px';
        writeContent.style.margin = '0';
        writeContent.style.transform = 'none';

        hasWriteModalPosition = true;
    }

    function clampWriteModalPosition(left, top) {
        const rect = writeContent.getBoundingClientRect();
        const maxLeft = Math.max(0, window.innerWidth - rect.width);
        const maxTop = Math.max(0, window.innerHeight - rect.height);

        return {
            left: Math.min(Math.max(0, left), maxLeft),
            top: Math.min(Math.max(0, top), maxTop)
        };
    }

    function startWriteModalDrag(event) {
        if (!writeContent) {
            return;
        }

        const pointer = event.touches ? event.touches[0] : event;

        lockWriteModalPosition();

        const rect = writeContent.getBoundingClientRect();

        isDraggingWriteModal = true;
        dragStartX = pointer.clientX;
        dragStartY = pointer.clientY;
        modalStartLeft = rect.left;
        modalStartTop = rect.top;

        document.body.classList.add('is-chat-modal-dragging');

        event.preventDefault();
    }

    function moveWriteModalDrag(event) {
        if (!isDraggingWriteModal || !writeContent) {
            return;
        }

        const pointer = event.touches ? event.touches[0] : event;

        const nextLeft = modalStartLeft + pointer.clientX - dragStartX;
        const nextTop = modalStartTop + pointer.clientY - dragStartY;
        const clamped = clampWriteModalPosition(nextLeft, nextTop);

        writeContent.style.left = clamped.left + 'px';
        writeContent.style.top = clamped.top + 'px';

        event.preventDefault();
    }

    function endWriteModalDrag() {
        isDraggingWriteModal = false;
        document.body.classList.remove('is-chat-modal-dragging');
    }

    function updateWriteMode() {
        const type = typeSelect.value;

        tagRow.style.display = type === 'azione' ? 'block' : 'none';
        whisperRow.style.display = type === 'sussurro' ? 'block' : 'none';

        updateCounter();
    }

    function updateCounter() {
        const type = typeSelect.value;
        let length = textarea.value.length;

        if (type !== 'azione') {
            counter.textContent = '';
            sendButton.disabled = textarea.value.trim().length === 0;
            return;
        }

        if (length > maxAction) {
            textarea.value = textarea.value.substring(0, maxAction);
            length = textarea.value.length;
        }

        counter.textContent = length + ' / ' + maxAction + ' caratteri. Minimo: ' + minAction;
        sendButton.disabled = textarea.value.trim().length < minAction;
    }

    function updateEditCounter() {
        const type = editTypeInput.value;
        let length = editTextarea.value.length;

        if (type !== 'azione') {
            editCounter.textContent = '';
            editSaveButton.disabled = editTextarea.value.trim().length === 0;
            return;
        }

        if (length > maxAction) {
            editTextarea.value = editTextarea.value.substring(0, maxAction);
            length = editTextarea.value.length;
        }

        editCounter.textContent = length + ' / ' + maxAction + ' caratteri. Minimo: ' + minAction;
        editSaveButton.disabled = editTextarea.value.trim().length < minAction;
    }

    function appendMessage(message) {
        if (document.querySelector('.chat-message[data-id="' + message.id_message + '"]')) {
            return;
        }

        const shouldScroll = isNearBottom();

        const row = document.createElement('div');
        row.className = 'chat-message chat-message-' + message.message_type;
        row.dataset.id = message.id_message;
        row.dataset.type = message.message_type;
        row.dataset.content = message.content || '';
        row.dataset.context = message.context || '';
        row.dataset.targetName = message.target_name || '';
        row.dataset.createdAt = message.created_at || '';
        row.innerHTML = message.html;

        messagesBox.appendChild(row);

        if (message.id_message > lastId) {
            lastId = message.id_message;
        }

        if (shouldScroll) {
            scrollToBottom();
        }
    }

    function fetchMessages() {
        if (isFetching) {
            return;
        }

        isFetching = true;

        fetch('/actions/chat_fetch.php?id_chat=' + encodeURIComponent(idChat) + '&last_id=' + encodeURIComponent(lastId), {
            cache: 'no-store'
        })
        .then(function (response) {
            return response.json();
        })
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
            isFetching = false;
        });
    }

    function refreshEditedRow(idMessage, content, context) {
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
                text.innerHTML = formatClientChatText(content);
            }

            return;
        }

        if (type === 'master') {
            const text = row.querySelector('.chat-text');

            if (text) {
                text.innerHTML = formatClientChatText(content);
            }

            return;
        }

        if (type === 'sussurro') {
            const text = row.querySelector('.chat-text');
            const targetName = row.dataset.targetName || '';

            if (text) {
                text.innerHTML = 'Sussurri a ' + escapeHtml(targetName) + ': ' + formatClientChatText(content);
            }

            return;
        }

        if (type === 'offgame') {
            const text = row.querySelector('.chat-text');

            if (text) {
                text.innerHTML = formatClientChatText(content);
            }
        }
    }

    function enterScene() {
        if (!btnSceneEnter) {
            return;
        }

        btnSceneEnter.disabled = true;

        fetch('/actions/chat_scene_enter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: idChat
            })
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                isInSceneHere = true;
                isLockedElsewhere = false;
                updateSceneButtons();
                return;
            }

            updateSceneButtons();
        })
        .catch(function () {
            updateSceneButtons();
        });
    }

    function leaveScene() {
        if (!btnSceneLeave) {
            return;
        }

        btnSceneLeave.disabled = true;

        fetch('/actions/chat_scene_leave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: idChat
            })
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                isInSceneHere = false;
                isLockedElsewhere = false;
                hideWriteRestore();
                closeModal(writeModal);
                updateSceneButtons();
                return;
            }

            updateSceneButtons();
        })
        .catch(function () {
            updateSceneButtons();
        });
    }

    function renderInvites(invites) {
        if (!invitesList) {
            return;
        }

        if (!Array.isArray(invites) || invites.length === 0) {
            invitesList.innerHTML = '<div class="chat-invite-empty">Nessun invitato attivo.</div>';
            return;
        }

        let html = '';

        invites.forEach(function (invite) {
            html += ''
                + '<div class="chat-invite-row">'
                + '<span class="chat-invite-name">' + escapeHtml(invite.character_name || '') + '</span>'
                + '<button type="button" class="chat-btn chat-btn-danger chat-invite-remove" data-character-id="' + escapeHtml(invite.id_character || '') + '">Rimuovi</button>'
                + '</div>';
        });

        invitesList.innerHTML = html;
    }

    function setInviteFeedback(message) {
        if (inviteFeedback) {
            inviteFeedback.textContent = message || '';
        }
    }

    function loadInvites() {
        if (!invitesList) {
            return;
        }

        setInviteFeedback('');

        fetch('/actions/chat_invite_list.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: idChat
            })
        })
        .then(parseJsonResponse)
        .then(function (data) {
            if (!data.success) {
                renderInvites([]);
                setInviteFeedback(data.message || 'Impossibile caricare gli inviti.');
                return;
            }

            renderInvites(data.invites || []);
        })
        .catch(function (error) {
            renderInvites([]);
            setInviteFeedback('Errore durante il caricamento: ' + error.message);
        });
    }

    function addInvite() {
        if (!inviteNameInput || !inviteAddButton) {
            return;
        }

        const name = inviteNameInput.value.trim();

        if (name === '') {
            setInviteFeedback('Inserisci un nome personaggio.');
            return;
        }

        inviteAddButton.disabled = true;
        setInviteFeedback('');

        fetch('/actions/chat_invite_add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: idChat,
                character_name: name
            })
        })
        .then(parseJsonResponse)
        .then(function (data) {
            setInviteFeedback(data.message || '');

            if (data.success) {
                inviteNameInput.value = '';
                loadInvites();
            }
        })
        .catch(function (error) {
            setInviteFeedback('Errore durante l’invito: ' + error.message);
        })
        .finally(function () {
            inviteAddButton.disabled = false;
        });
    }

    function removeInvite(idTargetCharacter) {
        if (!idTargetCharacter) {
            return;
        }

        setInviteFeedback('');

        fetch('/actions/chat_invite_remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: idChat,
                id_character: idTargetCharacter
            })
        })
        .then(parseJsonResponse)
        .then(function (data) {
            setInviteFeedback(data.message || '');

            if (data.success) {
                loadInvites();
            }
        })
        .catch(function (error) {
            setInviteFeedback('Errore durante la rimozione: ' + error.message);
        });
    }

    if (btnSceneEnter) {
        btnSceneEnter.addEventListener('click', enterScene);
    }

    if (btnSceneLeave) {
        btnSceneLeave.addEventListener('click', leaveScene);
    }

    btnWrite.addEventListener('click', function () {
        if (!isStaff && (!isInSceneHere || isLockedElsewhere)) {
            return;
        }

        hideWriteRestore();
        openModal(writeModal);
        textarea.focus();
        updateWriteMode();
    });

    btnInfo.addEventListener('click', function () {
        openModal(infoModal);
    });

    if (btnInvites && invitesModal) {
        btnInvites.addEventListener('click', function () {
            openModal(invitesModal);
            loadInvites();

            if (inviteNameInput) {
                inviteNameInput.focus();
            }
        });
    }

    writeClose.addEventListener('click', function () {
        hideWriteRestore();
        closeModal(writeModal);
    });

    writeMinimize.addEventListener('click', function () {
        closeModal(writeModal);
        showWriteRestore();
    });

    writeRestore.addEventListener('click', function () {
        if (!isStaff && (!isInSceneHere || isLockedElsewhere)) {
            return;
        }

        hideWriteRestore();
        openModal(writeModal);
        textarea.focus();
        updateWriteMode();
    });

    editClose.addEventListener('click', function () {
        closeModal(editModal);
    });

    infoClose.addEventListener('click', function () {
        closeModal(infoModal);
    });

    if (invitesClose && invitesModal) {
        invitesClose.addEventListener('click', function () {
            closeModal(invitesModal);
        });
    }

    writeModal.addEventListener('click', function (event) {
        if (event.target === writeModal) {
            hideWriteRestore();
            closeModal(writeModal);
        }
    });

    editModal.addEventListener('click', function (event) {
        if (event.target === editModal) {
            closeModal(editModal);
        }
    });

    infoModal.addEventListener('click', function (event) {
        if (event.target === infoModal) {
            closeModal(infoModal);
        }
    });

    if (invitesModal) {
        invitesModal.addEventListener('click', function (event) {
            if (event.target === invitesModal) {
                closeModal(invitesModal);
            }
        });
    }

    if (writeDragHandle) {
        writeDragHandle.addEventListener('mousedown', startWriteModalDrag);
        writeDragHandle.addEventListener('touchstart', startWriteModalDrag, { passive: false });
    }

    document.addEventListener('mousemove', moveWriteModalDrag);
    document.addEventListener('mouseup', endWriteModalDrag);
    document.addEventListener('touchmove', moveWriteModalDrag, { passive: false });
    document.addEventListener('touchend', endWriteModalDrag);

    window.addEventListener('resize', function () {
        if (!hasWriteModalPosition || !writeContent) {
            return;
        }

        const rect = writeContent.getBoundingClientRect();
        const clamped = clampWriteModalPosition(rect.left, rect.top);

        writeContent.style.left = clamped.left + 'px';
        writeContent.style.top = clamped.top + 'px';
    });

    typeSelect.addEventListener('change', function () {
        updateWriteMode();
    });

    textarea.addEventListener('input', function () {
        localStorage.setItem(draftKey, textarea.value);
        updateCounter();
    });

    editTextarea.addEventListener('input', function () {
        updateEditCounter();
    });

    if (inviteAddButton) {
        inviteAddButton.addEventListener('click', addInvite);
    }

    if (inviteNameInput) {
        inviteNameInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addInvite();
            }
        });
    }

    if (invitesList) {
        invitesList.addEventListener('click', function (event) {
            const button = event.target.closest('.chat-invite-remove');

            if (!button) {
                return;
            }

            const idTargetCharacter = parseInt(button.dataset.characterId, 10);

            if (!Number.isNaN(idTargetCharacter) && idTargetCharacter > 0) {
                removeInvite(idTargetCharacter);
            }
        });
    }

    const savedDraft = localStorage.getItem(draftKey);

    if (savedDraft) {
        textarea.value = savedDraft;
    }

    updateWriteMode();
    updateSceneButtons();
    cleanupOldMessages();
    scrollToBottom();

    messagesBox.addEventListener('click', function (event) {
        const button = event.target.closest('.chat-edit-btn');

        if (!button) {
            return;
        }

        const row = button.closest('.chat-message');

        if (!row) {
            return;
        }

        const type = row.dataset.type || '';
        const idMessage = row.dataset.id || '';
        const rawContent = row.dataset.content || '';
        const rawContext = row.dataset.context || '';

        editIdInput.value = idMessage;
        editTypeInput.value = type;
        editTextarea.value = rawContent;
        editTagInput.value = rawContext;

        editTagRow.style.display = type === 'azione' ? 'block' : 'none';

        openModal(editModal);
        editTextarea.focus();
        updateEditCounter();
    });

    sendButton.addEventListener('click', function () {
        const payload = {
            id_chat: idChat,
            message_type: typeSelect.value,
            context: document.getElementById('chat-tag-input').value,
            target: document.getElementById('chat-whisper-target').value,
            content: textarea.value
        };

        sendButton.disabled = true;

        fetch('/actions/chat_send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify(payload)
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                removeCurrentEditButtons();
                textarea.value = '';
                localStorage.removeItem(draftKey);
                updateCounter();
                hideWriteRestore();
                closeModal(writeModal);
                fetchMessages();
                return;
            }

            updateCounter();
        })
        .catch(function () {
            updateCounter();
        });
    });

    editSaveButton.addEventListener('click', function () {
        const idMessage = parseInt(editIdInput.value, 10);
        const type = editTypeInput.value;
        const content = editTextarea.value;
        const context = type === 'azione' ? editTagInput.value : '';

        if (!idMessage) {
            return;
        }

        editSaveButton.disabled = true;

        fetch('/actions/chat_edit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_message: idMessage,
                content: content,
                context: context
            })
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                refreshEditedRow(idMessage, content, context);
                closeModal(editModal);
            }
        })
        .catch(function () {})
        .finally(function () {
            updateEditCounter();
        });
    });

    fetchMessages();
    setInterval(fetchMessages, 1500);
    setInterval(cleanupOldMessages, 60000);
});

// by LaEmiX