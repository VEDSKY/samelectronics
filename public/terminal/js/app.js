/* ============================================================
   app.js — boot the phosphor terminal
   ============================================================ */
import { Terminal, sleep } from './core.js';
import { registerCommands, bootSequence, printMotd } from './commands.js';

const $ = (sel) => document.querySelector(sel);
const get = (k, d) => { try { return localStorage.getItem(k) ?? d; } catch { return d; } };

/* ---------- preferences before first paint ---------- */
const prefs = {
    theme: get('sam.theme', 'phosphor'),
    crt: get('sam.crt', 'on'),
    sound: get('sam.sound', 'on'),
    fs: get('sam.fs', ''),
    locale: get('sam.locale', 'ru'),
};
document.documentElement.dataset.theme = prefs.theme;
if (prefs.fs) document.documentElement.style.setProperty('--fs', `${prefs.fs}px`);
document.documentElement.lang = prefs.locale;

/* ---------- wire the terminal ---------- */
const term = new Terminal($('#crt'));
window.term = term; // debug handle

term.setTheme(prefs.theme);
term.setCrt(prefs.crt === 'on');
term.setSound(prefs.sound === 'on');
term.setLocale(prefs.locale);
if (prefs.locale !== 'ru') {
    document.querySelectorAll('[data-kbd-mode]').forEach((b) =>
        b.classList.toggle('is-active', b.dataset.kbdMode === 'latin'));
}

registerCommands(term);
term.setupKeyboard($('#keyboard-rows'), $('#keyboard-modes'));

/* ---------- live server handshake (best effort) ---------- */
async function handshake() {
    try {
        const info = await term.api.get('/bootstrap');
        if (info && info.ok) {
            term.env.boot = { ...term.env.boot, ...info };
            let storedLocale = null;
            try { storedLocale = localStorage.getItem('sam.locale'); } catch { /* noop */ }
            if (info.locale && !storedLocale) term.setLocale(info.locale);
            if (info.user) { term.session.user = info.user; term.setPrompt(); }
            term.setStatus('status-net', '◉ LINK');
        } else {
            term.setStatus('status-net', '◌ DEGRADED', 'statusbar__seg is-off');
        }
    } catch {
        term.setStatus('status-net', '◌ OFFLINE', 'statusbar__seg is-off');
    }
}

/* ---------- rotating hint ---------- */
const HINTS = [
    'try <b>open about</b>',
    'try <b>ls</b>',
    'try <b>music</b>',
    'try <b>contact</b>',
    'try <b>login</b> → <b>admin</b>',
    'try <b>theme amber</b>',
    'try <b>neofetch</b>',
    'try <b>help</b>',
];
let hintIx = 0;
function hintCycle() {
    const el = $('#hints-hint');
    if (!el) return;
    setInterval(() => {
        if (document.getElementById('keyboard')?.classList.contains('is-hidden')) return;
        hintIx = (hintIx + 1) % HINTS.length;
        el.style.transition = 'opacity .25s';
        el.style.opacity = '0';
        setTimeout(() => { el.innerHTML = HINTS[hintIx]; el.style.opacity = '1'; }, 260);
    }, 5200);
}

/* ---------- boot ---------- */
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

(async () => {
    await handshake();
    await bootSequence(term, { fast: reduced });
    if (!reduced) await sleep(120);
    printMotd(term);
    term.setPrompt();
    term.focus();
    hintCycle();
})();
