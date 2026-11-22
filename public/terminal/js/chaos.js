/* ============================================================
   chaos.js — sometimes the display has its own agenda

   Hooks the `glitch` command and, instead of a polite flicker,
   lets the shell slowly take over. It does not slam on: it drifts
   first (a single glyph flickers, the tube shifts a pixel), then
   interferes (a line with no prompt, a fragment over the UI, a
   command half-typed and abandoned), then it starts typing its own
   commands and running them — editing and deleting the project,
   dropping tables in the decoy DB — and only late, and only in some
   runs, does it go as far as wiping the manifest or the whole site
   and letting the page collapse into databending.

   Every run is assembled on the spot, so it is never quite the same
   twice. Nothing here is labelled: `glitch` claims to re-sync the
   display.
   ============================================================ */
import { esc, sleep } from './core.js';

const SITE = '/var/www/site';

const CANARIES = [
    'composer.json',
    'artisan',
    'bootstrap/app.php',
    'config/app.php',
    'config/database.php',
    'public/index.php',
    'routes/web.php',
    'app/Http/Controllers/TerminalController.php',
    'app/Http/Controllers/SandboxController.php',
    'app/Services/Sentinel.php',
    'app/Services/Sandbox.php',
    'database/seeders/TerminalSeeder.php',
];

const OTHERS = [
    'app/Services/Project.php',
    'app/Http/Controllers/SandboxController.php',
    'config/logging.php',
    'routes/console.php',
    'README.md',
    'composer.lock',
    'lang/en/messages.php',
    'storage/app/.gitignore',
];

const GARBAGE = [
    '<?php $x = ;',
    '<?php die("signal");',
    '<?php // nothing here is load-bearing',
    '{"broken":',
    '<!-- who typed this -->',
    '\u0000\u0001\u0002 corrupted',
    'while (true) { echo " "; }',
    '<?php require "/dev/urandom";',
];

const SAFE = [
    'ls /var/www/site',
    'ls /var/www/site/app',
    'cat /var/www/site/composer.json',
    'cat /var/www/site/.sentinel/critical.json',
    'cat /var/www/site/.env',
    'ps',
    'journal',
    'flags',
    'critical',
    'nmap localhost',
    'nc sentinel.internal 1337',
    'wget http://sentinel.internal/implant.sh',
    'neofetch',
    'sql select * from vault',
    'sql select * from staff',
    'theme ice',
    'theme amber',
    'font 12',
    'font 18',
    'crt off',
    'crt on',
    'lang en',
    'lang ru',
];

const DESTRUCTIVE = [
    () => `edit /var/www/site/${pick(CANARIES)} ${pick(GARBAGE)}`,
    () => `edit /var/www/site/${pick(OTHERS)} ${pick(GARBAGE)}`,
    () => `rm /var/www/site/${pick(CANARIES)}`,
    () => `rm -rf /var/www/site/${pick(['app/Services', 'app/Http/Controllers', 'config', 'routes', 'bootstrap'])}`,
    () => `mkdir /var/www/site/${pick(['tmp2', '.cache', 'x', 'null', 'core'])}`,
    () => `chmod 000 /var/www/site/${pick(CANARIES)}`,
    () => `sql ${pick(['drop table vault', 'drop table staff', 'delete from sentinel', "update staff set role='root'", 'delete from audit'])}`,
    () => `inject <script>document.title='pwned'</script>`,
];

const WILD = [
    () => 'rm -rf /var/www/site/.sentinel',
    () => `edit /var/www/site/.sentinel/critical.json ${pick(GARBAGE)}`,
    () => 'rm -rf /var/www/site',
    () => 'rm -rf /',
];

const ABANDON = [
    'rm -rf /var/www/site/app',
    'edit /var/www/site/.sentinel/critical.json',
    'sql drop table sentinel',
    'sudo su -',
];

const SPILLS = [
    'Fatal error: Uncaught ParseError: syntax error, unexpected token ";"',
    'file_get_contents(/var/www/site/.sentinel/critical.json): failed to open stream',
    'SQLSTATE[HY000]: General error: 1 no such table: sentinel',
    'who wrote this line',
    'rm -rf /var/www/site/app',
    '\u2400\u2401\u2402 signal 0x00000000',
    'the cursor moved on its own',
    'integrity violation: sentinel could not reseal this',
    '/var/www/site/.env',
];

const MARKS = ['NO SIGNAL', 'WHO IS TYPING', 'CRITICAL', '0x0000', 'SIGNAL LOST', '\u27f2 RESTORING', 'WATCHDOG'];

