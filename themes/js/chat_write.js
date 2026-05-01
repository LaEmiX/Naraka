'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.initWrite = function () {
    const state = window.NarakaChat.state;
    const dom = window.NarakaChat.dom;

    if (!dom.textarea || !dom.typeSelect || !dom.sendButton) {
        return;
    }

    const minAction = parseInt(dom.textarea.dataset.minAction, 10);
    const maxAction = parseInt(dom.textarea.dataset.maxAction, 10);

    window.NarakaChat.hideWriteRestore = function () {
        if (dom.writeRestore) {
            dom.writeRestore.classList.remove('is-visible');
        }
    };

    window.NarakaChat.showWriteRestore = function () {
        if (dom.writeRestore) {
            dom.writeRestore.classList.add('is-visible');
        }
    };

    function updateCounter() {
        const type = dom.typeSelect.value;
        let length = dom.textarea.value.length;

        if (type !== 'azione') {
            dom.counter.textContent = '';
            dom.sendButton.disabled = dom.textarea.value.trim().length === 0;
            return;
        }

        if (length > maxAction) {
            dom.textarea.value = dom.textarea.value.substring(0, maxAction);
            length = dom.textarea.value.length;
        }

        dom.counter.textContent = length + ' / ' + maxAction + ' caratteri. Minimo: ' + minAction;
        dom.sendButton.disabled = dom.textarea.value.trim().length < minAction;
    }

    function updateWriteMode() {
        const type = dom.typeSelect.value;

        if (dom.tagRow) {
            dom.tagRow.style.display = type === 'azione' ? 'block' : 'none';
        }

        if (dom.whisperRow) {
            dom.whisperRow.style.display = type === 'sussurro' ? 'block' : 'none';
        }

        updateCounter();
    }

    function removeCurrentEditButtons() {
        document.querySelectorAll('#chat-messages .chat-edit-btn').forEach(function (button) {
            button.remove();
        });
    }

    if (dom.btnWrite) {
        dom.btnWrite.addEventListener('click', function () {
            if (!state.isStaff && (!state.isInSceneHere || state.isLockedElsewhere)) {
                return;
            }

            window.NarakaChat.hideWriteRestore();
            window.NarakaChat.openModal(dom.writeModal);
            dom.textarea.focus();
            updateWriteMode();
        });
    }

    if (dom.writeClose) {
        dom.writeClose.addEventListener('click', function () {
            window.NarakaChat.hideWriteRestore();
            window.NarakaChat.closeModal(dom.writeModal);
        });
    }

    if (dom.writeMinimize) {
        dom.writeMinimize.addEventListener('click', function () {
            window.NarakaChat.closeModal(dom.writeModal);
            window.NarakaChat.showWriteRestore();
        });
    }

    if (dom.writeRestore) {
        dom.writeRestore.addEventListener('click', function () {
            if (!state.isStaff && (!state.isInSceneHere || state.isLockedElsewhere)) {
                return;
            }

            window.NarakaChat.hideWriteRestore();
            window.NarakaChat.openModal(dom.writeModal);
            dom.textarea.focus();
            updateWriteMode();
        });
    }

    if (dom.writeModal) {
        dom.writeModal.addEventListener('click', function (event) {
            if (event.target === dom.writeModal) {
                window.NarakaChat.hideWriteRestore();
                window.NarakaChat.closeModal(dom.writeModal);
            }
        });
    }

    dom.typeSelect.addEventListener('change', function () {
        updateWriteMode();
    });

    dom.textarea.addEventListener('input', function () {
        localStorage.setItem(state.draftKey, dom.textarea.value);
        updateCounter();
    });

    const savedDraft = localStorage.getItem(state.draftKey);

    if (savedDraft) {
        dom.textarea.value = savedDraft;
    }

    dom.sendButton.addEventListener('click', function () {
        const payload = {
            id_chat: state.idChat,
            message_type: dom.typeSelect.value,
            context: document.getElementById('chat-tag-input').value,
            target: document.getElementById('chat-whisper-target').value,
            content: dom.textarea.value
        };

        dom.sendButton.disabled = true;

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
                dom.textarea.value = '';
                localStorage.removeItem(state.draftKey);
                updateCounter();
                window.NarakaChat.hideWriteRestore();
                window.NarakaChat.closeModal(dom.writeModal);

                if (window.NarakaChat.fetchMessages) {
                    window.NarakaChat.fetchMessages();
                }

                return;
            }

            updateCounter();
        })
        .catch(function () {
            updateCounter();
        });
    });

    updateWriteMode();
};

// by LaEmiX