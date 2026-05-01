'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.initEdit = function () {
    const dom = window.NarakaChat.dom;

    if (!dom.editModal || !dom.editTextarea || !dom.editSaveButton) {
        return;
    }

    const minAction = parseInt(dom.editTextarea.dataset.minAction, 10);
    const maxAction = parseInt(dom.editTextarea.dataset.maxAction, 10);

    function updateEditCounter() {
        const type = dom.editTypeInput.value;
        let length = dom.editTextarea.value.length;

        if (type !== 'azione') {
            dom.editCounter.textContent = '';
            dom.editSaveButton.disabled = dom.editTextarea.value.trim().length === 0;
            return;
        }

        if (length > maxAction) {
            dom.editTextarea.value = dom.editTextarea.value.substring(0, maxAction);
            length = dom.editTextarea.value.length;
        }

        dom.editCounter.textContent = length + ' / ' + maxAction + ' caratteri. Minimo: ' + minAction;
        dom.editSaveButton.disabled = dom.editTextarea.value.trim().length < minAction;
    }

    if (dom.editClose) {
        dom.editClose.addEventListener('click', function () {
            window.NarakaChat.closeModal(dom.editModal);
        });
    }

    dom.editModal.addEventListener('click', function (event) {
        if (event.target === dom.editModal) {
            window.NarakaChat.closeModal(dom.editModal);
        }
    });

    dom.editTextarea.addEventListener('input', function () {
        updateEditCounter();
    });

    if (dom.messagesBox) {
        dom.messagesBox.addEventListener('click', function (event) {
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

            dom.editIdInput.value = idMessage;
            dom.editTypeInput.value = type;
            dom.editTextarea.value = rawContent;
            dom.editTagInput.value = rawContext;

            dom.editTagRow.style.display = type === 'azione' ? 'block' : 'none';

            window.NarakaChat.openModal(dom.editModal);
            dom.editTextarea.focus();
            updateEditCounter();
        });
    }

    dom.editSaveButton.addEventListener('click', function () {
        const idMessage = parseInt(dom.editIdInput.value, 10);
        const type = dom.editTypeInput.value;
        const content = dom.editTextarea.value;
        const context = type === 'azione' ? dom.editTagInput.value : '';

        if (!idMessage) {
            return;
        }

        dom.editSaveButton.disabled = true;

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
                window.NarakaChat.refreshEditedRow(idMessage, content, context);
                window.NarakaChat.closeModal(dom.editModal);
            }
        })
        .catch(function () {})
        .finally(function () {
            updateEditCounter();
        });
    });
};

// by LaEmiX