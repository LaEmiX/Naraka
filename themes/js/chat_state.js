'use strict';

window.NarakaChat = window.NarakaChat || {};

window.NarakaChat.state = {
    config: window.NarakaChatConfig || {},

    idChat: 0,
    idCharacter: 0,
    isStaff: false,
    isInSceneHere: false,
    isLockedElsewhere: false,

    draftKey: '',

    lastId: 0,
    isFetching: false
};

window.NarakaChat.initState = function () {
    const state = window.NarakaChat.state;
    const config = state.config;

    state.idChat = config.idChat || 0;
    state.idCharacter = config.idCharacter || 0;
    state.isStaff = config.isStaff || false;
    state.isInSceneHere = config.isInSceneHere || false;
    state.isLockedElsewhere = config.isLockedElsewhere || false;

    state.draftKey = 'naraka_chat_draft_' + state.idChat + '_' + state.idCharacter;
};

// by LaEmiX