/* ============================================================
   integrity.js — the shell bleeds when its critical spots break

   Polls /shell/integrity. Damage to the project's load-bearing files
   is reported first: authentic PHP/Laravel exception traces that the
   visitor can actually re-read from the server with `critical`.

   Only then does the display start to drift. The glitch ramps in
   tiers over several seconds, printed text slowly decays, and — once
   the manifest or the whole project is gone — the page fades into
   full databending: canvas feedback, RGB tearing, scrambled text,
   phantom stack traces and a layout that refuses to sit still.

   The sentinel restores everything on its next pulse; the shell then
   unwinds through the tiers instead of snapping back.
   ============================================================ */
import { esc, sleep } from './core.js';

const POLL_MS = 3500;
const RAMP_MS = 1100;
const CHAOS_DELAY = 3400;   // how long the real error stays readable
const CHAOS_FADE = 5200;    // how long the databending takes to take hold
const DECAY_MS = 1700;
const GLYPHS = '▓▒░█▌▐╳#@%&$*+=~^<>/\\|_';

const rand = (min, max) => min + Math.floor(Math.random() * (max - min + 1));

const glyphs = (n) => {
    let out = '';
    for (let i = 0; i < n; i++) out += GLYPHS[rand(0, GLYPHS.length - 1)];
    return out;
};

export function installIntegrity(term) {
    const state = {
        level: 0,
        total: 0,
        catastrophic: false,
        key: '',
        hint: null,

        tier: 0,
        targetTier: 0,
        ramp: null,

        chaos: null,
        pendingChaosAt: 0,

        decay: 0,
        decayTimer: null,
        decayOriginals: new Map(),

        inited: false,
    };

    term.integrity = state;

    const fetchIntegrity = async () => {
        try {
            const res = await fetch('/api/terminal/shell/integrity', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) return null;
            return await res.json();
        } catch {
            return null;
        }
    };

    const tick = async () => {
        const data = await fetchIntegrity();
        if (!data || !data.ok) return;
        await apply(term, state, data);
    };

    // let the sentinel's heal trigger an immediate re-check
    term.integrityRefresh = tick;

    setInterval(tick, POLL_MS);
    tick();
}

async function apply(term, state, data) {
    state.level = Number(data.level) || 0;
    state.total = Number(data.total) || 0;
    state.catastrophic = Boolean(data.catastrophic);
    state.hint = data;

    // report every distinct damage set exactly once, with the real error
    const key = state.level + '|'
        + (data.broken || []).map((b) => b.path + ':' + b.state).join(',')
        + '|' + (data.manifest_broken ? 'M' : '')
        + (data.db_broken ? 'D' : '');

    if (state.level > 0 && key !== state.key) {
        state.key = key;
        await report(term, data, state);
    }

    if (state.level === 0) {
        calm(term, state);
        return;
    }

    state.targetTier = state.catastrophic
        ? 4
        : Math.min(3, 1 + Math.floor((state.level - 1) / 3));

    ensureRamp(term, state);
    if (!state.chaos) startDecay(term, state);

    if (state.catastrophic) {
        if (!state.pendingChaosAt && !state.chaos) {
            state.pendingChaosAt = Date.now() + CHAOS_DELAY + rand(0, 900);
        }
    } else {
        state.pendingChaosAt = 0;
    }
}

/* ---------- the error, before the show ---------- */
async function report(term, data, state) {
    term.blank();
    term.write(
        `<span class="line--err">!! INTEGRITY FAILURE</span> ` +
        `<span class="line--dim">${data.level}/${data.total} critical spots compromised` +
        `${state.catastrophic ? ' · CATASTROPHIC' : ''}</span>`
    );
    await sleep(140);

    const errors = (data.errors || []).slice(0, 4);
    for (const e of errors) {
        term.write(`<span class="line--err">${esc(e.class)}</span> <span class="line--dim">at ${esc(e.file)}:${esc(String(e.line))}</span>`);
        term.write(`  ${esc(e.message)}`, 'line--err');
        (e.trace || []).slice(0, 3).forEach((l) => term.write(`  ${esc(l)}`, 'line--dim'));
        await sleep(120);
    }

    term.dim(state.catastrophic
        ? 'the failure is real · run `critical` to read it again from the server · sync is going'
        : 'the failure is real · run `critical` to read it again from the server · sync is drifting');
    term.blank();
}

