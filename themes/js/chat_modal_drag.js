'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.initModalDrag = function () {
    const dom = window.NarakaChat.dom;

    if (!dom.writeContent || !dom.writeDragHandle) {
        return;
    }

    let isDraggingWriteModal = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let modalStartLeft = 0;
    let modalStartTop = 0;
    let hasWriteModalPosition = false;

    function lockWriteModalPosition() {
        if (hasWriteModalPosition) {
            return;
        }

        const rect = dom.writeContent.getBoundingClientRect();

        dom.writeContent.style.position = 'fixed';
        dom.writeContent.style.left = rect.left + 'px';
        dom.writeContent.style.top = rect.top + 'px';
        dom.writeContent.style.margin = '0';
        dom.writeContent.style.transform = 'none';

        hasWriteModalPosition = true;
    }

    function clampWriteModalPosition(left, top) {
        const rect = dom.writeContent.getBoundingClientRect();
        const maxLeft = Math.max(0, window.innerWidth - rect.width);
        const maxTop = Math.max(0, window.innerHeight - rect.height);

        return {
            left: Math.min(Math.max(0, left), maxLeft),
            top: Math.min(Math.max(0, top), maxTop)
        };
    }

    function startWriteModalDrag(event) {
        const pointer = event.touches ? event.touches[0] : event;

        lockWriteModalPosition();

        const rect = dom.writeContent.getBoundingClientRect();

        isDraggingWriteModal = true;
        dragStartX = pointer.clientX;
        dragStartY = pointer.clientY;
        modalStartLeft = rect.left;
        modalStartTop = rect.top;

        document.body.classList.add('is-chat-modal-dragging');

        event.preventDefault();
    }

    function moveWriteModalDrag(event) {
        if (!isDraggingWriteModal) {
            return;
        }

        const pointer = event.touches ? event.touches[0] : event;

        const nextLeft = modalStartLeft + pointer.clientX - dragStartX;
        const nextTop = modalStartTop + pointer.clientY - dragStartY;
        const clamped = clampWriteModalPosition(nextLeft, nextTop);

        dom.writeContent.style.left = clamped.left + 'px';
        dom.writeContent.style.top = clamped.top + 'px';

        event.preventDefault();
    }

    function endWriteModalDrag() {
        isDraggingWriteModal = false;
        document.body.classList.remove('is-chat-modal-dragging');
    }

    dom.writeDragHandle.addEventListener('mousedown', startWriteModalDrag);
    dom.writeDragHandle.addEventListener('touchstart', startWriteModalDrag, { passive: false });

    document.addEventListener('mousemove', moveWriteModalDrag);
    document.addEventListener('mouseup', endWriteModalDrag);
    document.addEventListener('touchmove', moveWriteModalDrag, { passive: false });
    document.addEventListener('touchend', endWriteModalDrag);

    window.addEventListener('resize', function () {
        if (!hasWriteModalPosition) {
            return;
        }

        const rect = dom.writeContent.getBoundingClientRect();
        const clamped = clampWriteModalPosition(rect.left, rect.top);

        dom.writeContent.style.left = clamped.left + 'px';
        dom.writeContent.style.top = clamped.top + 'px';
    });
};

// by LaEmiX