(function () {
    'use strict';

    const root = document.querySelector('[data-meteo-root]');
    if (!root) return;

    const clock = root.querySelector('[data-meteo-clock]');
    const temp = root.querySelector('[data-meteo-temp]');
    const desc = root.querySelector('[data-meteo-desc]');

    if (!clock || !temp || !desc) return;

    function formattaCondizione(condizione) {
        if (!condizione) return '';
        return condizione.replace(/_/g, ' ')
                         .replace(/\b\w/g, c => c.toUpperCase());
    }

    function aggiornaOrologioLocale() {
        const now = new Date();

        const g = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const a = now.getFullYear();

        const h = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');

        clock.textContent = `${g}/${m}/${a} — ${h}:${min}`;
    }

    function costruisciRiga(dati) {
        const cond = formattaCondizione(dati.condizione);

        if (dati.fascia === 'notte') {
            return `${dati.temperatura}°C · ${cond} · ${dati.luna}`;
        }

        return `${dati.temperatura}°C · ${cond}`;
    }

    async function aggiornaMeteo() {
        try {
            const res = await fetch('/ajax/meteo.php', {
                credentials: 'same-origin'
            });

            if (!res.ok) return;

            const json = await res.json();
            if (!json.success) return;

            const d = json.data;

            clock.textContent = d.data_ora;
            temp.textContent = costruisciRiga(d);
            desc.textContent = d.descrizione;

        } catch (e) {
            // silenzioso
        }
    }

    aggiornaOrologioLocale();
    aggiornaMeteo();

    setInterval(aggiornaOrologioLocale, 1000);
    setInterval(aggiornaMeteo, 300000); // ogni 5 minuti
})();