/* ---------- the tier ramp ---------- */
function ensureRamp(term, state) {
    if (state.ramp) return;

    state.ramp = setInterval(() => {
        if (state.level === 0 && state.tier <= 0) {
            clearInterval(state.ramp);
            state.ramp = null;
            if (!term.possession) {
                delete term.root.dataset.glitch;
                delete term.root.dataset.degrade;
            }
            return;
        }

        if (state.tier < state.targetTier) state.tier++;
        else if (state.tier > state.targetTier) state.tier = Math.max(0, state.tier - 1);

        applyTier(term, state);

        if (state.pendingChaosAt && state.catastrophic && !state.chaos
            && Date.now() >= state.pendingChaosAt) {
            state.pendingChaosAt = 0;
            state.tier = 4;
            applyTier(term, state);
            beginChaos(term, state, state.hint || {});
        }
    }, RAMP_MS);
}

function applyTier(term, state) {
    // while `glitch` owns the display, it handles its own tiers
    if (term.possession) return;

    const root = term.root;
    if (state.tier <= 0) {
        if (root.dataset.glitch) delete root.dataset.glitch;
        if (root.dataset.degrade) delete root.dataset.degrade;
        return;
    }
    root.dataset.glitch = String(Math.min(3, state.tier));
    // focus follows the same ramp, a step behind the tearing
    root.dataset.degrade = String(Math.min(4, state.tier + 1));
}

/* ---------- slow text decay ---------- */
function startDecay(term, state) {
    if (state.decayTimer || state.chaos) return;

    state.decay = Math.min(0.12, state.decay + 0.04);
    corrupt(term, state);

    state.decayTimer = setInterval(() => {
        if (state.level === 0) return;
        state.decay = Math.min(0.8, state.decay + 0.045);
        corrupt(term, state);
    }, DECAY_MS);
}

function corrupt(term, state) {
    const scope = term.linesEl || term.root.querySelector('#lines');
    if (!scope) return;

    const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
    const nodes = [];
    let n;
    while ((n = walker.nextNode())) {
        if (n.nodeValue && n.nodeValue.trim().length > 4) nodes.push(n);
    }
    if (!nodes.length) return;

    const count = Math.max(1, Math.round(state.decay * 4));
    for (let i = 0; i < count; i++) {
        const node = nodes[rand(0, nodes.length - 1)];
        if (!node.nodeValue) continue;
        if (!state.decayOriginals.has(node)) state.decayOriginals.set(node, node.nodeValue);
        const s = node.nodeValue;
        const at = rand(0, Math.max(0, s.length - 1));
        const len = 1 + rand(0, 2);
        node.nodeValue = s.slice(0, at) + glyphs(len) + s.slice(at + len);
    }
}

function freezeDecay(state) {
    if (state.decayTimer) {
        clearInterval(state.decayTimer);
        state.decayTimer = null;
    }
}

function stopDecay(term, state) {
    freezeDecay(state);
    state.decay = 0;
    for (const [node, value] of state.decayOriginals) {
        try { node.nodeValue = value; } catch { /* detached */ }
    }
    state.decayOriginals.clear();
}

/* ---------- calm ---------- */
function calm(term, state) {
    state.key = '';
    state.targetTier = 0;
    state.pendingChaosAt = 0;

    if (state.chaos) {
        endChaos(term, state);
        state.tier = 3; // unwind through the tiers instead of snapping clear
    }

    stopDecay(term, state);
    ensureRamp(term, state);

    if (!term.possession && state.tier <= 0 && !state.ramp) {
        delete term.root.dataset.glitch;
        delete term.root.dataset.degrade;
    }
}

/* ============================================================
   catastrophic mode — databending
   ============================================================ */

