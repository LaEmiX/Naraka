'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const frame = document.getElementById('narakaFrame');
    const music = document.getElementById('narakaMusic');
    const overlay = document.getElementById('narakaStartOverlay');
    const startButton = document.getElementById('narakaStartButton');

    if (!frame || !music || !overlay || !startButton) {
        return;
    }

    const preGamePaths = [
        '/login.php',
        '/register.php',
        '/registrazione.php',
        '/onboarding_city.php',
        '/onboarding_echoes.php',
        '/onboarding_stats_city.php',
        '/onboarding_stats_echoes.php',
        '/onboarding_confirm.php'
    ];

    function isPreGameUrl(url) {
        return preGamePaths.some(function (path) {
            return url.pathname === path;
        });
    }

    function startMusic() {
        music.volume = 0.75;
        music.loop = true;

        music.play().catch(function () {
            return;
        });
    }

    function stopMusic() {
        music.pause();
        music.currentTime = 0;
    }

    startButton.addEventListener('click', function () {
        overlay.style.display = 'none';
        startMusic();

        if (frame.getAttribute('src') === 'about:blank') {
            frame.src = '/login.php';
        }
    });

    frame.addEventListener('load', function () {
        let frameUrl;

        try {
            frameUrl = new URL(frame.contentWindow.location.href);
        } catch (error) {
            return;
        }

        if (frameUrl.href === 'about:blank') {
            return;
        }

        if (isPreGameUrl(frameUrl)) {
            startMusic();
            return;
        }

        if (frameUrl.pathname === '/index.php') {
            stopMusic();
            frame.src = 'about:blank';
            window.top.location.replace('/index.php');
            return;
        }

        stopMusic();
    });
});