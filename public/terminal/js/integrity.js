/* ============================================================
   integrity.js — the shell bleeds when its critical spots break

   Polls /shell/integrity. Any damage to the project's load-bearing
   files turns into escalating glitches, authentic PHP/Laravel
   exception traces, and — once the manifest or the whole project is
   gone — full databending: canvas feedback, RGB tearing, scrambled
   text, phantom stack traces and a layout that refuses to sit still.

   The sentinel restores everything on its next pulse; the shell then
   snaps back together.
   ============================================================ */
import { esc } from './core.js';

const POLL_MS = 3500;
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
        chaos: null,
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
        apply(term, state, data);
    };

    // let the sentinel's heal trigger an immediate re-check
    term.integrityRefresh = tick;

    setInterval(tick, POLL_MS);
    tick();
}

function apply(term, state, data) {
    state.level = Number(data.level) || 0;
    state.total = Number(data.total) || 0;
    state.catastrophic = Boolean(data.catastrophic);

    const root = term.root;

    if (state.level === 0) {
        if (state.chaos) endChaos(term, state);
        if (root.dataset.glitch) delete root.dataset.glitch;
        state.key = '';
        return;
    }

    // escalate the filter-based glitch
    const tier = state.catastrophic ? 4 : Math.min(3, 1 + Math.floor(state.level / 3));
    root.dataset.glitch = String(tier);

    if (state.catastrophic && !state.chaos) {
        beginChaos(term, state, data);
    }

    // announce the damage once per distinct damage set
    const key = state.level + '|' + (data.broken || []).map((b) => b.path + ':' + b.state).join(',') + '|' + (data.manifest_broken ? 'M' : '') + (data.db_broken ? 'D' : '');
    if (key !== state.key) {
        state.key = key;
        report(term, data, state);
    }
}

function report(term, data, state) {
    term.blank();
    term.write(
        `<span class="line--err">!! INTEGRITY FAILURE</span> ` +
        `<span class="line--dim">${data.level}/${data.total} critical spots compromised` +
        `${state.catastrophic ? ' · CATASTROPHIC' : ''}</span>`
    );

    (data.errors || []).slice(0, 4).forEach((e) => {
        term.write(`<span class="line--err">${esc(e.class)}</span> <span class="line--dim">at ${esc(e.file)}:${esc(String(e.line))}</span>`);
        term.write(`  ${esc(e.message)}`, 'line--err');
        (e.trace || []).slice(0, 3).forEach((l) => term.write(`  ${esc(l)}`, 'line--dim'));
    });

    if (state.catastrophic) {
        term.dim('the shell can no longer guarantee its own output — holding until the sentinel pulse');
    } else {
        term.dim('run `sentinel`? no. run `reset` to heal now, or wait for the pulse.');
    }
    term.blank();
}

/* ============================================================
   catastrophic mode — databending
   ============================================================ */

function beginChaos(term, state, data) {
    const root = term.root;
    if (!root) return;

    root.classList.add('is-databending');
    ensureSvgFilter();

    const canvas = document.createElement('canvas');
    canvas.className = 'databend-canvas';
    canvas.setAttribute('aria-hidden', 'true');
    root.appendChild(canvas);
    const ctx = canvas.getContext('2d', { alpha: true });

    const noise = makeNoise();
    const errors = (data.errors || []).slice(0, 6);

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

        // feedback: smear the previous frame sideways
        ctx.globalCompositeOperation = 'source-over';
        ctx.globalAlpha = 0.22;
        ctx.drawImage(canvas, rand(-9, 9), rand(-5, 5));
        ctx.globalAlpha = 1;

        // signal noise, added with screen blending
        ctx.globalCompositeOperation = 'screen';
        ctx.globalAlpha = 0.10 + Math.random() * 0.12;
        for (let i = 0; i < 3; i++) {
            ctx.drawImage(noise, rand(-noise.width, w), rand(-noise.height, h));
        }
        ctx.globalAlpha = 1;

        // torn scanlines: copy bands to random offsets
        ctx.globalCompositeOperation = 'source-over';
        for (let i = 0; i < 11; i++) {
            const sh = rand(2, 34);
            const sy = rand(0, Math.max(1, h - sh));
            const dy = rand(0, Math.max(1, h - sh));
            const dx = rand(-90, 90);
            ctx.drawImage(canvas, 0, sy, w, sh, dx, dy, w, sh);
        }

        // channel-split bands
        for (let i = 0; i < 5; i++) {
            ctx.fillStyle = `rgba(${rand(0, 255)}, ${rand(0, 255)}, ${rand(0, 255)}, 0.16)`;
            ctx.fillRect(0, rand(0, h), w, rand(1, 12));
        }

        raf = requestAnimationFrame(frame);
    };
    raf = requestAnimationFrame(frame);

    // scramble whatever text is on screen
    const originals = new Map();
    const scramble = () => {
        const scope = root.querySelector('#lines') || root;
        const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
        const nodes = [];
        let n;
        while ((n = walker.nextNode())) {
            if (n.nodeValue && n.nodeValue.trim()) nodes.push(n);
        }
        for (let i = 0; i < 12 && nodes.length; i++) {
            const node = nodes[rand(0, nodes.length - 1)];
            if (!node.nodeValue) continue;
            if (!originals.has(node)) originals.set(node, node.nodeValue);
            const s = node.nodeValue;
            const at = rand(0, Math.max(0, s.length - 1));
            const len = 1 + rand(0, 4);
            node.nodeValue = s.slice(0, at) + glyphs(len) + s.slice(at + len);
        }
    };
    const scrambleTimer = setInterval(scramble, 110);

    // the page refuses to sit still
    const jitterTimer = setInterval(() => {
        const dx = rand(-8, 8);
        const dy = rand(-5, 5);
        const scale = (1 + (Math.random() - 0.5) * 0.035).toFixed(3);
        const skew = ((Math.random() - 0.5) * 1.8).toFixed(2);
        root.style.transform = `translate(${dx}px, ${dy}px) scale(${scale}) skewX(${skew}deg)`;
        root.style.filter = `url(#db-filter) contrast(${rand(90, 190)}%) hue-rotate(${rand(-45, 45)}deg) saturate(${rand(80, 230)}%)`;

        if (Math.random() < 0.2) flash(root);
    }, 130);

    // phantom stack traces drifting over the shell
    const panelTimer = setInterval(() => {
        if (root.querySelectorAll('.phantom-error').length > 7) return;
        spawnPanel(root, errors);
    }, 520);

    // occasionally the input loses what you typed
    const inputTimer = setInterval(() => {
        if (term.mode !== 'ask' && Math.random() < 0.18 && (term.value || '').length > 3) {
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
    if (chaos.root.dataset.glitch) delete chaos.root.dataset.glitch;

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