function beginChaos(term, state, data) {
    const root = term.root;
    if (!root || state.chaos) return;

    freezeDecay(state); // the scramble takes over from here

    root.classList.add('is-databending');
    ensureSvgFilter();

    const canvas = document.createElement('canvas');
    canvas.className = 'databend-canvas';
    canvas.setAttribute('aria-hidden', 'true');
    canvas.style.opacity = '0.05';
    root.appendChild(canvas);
    const ctx = canvas.getContext('2d', { alpha: true });

    const noise = makeNoise();
    const errors = (data.errors || []).slice(0, 6);
    const startedAt = performance.now();
    const growth = () => Math.min(1, (performance.now() - startedAt) / CHAOS_FADE);

    const resize = () => {
        canvas.width = Math.max(1, root.clientWidth);
        canvas.height = Math.max(1, root.clientHeight);
    };
    resize();
    window.addEventListener('resize', resize);

    let raf = 0;
    const frame = () => {
        const w = canvas.width;
        const h = canvas.height;
        if (!w || !h) {
            raf = requestAnimationFrame(frame);
            return;
        }

        const t = growth();
        canvas.style.opacity = (0.1 + 0.8 * t).toFixed(3);

        // feedback: smear the previous frame sideways
        ctx.globalCompositeOperation = 'source-over';
        ctx.globalAlpha = 0.06 + 0.16 * t;
        ctx.drawImage(canvas, rand(-9, 9), rand(-5, 5));
        ctx.globalAlpha = 1;

        // signal noise, added with screen blending
        ctx.globalCompositeOperation = 'screen';
        ctx.globalAlpha = 0.04 + 0.1 * t;
        const puffs = 1 + Math.round(2 * t);
        for (let i = 0; i < puffs; i++) {
            ctx.drawImage(noise, rand(-noise.width, w), rand(-noise.height, h));
        }
        ctx.globalAlpha = 1;

        // torn scanlines: copy bands to random offsets
        ctx.globalCompositeOperation = 'source-over';
        const bands = Math.round(2 + 9 * t);
        for (let i = 0; i < bands; i++) {
            const sh = rand(2, 34);
            const sy = rand(0, Math.max(1, h - sh));
            const dy = rand(0, Math.max(1, h - sh));
            const dx = rand(-1, 1) * Math.round(20 + 70 * t);
            ctx.drawImage(canvas, 0, sy, w, sh, dx, dy, w, sh);
        }

        // channel-split bands
        const splits = Math.round(1 + 4 * t);
        for (let i = 0; i < splits; i++) {
            ctx.fillStyle = `rgba(${rand(0, 255)}, ${rand(0, 255)}, ${rand(0, 255)}, ${(0.06 + 0.12 * t).toFixed(3)})`;
            ctx.fillRect(0, rand(0, h), w, rand(1, 12));
        }

        raf = requestAnimationFrame(frame);
    };
    raf = requestAnimationFrame(frame);

    // scramble whatever text is on screen — sparse at first
    const originals = new Map();
    const scramble = () => {
        const t = growth();
        if (Math.random() > 0.2 + 0.8 * t) return;

        const scope = root.querySelector('#lines') || root;
        const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
        const nodes = [];
        let n;
        while ((n = walker.nextNode())) {
            if (n.nodeValue && n.nodeValue.trim()) nodes.push(n);
        }

        const count = Math.max(1, Math.round(1 + 11 * t));
        for (let i = 0; i < count && nodes.length; i++) {
            const node = nodes[rand(0, nodes.length - 1)];
            if (!node.nodeValue) continue;
            if (!originals.has(node)) originals.set(node, node.nodeValue);
            const s = node.nodeValue;
            const at = rand(0, Math.max(0, s.length - 1));
            const len = 1 + rand(0, 4);
            node.nodeValue = s.slice(0, at) + glyphs(len) + s.slice(at + len);
        }
    };
    const scrambleTimer = setInterval(scramble, 120);

    // the page refuses to sit still — amplitude grows as it takes hold
    const jitterTimer = setInterval(() => {
        const t = growth();
        const amp = 0.2 + 0.8 * t;
        const dx = rand(-8, 8) * amp;
        const dy = rand(-5, 5) * amp;
        const scale = 1 + (Math.random() - 0.5) * 0.035 * amp;
        const skew = (Math.random() - 0.5) * 1.8 * amp;
        root.style.transform = `translate(${dx.toFixed(1)}px, ${dy.toFixed(1)}px) scale(${scale.toFixed(3)}) skewX(${skew.toFixed(2)}deg)`;
        root.style.filter = `url(#db-filter) contrast(${Math.round(100 + 40 * t)}%) hue-rotate(${Math.round(rand(-45, 45) * t)}deg) saturate(${Math.round(100 + 60 * t)}%)`;

        if (Math.random() < 0.05 + 0.2 * t) flash(root);
    }, 150);

    // phantom stack traces drift in once the signal is really gone
    const panelTimer = setInterval(() => {
        const t = growth();
        if (t < 0.12) return;
        if (Math.random() > 0.2 + 0.8 * t) return;
        if (root.querySelectorAll('.phantom-error').length > 7) return;
        spawnPanel(root, errors);
    }, 620);

    // occasionally the input loses what you typed
    const inputTimer = setInterval(() => {
        const t = growth();
        if (term.mode !== 'ask' && Math.random() < 0.04 + 0.18 * t && (term.value || '').length > 3) {
            term.clearLine();
        }
    }, 240);

    state.chaos = {
        root,
        canvas,
        raf,
        resize,
        timers: [scrambleTimer, jitterTimer, panelTimer, inputTimer],
        originals,
    };
}

