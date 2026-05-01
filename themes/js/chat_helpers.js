'use strict';

window.NarakaChat = window.NarakaChat || {};

/* =========================
   BASE UTILS
========================= */

window.NarakaChat.escapeHtml = function (value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

window.NarakaChat.parseJsonResponse = function (response) {
    return response.text().then(function (text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error(text);
        }
    });
};

window.NarakaChat.formatClientChatText = function (value) {
    let text = window.NarakaChat.escapeHtml(value);

    text = text.replace(/\[(.*?)\]/g, '&lt;$1&gt;');
    text = text.replace(/\&lt;(.*?)\&gt;/g, '<span class="chat-action-text">&lt;$1&gt;</span>');
    text = text.replace(/\n/g, '<br>');

    return text;
};

/* =========================
   MODALS
========================= */

window.NarakaChat.openModal = function (modal) {
    if (modal) {
        modal.style.display = 'flex';
    }
};

window.NarakaChat.closeModal = function (modal) {
    if (modal) {
        modal.style.display = 'none';
    }
};

/* =========================
   SCROLL
========================= */

window.NarakaChat.isNearBottom = function (container) {
    if (!container) {
        return false;
    }

    return container.scrollHeight - container.scrollTop - container.clientHeight < 80;
};

window.NarakaChat.scrollToBottom = function (container) {
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
};

// by LaEmiX