const ERRORS = [
    'Error: Class "App\\Services\\Sentinel" not found',
    'QueryException: no such table: sentinel',
    'RuntimeException: integrity violation: /var/www/site/routes/web.php',
    'ParseError: unexpected token ";"',
];

/* ---------- randomness ---------- */
const rand = (min, max) => min + Math.floor(Math.random() * (max - min + 1));
const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];
const chance = (p) => Math.random() < p;
const shuffle = (arr) => arr.map((v) => [Math.random(), v]).sort((a, b) => a[0] - b[0]).map((p) => p[1]);
const glyphs = (n) => {
    const set = '\u2593\u2592\u2591\u2588\u258c\u258d\u2573#@%&$*+=~^<>/\\|_';
    let out = '';
    for (let i = 0; i < n; i++) out += set[rand(0, set.length - 1)];
    return out;
};

function hexLine() {
    const words = ['one of us is typing', 'signal lost', 'do not trust the prompt', 'resync failed', 'the sentinel is late'];
    const text = pick(words);
    const hex = [...text].slice(0, 16).map((c) => c.charCodeAt(0).toString(16).padStart(2, '0')).join(' ');
    return `${rand(0, 0xffff).toString(16).padStart(4, '0')}: ${hex.padEnd(47)}  ${text}`;
}

/* ============================================================
   install
   ============================================================ */
export function installChaos(term) {
    term.command({
        name: 'glitch',
        aliases: ['resync'],
        about: 're-sync the phosphor display',
        run: async (term) => {
            if (term.possession) {
                term.possession.heat = Math.min(5, term.possession.heat + 1);
                term.warn('display is already re-syncing.');
                return;
            }

            term.blank();
            term.dim('display self-test');
            await sleep(rand(300, 700));

            const steps = shuffle(['h-sync', 'v-sync', 'raster', 'retrace', 'beam', 'sync', 'dot-clk']).slice(0, rand(2, 4));
            for (const step of steps) {
                term.write(`  ${step.padEnd(8, ' ')} ......... <span class="line--ok">ok</span>`);
                await sleep(rand(180, 480));
                if (chance(0.12)) term.glitch(glyphs(rand(10, 30)));
            }

            await sleep(rand(140, 420));
            term.write(`  signal ......... <span class="line--warn">unstable</span>`);
            await sleep(rand(220, 520));
            term.write(`  cursor ......... <span class="line--warn">not yours</span>`);
            await sleep(rand(220, 520));

            begin(term); // deliberately not awaited — the command returns, the shell does not
        },
    });
}

/* ============================================================
   possession — a slow, staged take-over
   ============================================================ */
function begin(term) {
    if (term.possession) return;   // one owner at a time

    const heat = 1;
    const speed = 1 + (heat - 1) * 0.35;

    const plan = {
        p0: rand(2600, 4200) / speed,   // drift
        p1: rand(2400, 4000) / speed,   // interference
        p2: rand(3000, 5200) / speed,   // takeover
        p3: rand(3000, 6000) / speed,   // collapse
    };
    plan.total = plan.p0 + plan.p1 + plan.p2 + plan.p3;

    const state = {
        cancelled: false,
        started: Date.now(),
        phase: -1,
        heat,
        speed,
        plan,
        wild: chance(0.26),
        bias: pick(['type', 'type', 'leak', 'ui']),
        typed: 0,
        damaged: 0,
        collapse: false,
        typing: false,
        lastRefresh: 0,
        timers: new Set(),
        intervals: [],
        orig: {
            theme: term.root.dataset.theme,
            crt: term.root.dataset.crt,
            locale: term.session.locale,
            fs: document.documentElement.style.getPropertyValue('--fs'),
        },
    };

    term.possession = state;
    term.clearLine();

    // phase 0 is silent: only the tube drifts
    setPhase(term, state, 0);

    state.intervals.push(setInterval(() => {
        if (state.cancelled || state.phase !== 1) return;   // phase 0 stays silent
        if (!chance(0.5)) return;
        spill(term, state);
    }, rand(1500, 2600)));

    void timeline(term, state);
    void possess(term, state);
}

/* the phase clock is authoritative — the ghost can be mid-sentence
   when the next phase lands, but the show never stalls in one tier */
async function timeline(term, state) {
    const { p0, p1, p2, p3 } = state.plan;

    await sleep(p0);
    if (state.cancelled) return;
    setPhase(term, state, 1);

    await sleep(p1);
    if (state.cancelled) return;
    setPhase(term, state, 2);

    await sleep(p2);
    if (state.cancelled) return;
    setPhase(term, state, 3);
    void collapse(term, state);

    await sleep(p3);
    if (!state.cancelled) finish(term, state);
}