function endChaos(term, state) {
    const chaos = state.chaos;
    if (!chaos) return;
    state.chaos = null;

    cancelAnimationFrame(chaos.raf);
    window.removeEventListener('resize', chaos.resize);
    chaos.timers.forEach((t) => clearInterval(t));

    chaos.canvas.remove();
    chaos.root.querySelectorAll('.phantom-error').forEach((el) => el.remove());
    chaos.root.querySelectorAll('.chaos-flash').forEach((el) => el.remove());

    chaos.originals.forEach((value, node) => {
        try { node.nodeValue = value; } catch { /* detached */ }
    });

    chaos.root.classList.remove('is-databending');
    chaos.root.style.transform = '';
    chaos.root.style.filter = '';
    if (!term.possession) delete chaos.root.dataset.degrade;

    const overlay = document.getElementById('recovery');
    if (overlay) {
        overlay.classList.add('is-on');
        setTimeout(() => overlay.classList.remove('is-on'), 900);
    }

    term.ok('⟲ [sentinel] core integrity restored — every canary resealed');
    term.dim('the shell is whole again. it always comes back.');
    term.blank();
}

/* ============================================================
   helpers
   ============================================================ */

function makeNoise() {
    const tile = document.createElement('canvas');
    tile.width = 96;
    tile.height = 96;
    const ctx = tile.getContext('2d');
    const img = ctx.createImageData(tile.width, tile.height);
    const d = img.data;

    for (let i = 0; i < d.length; i += 4) {
        const v = Math.random() < 0.5 ? rand(0, 70) : rand(170, 255);
        d[i] = v;
        d[i + 1] = Math.random() < 0.15 ? rand(120, 255) : v;
        d[i + 2] = Math.random() < 0.15 ? rand(120, 255) : v;
        d[i + 3] = 255;
    }
    ctx.putImageData(img, 0, 0);

    return tile;
}

let svgReady = false;
function ensureSvgFilter() {
    if (svgReady || document.getElementById('db-filter')) {
        svgReady = true;
        return;
    }

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '0');
    svg.setAttribute('height', '0');
    svg.setAttribute('aria-hidden', 'true');
    svg.style.position = 'absolute';
    svg.style.pointerEvents = 'none';

    svg.innerHTML = `
        <filter id="db-filter" x="-12%" y="-12%" width="124%" height="124%">
            <feTurbulence type="fractalNoise" baseFrequency="0.0009 0.05" numOctaves="2" seed="3" result="noise">
                <animate attributeName="seed" values="1;5;2;8;3;6;1" dur="0.45s" repeatCount="indefinite" />
            </feTurbulence>
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="16" xChannelSelector="R" yChannelSelector="G" />
        </filter>`;

    document.body.appendChild(svg);
    svgReady = true;
}

function flash(root) {
    const el = document.createElement('div');
    el.className = 'chaos-flash';
    el.setAttribute('aria-hidden', 'true');
    root.appendChild(el);
    setTimeout(() => el.remove(), 140);
}

function spawnPanel(root, errors) {
    const err = errors.length
        ? errors[rand(0, errors.length - 1)]
        : { class: 'RuntimeException', message: 'segmentation fault (core dumped)', file: '/var/www/site', line: 1, trace: [] };

    const el = document.createElement('div');
    el.className = 'phantom-error';
    el.style.left = rand(0, 55) + '%';
    el.style.top = rand(5, 80) + '%';

    const trace = (err.trace || []).slice(0, 2).map((l) => esc(l)).join('\n');
    el.innerHTML =
        `<b>${esc(err.class)}</b>\n` +
        `${esc(err.message)}\n` +
        `<span class="dim">at ${esc(err.file)}:${esc(String(err.line))}</span>` +
        (trace ? `\n${trace}` : '');

    root.appendChild(el);
    setTimeout(() => el.remove(), rand(900, 2600));
}