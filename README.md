# NARAKA2 – Documentazione Tecnica (PRO LEVEL)

---

## 🧠 VISIONE PROGETTO

Naraka è una piattaforma GDR multi-land progettata per offrire:

* onboarding narrativo guidato
* doppia identità personaggio (City / Echoes)
* esperienza immersiva (UI + audio persistente)
* architettura scalabile e modulare

Obiettivo: separare completamente **esperienza utente** e **logica di gioco**, mantenendo un sistema espandibile senza framework.

---

## ⚙️ STACK TECNOLOGICO

* PHP 8 (strict mode)
* MySQL (InnoDB)
* CSS custom (no framework)
* JavaScript vanilla
* Hosting: Aruba Linux

Vincoli:

* compatibilità browser completa
* mobile responsive
* no CSS inline
* no dipendenze esterne runtime

---

## 📁 ARCHITETTURA FILE

```
/
├── app.php               # container principale (audio + iframe)
├── login.php
├── register.php
├── onboarding_*.php
├── index.php            # entry gameplay
│
├── /config/
│   ├── database.php
│   ├── auth.php
│   ├── land.php
│
├── /themes/
│   ├── auth.css
│   ├── app.css
│   ├── app.js
│   ├── /images/
│   ├── /audio/
```

Pattern:

* ogni pagina = file standalone
* nessuna dipendenza implicita
* inclusioni centralizzate

---

## 🔐 SISTEMA AUTENTICAZIONE

Caratteristiche:

* login via username/email
* password hashing (`password_hash`)
* sessioni PHP sicure
* rigenerazione ID sessione

Flusso:

```
login → controllo DB → sessione → routing onboarding / game
```

---

## 🌍 SISTEMA MULTI-LAND

Land attive:

* `city`
* `echoes`

Gestione:

```
$_SESSION['current_land']
```

Relazioni:

* 1 utente → 2 personaggi (uno per land)

---

## 🧬 MODELLO DATI

### users

```
id_user
username
email
password_hash
role
is_active
```

### lands

```
id_land
slug (city / echoes)
name
```

### characters

```
id_character
id_user
id_land
name
slug
```

### stats

```
id_stat
slug
name
```

### character_stats

```
id_character
id_stat
value
```

---

## 📊 SISTEMA STATISTICHE

### CITY

* influenza
* intelletto
* percezione
* prontezza
* vigore
* volonta

### ECHOES

* forza
* destrezza
* costituzione
* intelligenza
* saggezza
* carisma

### REGOLAMENTO

* min: 1
* max: 5
* totale: 18
* max una stat a 5

### VALIDAZIONE

* frontend (JS): UX
* backend (PHP): sicurezza

---

## 🧭 ONBOARDING SYSTEM

### FLOW

```
login/register
→ onboarding_city
→ onboarding_echoes
→ onboarding_stats_city
→ onboarding_stats_echoes
→ onboarding_confirm
→ index
```

### CARATTERISTICHE

* stato salvato in `$_SESSION['onboarding']`
* navigazione bidirezionale
* UI narrativa (Lene)
* typing dinamico

---

## 💾 PERSISTENZA

Implementata in `onboarding_confirm.php`

Processo:

1. transaction start
2. insert characters (city + echoes)
3. mapping stats
4. insert character_stats
5. commit
6. reset session onboarding

Fail-safe:

* rollback su errore

---

## 🎧 SISTEMA AUDIO

Implementazione:

* `app.php` come shell persistente
* `<audio>` fuori dall’iframe

Comportamento:

* parte dopo interazione utente
* continua tra le pagine
* si interrompe su ingresso gioco

Vincoli browser:

* autoplay vietato senza input utente

---

## 🖥️ UI SYSTEM

### FONT

* VT323 → titoli
* IBM Plex Mono → testo

### COMPONENTI

* auth-panel
* auth-field
* auth-button
* auth-stat-row
* lene-wrap

### PRINCIPI

* modularità
* riuso
* consistenza visiva

---

## 🔄 ROUTING

`.htaccess`

```
DirectoryIndex app.php
RewriteRule ^$ /app.php [L]
```

Sistema:

* container iframe
* routing interno via PHP

---

## ⚠️ ANTIPATTERN DA EVITARE

* modifiche parziali (solo file completi)
* CSS inline
* logica duplicata
* accesso diretto a DB fuori config
* uso di ID numerici hardcoded per land

---

## 🚧 ROADMAP

### IMMEDIATO

* index.php (core gameplay)

  * layout colonne
  * location
  * presenti

### BREVE TERMINE

* TalkBox (messaggistica)
* gestione sessione avanzata
* permessi utenti

### MEDIO TERMINE

* sistema eventi
* inventario
* combat system

## 📐 CONVENZIONI

Naming:

* snake_case DB
* lowercase slug
* file PHP descrittivi

Regole:

* file sempre completi
* niente patch
* firma obbligatoria

---

## ✍️ AUTORE

LaEmiX

---

## 📌 NOTE FINALI

Naraka è progettato per crescere senza rifattorizzazioni massicce.

Ogni nuova feature deve:

* integrarsi senza rompere flussi esistenti
* rispettare session management
* mantenere coerenza UI

---

**by LaEmiX**
