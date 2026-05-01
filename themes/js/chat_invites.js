'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.initInvites = function () {
    const state = window.NarakaChat.state;
    const dom = window.NarakaChat.dom;

    if (!dom.invitesModal || !dom.invitesList) {
        return;
    }

    function renderInvites(invites) {
        if (!Array.isArray(invites) || invites.length === 0) {
            dom.invitesList.innerHTML = '<div class="chat-invite-empty">Nessun invitato attivo.</div>';
            return;
        }

        let html = '';

        invites.forEach(function (invite) {
            html += ''
                + '<div class="chat-invite-row">'
                + '<span class="chat-invite-name">' + window.NarakaChat.escapeHtml(invite.character_name || '') + '</span>'
                + '<button type="button" class="chat-btn chat-btn-danger chat-invite-remove" data-character-id="' + window.NarakaChat.escapeHtml(invite.id_character || '') + '">Rimuovi</button>'
                + '</div>';
        });

        dom.invitesList.innerHTML = html;
    }

    function setInviteFeedback(message) {
        if (dom.inviteFeedback) {
            dom.inviteFeedback.textContent = message || '';
        }
    }

    function loadInvites() {
        setInviteFeedback('');

        fetch('/actions/chat_invite_list.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: state.idChat
            })
        })
        .then(window.NarakaChat.parseJsonResponse)
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
        if (!dom.inviteNameInput || !dom.inviteAddButton) {
            return;
        }

        const name = dom.inviteNameInput.value.trim();

        if (name === '') {
            setInviteFeedback('Inserisci un nome personaggio.');
            return;
        }

        dom.inviteAddButton.disabled = true;
        setInviteFeedback('');

        fetch('/actions/chat_invite_add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: state.idChat,
                character_name: name
            })
        })
        .then(window.NarakaChat.parseJsonResponse)
        .then(function (data) {
            setInviteFeedback(data.message || '');

            if (data.success) {
                dom.inviteNameInput.value = '';
                loadInvites();
            }
        })
        .catch(function (error) {
            setInviteFeedback('Errore durante l’invito: ' + error.message);
        })
        .finally(function () {
            dom.inviteAddButton.disabled = false;
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
                id_chat: state.idChat,
                id_character: idTargetCharacter
            })
        })
        .then(window.NarakaChat.parseJsonResponse)
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

    if (dom.btnInvites) {
        dom.btnInvites.addEventListener('click', function () {
            window.NarakaChat.openModal(dom.invitesModal);
            loadInvites();

            if (dom.inviteNameInput) {
                dom.inviteNameInput.focus();
            }
        });
    }

    if (dom.invitesClose) {
        dom.invitesClose.addEventListener('click', function () {
            window.NarakaChat.closeModal(dom.invitesModal);
        });
    }

    dom.invitesModal.addEventListener('click', function (event) {
        if (event.target === dom.invitesModal) {
            window.NarakaChat.closeModal(dom.invitesModal);
        }
    });

    if (dom.inviteAddButton) {
        dom.inviteAddButton.addEventListener('click', addInvite);
    }

    if (dom.inviteNameInput) {
        dom.inviteNameInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addInvite();
            }
        });
    }

    dom.invitesList.addEventListener('click', function (event) {
        const button = event.target.closest('.chat-invite-remove');

        if (!button) {
            return;
        }

        const idTargetCharacter = parseInt(button.dataset.characterId, 10);

        if (!Number.isNaN(idTargetCharacter) && idTargetCharacter > 0) {
            removeInvite(idTargetCharacter);
        }
    });
};

// by LaEmiX