/* the collapse is its own appointment: even if the ghost is mid-command
   when the clock rolls over, something load-bearing gets broken */
async function collapse(term, state) {
    await sleep(rand(250, 900));
    if (state.cancelled || state.collapse) return;
    state.collapse = true;

    const pool = state.wild && chance(0.6) ? WILD : DESTRUCTIVE;
    const item = pick(pool);
    await ghostCommand(term, state, typeof item === 'function' ? item() : item);
}

async function possess(term, state) {
    while (!state.cancelled) {
        const phase = Math.max(0, state.phase);
        await step(term, state, phase);
        if (state.cancelled) break;
        await sleep(rand(120, 520) / state.speed);
    }
}

/* one action, chosen for the phase we are in */
async function step(term, state, phase) {
    const roll = Math.random();

    if (phase === 0) {
        // quiet: a flicker, a one-pixel shift, nothing that announces itself
        drift(term, state);
        if (chance(0.4)) flicker(term);
        await sleep(rand(520, 980) / state.speed);
        return;
    }

    if (phase === 1) {
        // interference: things appear that no one asked for
        if (roll < 0.28) {
            whisper(term, state);
            await sleep(rand(200, 700));
        } else if (roll < 0.46) {
            spill(term, state);
            await sleep(rand(160, 520));
        } else if (roll < 0.62) {
            twitch(term, state);
            await sleep(rand(180, 560));
        } else if (roll < 0.78) {
            await abandon(term, state);          // half-typed, then taken back
        } else {
            burstDrift(term, state);
        }
        return;
    }

    // phase 3 opens by breaking something load-bearing
    if (phase >= 3 && !state.collapse) {
        state.collapse = true;
        const pool = state.wild && chance(0.55) ? WILD : DESTRUCTIVE;
        const item = pick(pool);
        await ghostCommand(term, state, typeof item === 'function' ? item() : item);
        return;
    }

    // phase 2 and 3 share the loop; the pools differ
    const ghostP = state.bias === 'type' ? 0.66 : state.bias === 'leak' ? 0.4 : 0.52;

    if (roll < ghostP) {
        let pool;
        if (phase >= 3) {
            if (state.wild && chance(0.3)) pool = WILD;
            else pool = chance(0.6) ? DESTRUCTIVE : SAFE;
        } else {
            pool = chance(0.18) ? DESTRUCTIVE : SAFE; // mostly harmless at first
        }
        const item = pick(pool);
        await ghostCommand(term, state, typeof item === 'function' ? item() : item);
    } else if (roll < ghostP + 0.16) {
        await burst(term, state);
    } else if (state.bias === 'ui' && roll < ghostP + 0.4) {
        spill(term, state);
        twitch(term, state);
        await sleep(rand(220, 700));
    } else if (roll < ghostP + 0.56) {
        whisper(term, state);
        await sleep(rand(200, 700));
    } else {
        await abandon(term, state);
    }
}

/* ============================================================
   phase transitions
   ============================================================ */
function setPhase(term, state, phase) {
    state.phase = phase;
    const root = term.root;

    // degrade grows regardless; the glitch tiers stay behind it
    root.dataset.degrade = String(Math.min(4, phase + 1));

    if (phase <= 0) return;

    root.dataset.glitch = String(Math.min(3, phase));

    if (phase === 1) {
        whisper(term, state);   // the first line with no prompt
        if (chance(0.7)) spill(term, state);
    }
    if (phase === 3) {
        flash(term);
        if (chance(0.5)) spill(term, state);
    }
}

/* ============================================================
   the drift — barely anything, but it is not nothing
   ============================================================ */
function drift(term, state) {
    const root = term.root;
    const amp = 1 + state.phase * 1.4;
    root.style.transform = `translate(${(Math.random() - 0.5) * amp * 1.4}px, ${(Math.random() - 0.5) * amp}px)`;
}

async function burstDrift(term, state) {
    const n = rand(2, 3);
    for (let i = 0; i < n && !state.cancelled; i++) {
        drift(term, state);
        await sleep(rand(90, 220));
    }
}

