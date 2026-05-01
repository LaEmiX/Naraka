'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.initInfo = function () {
    const dom = window.NarakaChat.dom;

    if (!dom.infoModal || !dom.btnInfo) {
        return;
    }

    dom.btnInfo.addEventListener('click', function () {
        window.NarakaChat.openModal(dom.infoModal);
    });

    if (dom.infoClose) {
        dom.infoClose.addEventListener('click', function () {
            window.NarakaChat.closeModal(dom.infoModal);
        });
    }

    dom.infoModal.addEventListener('click', function (event) {
        if (event.target === dom.infoModal) {
            window.NarakaChat.closeModal(dom.infoModal);
        }
    });
};

// by LaEmiX