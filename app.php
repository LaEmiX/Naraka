<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naraka</title>
    <link rel="stylesheet" href="/themes/auth.css">
    <link rel="stylesheet" href="/themes/app.css?v=audio-clean-06">
</head>
<body class="naraka-shell-body">

<div class="naraka-shell">

    <audio id="narakaMusic" src="/themes/audio/naraka_loop.mp3" loop preload="auto"></audio>

    <div class="naraka-start-overlay" id="narakaStartOverlay">

        <div class="auth-logo-wrap naraka-start-logo-wrap">
            <img src="/themes/images/titolo.png" alt="Naraka" class="auth-logo">
        </div>

        <div class="auth-panel naraka-start-panel">

            <p class="naraka-start-text">
                Naraka è un GdR PbC che fonde horror, fantasy e cultura pop anni ’80/’90, ideato e creato da
            </p>

            <div class="naraka-sinister-wrap">
                <img src="/themes/images/sinister.png" alt="Sinister" class="naraka-sinister-logo" id="openContactsModal">
            </div>

            <p class="naraka-start-text">
                Se ami le atmosfere sospese tra Stranger Things e Steven Spielberg, gli incubi di It, l’oscurità di Dark Souls e Bloodborne, fino al techno-delirio di The Lawnmower Man, WarGames e TRON, allora hai trovato il posto giusto.
            </p>

            <p class="auth-subtitle naraka-loading-text" id="narakaLoadingText"></p>

            <button type="button" class="auth-button" id="narakaStartButton">
                Avvia Echo System
            </button>

        </div>

        <div class="naraka-pegi-row">
            <img src="/themes/images/18.png" alt="18+" class="naraka-pegi-icon">
            <img src="/themes/images/violenza.png" alt="Violenza" class="naraka-pegi-icon">
            <img src="/themes/images/sesso.png" alt="Sesso" class="naraka-pegi-icon">
            <img src="/themes/images/droga.png" alt="Droga" class="naraka-pegi-icon">
            <img src="/themes/images/volgare.png" alt="Linguaggio scurrile" class="naraka-pegi-icon">
            <img src="/themes/images/paura.png" alt="Paura" class="naraka-pegi-icon">
        </div>

    </div>

    <iframe
        src="about:blank"
        class="naraka-frame"
        id="narakaFrame"
        name="narakaFrame"
        title="Naraka"
        allow="autoplay"
    ></iframe>

    <div class="naraka-modal" id="contactsModal">
        <button type="button" class="naraka-modal-close" id="closeContactsModal" aria-label="Chiudi contatti">
            ×
        </button>

        <div class="naraka-modal-content">

            <div class="naraka-modal-title">CONTATTI</div>

            <img src="/themes/images/family.png" alt="Family" class="naraka-modal-img-main">

            <div class="naraka-modal-text">mail: gestione@narakagdr.it</div>
            <div class="naraka-modal-text">discord: narakagdr</div>

            <img src="/themes/images/mimi.png" alt="Mimi" class="naraka-modal-img-staff">

            <div class="naraka-modal-name">MIMI</div>
            <div class="naraka-modal-role">Admin/Programmazione</div>
            <div class="naraka-modal-text">mimi@narakagdr.it</div>

            <img src="/themes/images/bubi.png" alt="Bubi" class="naraka-modal-img-staff">

            <div class="naraka-modal-name">BUBI</div>
            <div class="naraka-modal-role">Gestore/Lore/Storytelling/Master</div>
            <div class="naraka-modal-text">bubi@narakagdr.it</div>

            <img src="/themes/images/vic.png" alt="Vic" class="naraka-modal-img-staff">

            <div class="naraka-modal-name">VIC</div>
            <div class="naraka-modal-role">"Sono Mr. Wolf, risolvo problemi"/Rompicoglioni/Master</div>
            <div class="naraka-modal-text">mama@narakagdr.it</div>

            <div class="naraka-modal-quote">
                "Non è nuovo. Mi sono reso conto che niente di ciò che abbiamo fatto è nuovo. Non abbiamo attinto a nuove aree del cervello, abbiamo appena risvegliato le più antiche. Questa tecnologia è semplicemente una via verso i poteri che prestigiatori e alchimisti usavano secoli fa. La razza umana ha perso quella conoscenza e ora la sto recuperando attraverso la realtà virtuale."
            </div>

            <div class="naraka-modal-quote-author">
                (Jobe Smith, Il Tagliaerbe, 1992)
            </div>

        </div>
    </div>

</div>

<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const target = document.getElementById('narakaLoadingText');

    if (target) {
        const text = 'LOADING ECHO SYSTEM...';
        let i = 0;

        function typeLoadingText() {
            target.textContent = text.substring(0, i);
            i++;

            if (i <= text.length) {
                window.setTimeout(typeLoadingText, 60);
                return;
            }

            window.setTimeout(function () {
                i = 0;
                typeLoadingText();
            }, 1200);
        }

        typeLoadingText();
    }

    const openContactsModal = document.getElementById('openContactsModal');
    const contactsModal = document.getElementById('contactsModal');
    const closeContactsModal = document.getElementById('closeContactsModal');

    if (openContactsModal && contactsModal && closeContactsModal) {
        openContactsModal.addEventListener('click', function () {
            contactsModal.classList.add('open');
        });

        closeContactsModal.addEventListener('click', function () {
            contactsModal.classList.remove('open');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                contactsModal.classList.remove('open');
            }
        });
    }
});
</script>

<script src="/themes/app.js?v=audio-clean-06"></script>

</body>
</html>

<?php
// by LaEmiX