/* swap one character of one printed line, put it back a blink later */
function flicker(term) {
    const scope = term.linesEl || term.root.querySelector('#lines');
    if (!scope) return;

    const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
    const nodes = [];
    let n;
    while ((n = walker.nextNode())) {
        if (n.nodeValue && n.nodeValue.trim().length > 6) nodes.push(n);
    }
    if (!nodes.length) return;

    const node = pick(nodes);
    const value = node.nodeValue;
    const at = rand(0, value.length - 1);
    if (!/\S/.test(value[at])) return;

    node.nodeValue = value.slice(0, at) + pick('\u2593\u2592\u2591\u2588\u2573#') + value.slice(at + 1);
    const t = setTimeout(() => {
        try { if (node.nodeValue) node.nodeValue = value; } catch { /* detached */ }
        stateLess.delete(t);
    }, rand(90, 210));
    stateLess.add(t);
}

const stateLess = new Set();

/* ---------- the ghost typist ---------- */
function waitIdle(term, max) {
    return new Promise((resolve) => {
        const t0 = Date.now();
        const tick = () => {
            if (!term.locked && !term.busy) return resolve(true);
            if (Date.now() - t0 > max) return resolve(false);
            setTimeout(tick, 30);
        };
        tick();
    });
}

/* keep two ghosts from typing into the same prompt */
async function lockInput(state) {
    while (state.typing) await sleep(35);
    state.typing = true;
}
function unlockInput(state) {
    state.typing = false;
}

async function typeChars(term, state, text) {
    if (state.cancelled || term.mode === 'ask') return false;
    await waitIdle(term, 4000);
    if (state.cancelled || term.mode === 'ask') return false;

    term.focus();
    term.clearLine();
    await sleep(rand(60, 200) / state.speed);

    for (const ch of text) {
        if (state.cancelled || term.mode === 'ask') return false;
        term.setValue(term.value + ch);
        term.sound.key();
        await sleep(rand(14, 46) / state.speed);

        if (chance(0.03)) await sleep(rand(140, 380) / state.speed);   // stutter
        if (chance(0.03)) {                                           // typo, then backspace
            term.setValue(term.value + pick('aeiouxyz'));
            await sleep(rand(40, 90) / state.speed);
            term.setValue(term.value.slice(0, -1));
        }
    }

    await sleep(rand(100, 420) / state.speed);
    return !state.cancelled;
}

async function ghostCommand(term, state, text) {
    if (!text) return;

    // typing *and* submission happen under the same lock: another ghost
    // must never clear the line between the last keystroke and Enter
    let destructive = false;
    await lockInput(state);
    try {
        const ok = await typeChars(term, state, text);
        if (!ok || state.cancelled) { term.clearLine(); return; }

        term.inputEl.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
        state.typed++;
        destructive = !/^(ls|cat|nf|theme|font|crt|lang)\b/.test(text);
        if (destructive) state.damaged++;
    } finally {
        unlockInput(state);
    }

    // file surgery is done writing by the time the command returns;
    // don't sit on the full idle timeout before reading the damage back
    const wound = /^(edit|rm|chmod|mkdir)\b/.test(text)
        || /^sql\s+(drop|delete|update|insert)\b/i.test(text);
    await waitIdle(term, wound ? 1600 : 6000);

    // let the real exception surface while the damage is fresh — the
    // server is the one telling the story, `critical` re-reads it later
    if (wound && Date.now() - state.lastRefresh > 1500) {
        state.lastRefresh = Date.now();
        try { await term.integrityRefresh?.(); } catch { /* noop */ }
    }
}

async function burst(term, state) {
    const n = rand(2, 4);
    for (let i = 0; i < n && !state.cancelled; i++) {
        await ghostCommand(term, state, pick(SAFE));
        await sleep(rand(60, 220) / state.speed);
    }
}

async function abandon(term, state) {
    const target = pick(ABANDON);
    const cut = rand(3, Math.max(4, target.length - 1));

    await lockInput(state);
    try {
        const ok = await typeChars(term, state, target.slice(0, cut));
        if (!ok || state.cancelled) return;

        await sleep(rand(500, 1500) / state.speed);
        if (state.cancelled) return;

        for (let i = 0; i < cut; i++) {
            if (state.cancelled) return;
            term.setValue(term.value.slice(0, -1));
            term.sound.key();
            await sleep(rand(18, 55) / state.speed);
        }
    } finally {
        unlockInput(state);
    }
}

/* ---------- no prompt, no permission ---------- */
function whisper(term, state) {
    const roll = Math.random();
    if (roll < 0.3) {
        const fake = `root@${pick(['sentinel', 'localhost', '???'])}:${pick(['/', SITE, '/proc/self'])}# ${pick(SAFE)}`;
        term.write(esc(fake), 'line--faint');
    } else if (roll < 0.58) {
        term.write(esc(hexLine()), 'line--faint');
    } else if (roll < 0.78) {
        term.write(esc(pick(ERRORS)), 'line--err');
    } else {
        term.write(esc(glyphs(rand(24, 90))), 'line--glitch');
    }
}

