'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.dom = {};

window.NarakaChat.initDom = function () {
    const dom = window.NarakaChat.dom;

    dom.messagesBox = document.getElementById('chat-messages');

    dom.writeModal = document.getElementById('chat-write-modal');
    dom.writeContent = document.getElementById('chat-write-content');
    dom.writeDragHandle = document.getElementById('chat-write-drag-handle');
    dom.editModal = document.getElementById('chat-edit-modal');
    dom.infoModal = document.getElementById('chat-info-modal');
    dom.invitesModal = document.getElementById('chat-invites-modal');

    dom.btnSceneEnter = document.getElementById('btn-scene-enter');
    dom.btnSceneLeave = document.getElementById('btn-scene-leave');
    dom.btnWrite = document.getElementById('btn-write');
    dom.btnInfo = document.getElementById('btn-info');
    dom.btnInvites = document.getElementById('btn-invites');

    dom.writeClose = document.getElementById('chat-write-close');
    dom.writeMinimize = document.getElementById('chat-write-minimize');
    dom.writeRestore = document.getElementById('chat-write-restore');
    dom.editClose = document.getElementById('chat-edit-close');
    dom.infoClose = document.getElementById('chat-info-close');
    dom.invitesClose = document.getElementById('chat-invites-close');

    dom.typeSelect = document.getElementById('chat-type');
    dom.tagRow = document.getElementById('chat-tag-row');
    dom.whisperRow = document.getElementById('chat-whisper-row');

    dom.textarea = document.getElementById('chat-text');
    dom.counter = document.getElementById('chat-counter');
    dom.sendButton = document.getElementById('chat-send');

    dom.editIdInput = document.getElementById('chat-edit-id');
    dom.editTypeInput = document.getElementById('chat-edit-type');
    dom.editTagRow = document.getElementById('chat-edit-tag-row');
    dom.editTagInput = document.getElementById('chat-edit-tag-input');
    dom.editTextarea = document.getElementById('chat-edit-text');
    dom.editCounter = document.getElementById('chat-edit-counter');
    dom.editSaveButton = document.getElementById('chat-edit-save');

    dom.inviteNameInput = document.getElementById('chat-invite-name');
    dom.inviteAddButton = document.getElementById('chat-invite-add');
    dom.inviteFeedback = document.getElementById('chat-invite-feedback');
    dom.invitesList = document.getElementById('chat-invites-list');
};

// by LaEmiX