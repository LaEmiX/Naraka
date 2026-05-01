'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.updateSceneButtons = function () {
    const state = window.NarakaChat.state;
    const dom = window.NarakaChat.dom;

    if (dom.btnSceneEnter) {
        dom.btnSceneEnter.disabled = state.isInSceneHere || state.isLockedElsewhere;
    }

    if (dom.btnSceneLeave) {
        dom.btnSceneLeave.disabled = !state.isInSceneHere;
    }

    if (dom.btnWrite) {
        dom.btnWrite.disabled = !state.isStaff && (!state.isInSceneHere || state.isLockedElsewhere);
    }
};

window.NarakaChat.initScene = function () {
    const state = window.NarakaChat.state;
    const dom = window.NarakaChat.dom;

    function enterScene() {
        if (!dom.btnSceneEnter) {
            return;
        }

        dom.btnSceneEnter.disabled = true;

        fetch('/actions/chat_scene_enter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: state.idChat
            })
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                state.isInSceneHere = true;
                state.isLockedElsewhere = false;
                window.NarakaChat.updateSceneButtons();
                return;
            }

            window.NarakaChat.updateSceneButtons();
        })
        .catch(function () {
            window.NarakaChat.updateSceneButtons();
        });
    }

    function leaveScene() {
        if (!dom.btnSceneLeave) {
            return;
        }

        dom.btnSceneLeave.disabled = true;

        fetch('/actions/chat_scene_leave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                id_chat: state.idChat
            })
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                state.isInSceneHere = false;
                state.isLockedElsewhere = false;

                if (window.NarakaChat.hideWriteRestore) {
                    window.NarakaChat.hideWriteRestore();
                }

                window.NarakaChat.closeModal(dom.writeModal);
                window.NarakaChat.updateSceneButtons();
                return;
            }

            window.NarakaChat.updateSceneButtons();
        })
        .catch(function () {
            window.NarakaChat.updateSceneButtons();
        });
    }

    if (dom.btnSceneEnter) {
        dom.btnSceneEnter.addEventListener('click', enterScene);
    }

    if (dom.btnSceneLeave) {
        dom.btnSceneLeave.addEventListener('click', leaveScene);
    }

    window.NarakaChat.updateSceneButtons();
};

// by LaEmiX