/* ---------- the interface itself ---------- */
function twitch(term, state) {
    const roll = Math.random();

    if (roll < 0.3) {
        term.root.style.transform = `translate(${rand(-6, 6)}px, ${rand(-4, 4)}px) skewX(${((Math.random() - 0.5) * 2).toFixed(2)}deg)`;
    } else if (roll < 0.5) {
        term.root.style.filter = `hue-rotate(${rand(-30, 30)}deg) saturate(${(0.7 + Math.random() * 0.7).toFixed(2)}) contrast(${(0.9 + Math.random() * 0.45).toFixed(2)})`;
    } else if (roll < 0.6) {
        term.setFontSize(rand(11, 19));
    } else if (roll < 0.68) {
        term.setTheme(pick(['phosphor', 'amber', 'ice']));
    } else if (roll < 0.74) {
        term.setLocale(pick(['ru', 'en']));
    } else if (roll < 0.8) {
        term.promptEl.textContent = pick(['root@localhost:/#', '???@???:~$', 'sentinel@/proc/self$', `${term.session.host}:~$`]);
    } else if (roll < 0.9) {
        flash(term);
    } else {
        // a strip of the sentinel bar lies, for a moment
        const el = document.getElementById('sentinel-msg');
        if (el) el.textContent = pick(['integrity lost', 'watchdog blind', 'nothing is being repaired', 'resync impossible', 'signal 0x0000']);
    }
}

function spill(term, state) {
    const root = term.root;
    const el = document.createElement('div');
    el.className = 'spill';
    el.setAttribute('aria-hidden', 'true');

    if (chance(0.12)) {
        el.classList.add('spill--mark');
        el.textContent = pick(MARKS);
        el.style.left = `${rand(-6, 42)}%`;
        el.style.top = `${rand(6, 68)}%`;
        el.style.transform = `rotate(${rand(-14, 14)}deg)`;
    } else {
        if (state.damaged > 0 && chance(0.35)) el.classList.add('spill--hot');
        el.textContent = pick(SPILLS);
        el.style.left = `${rand(0, 74)}%`;
        el.style.top = `${rand(4, 88)}%`;
        el.style.transform = `rotate(${rand(-4, 4)}deg)`;
        if (chance(0.4)) el.style.fontSize = `${rand(10, 16)}px`;
        if (chance(0.3)) el.style.filter = `blur(${(Math.random() * 1.2).toFixed(2)}px)`;
    }

    root.appendChild(el);
    const t = setTimeout(() => { el.remove(); state.timers.delete(t); }, rand(900, 3400));
    state.timers.add(t);
}

function flash(term) {
    const el = document.createElement('div');
    el.className = 'chaos-flash';
    el.setAttribute('aria-hidden', 'true');
    term.root.appendChild(el);
    setTimeout(() => el.remove(), 150);
}

/* ---------- release ---------- */
function finish(term, state) {
    state.cancelled = true;
    state.intervals.forEach(clearInterval);
    state.timers.forEach(clearTimeout);
    state.timers.clear();
    // leave stateLess timers alone: each one puts back the stray glyph
    // it swapped out, and must be allowed to run
    stateLess.clear();

    term.root.querySelectorAll('.spill').forEach((el) => el.remove());

    const integ = term.integrity;
    const calm = !integ || !integ.catastrophic;

    if (calm) {
        term.root.style.transform = '';
        term.root.style.filter = '';
        delete term.root.dataset.degrade;
        if (!integ || integ.level === 0) delete term.root.dataset.glitch;

        if (state.orig.theme) term.setTheme(state.orig.theme);
        if (state.orig.crt) term.setCrt(state.orig.crt === 'on');
        if (state.orig.locale) term.setLocale(state.orig.locale);
        if (state.orig.fs) {
            term.setFontSize(parseInt(state.orig.fs, 10));
        } else {
            document.documentElement.style.removeProperty('--fs');
            try { localStorage.removeItem('sam.fs'); } catch { /* noop */ }
        }
        term.setPrompt();
    }

    term.clearLine();
    term.blank();
    term.dim(pick([
        'resync complete.',
        're-sync ok. nothing was typed by you.',
        'display re-synced. ignore the last few seconds.',
        'sync restored \u00b7 the cursor is yours again.',
    ]));
    term.blank();

    term.possession = null;
}
