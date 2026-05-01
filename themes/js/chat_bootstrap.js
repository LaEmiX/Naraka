'use strict';

document.addEventListener('DOMContentLoaded', function () {
    if (!window.NarakaChat) {
        return;
    }

    if (window.NarakaChat.initState) {
        window.NarakaChat.initState();
    }

    if (window.NarakaChat.initDom) {
        window.NarakaChat.initDom();
    }

    if (window.NarakaChat.initModalDrag) {
        window.NarakaChat.initModalDrag();
    }

    if (window.NarakaChat.initMessages) {
        window.NarakaChat.initMessages();
    }

    if (window.NarakaChat.initScene) {
        window.NarakaChat.initScene();
    }

    if (window.NarakaChat.initWrite) {
        window.NarakaChat.initWrite();
    }

    if (window.NarakaChat.initEdit) {
        window.NarakaChat.initEdit();
    }

    if (window.NarakaChat.initInvites) {
        window.NarakaChat.initInvites();
    }

    if (window.NarakaChat.initInfo) {
        window.NarakaChat.initInfo();
    }
});

// by LaEmiX