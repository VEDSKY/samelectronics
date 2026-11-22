/* ============================================================
   core.js — terminal kernel
   renderer · input · history · completion · sound · api · keyboard
   ============================================================ */

/* ---------- tiny utils ---------- */
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export const esc = (s) =>
    String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

export const padCols = (s, n, align = 'left') => {
    const clean = String(s).replace(/\x1b\[[0-9;]*m/g, '');
    const diff = Math.max(0, n - clean.length);
    if (align === 'right') return ' '.repeat(diff) + s;
    if (align === 'center') return ' '.repeat(Math.floor(diff / 2)) + s + ' '.repeat(Math.ceil(diff / 2));
    return s + ' '.repeat(diff);
};

/* ---------- audio: soft keyclicks ---------- */
class Sound {
    constructor() {
        this.enabled = true;
        this.ctx = null;
    }
    ensure() {
        if (!this.ctx) {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return null;
            this.ctx = new AC();
        }
        if (this.ctx.state === 'suspended') this.ctx.resume();
        return this.ctx;
    }
    blip(freq = 620, dur = 0.018, gain = 0.028, type = 'square') {
        if (!this.enabled) return;
        const ctx = this.ensure();
        if (!ctx) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = type;
        o.frequency.value = freq;
        o.connect(g).connect(ctx.destination);
        const t = ctx.currentTime;
        g.gain.setValueAtTime(gain, t);
        g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
        o.start(t);
        o.stop(t + dur);
    }
    key() { this.blip(600 + Math.random() * 240, 0.012, 0.02); }
    enter() { this.blip(300, 0.05, 0.03, 'triangle'); }
    ok() { this.blip(880, 0.06, 0.03, 'sine'); setTimeout(() => this.blip(1320, 0.08, 0.025, 'sine'), 70); }
    err() { this.blip(180, 0.12, 0.035, 'sawtooth'); }
    type() { this.blip(1400, 0.008, 0.012, 'square'); }
}

/* ---------- api client ---------- */
export class Api {
    constructor(base = '/api/terminal') {
        this.base = base;
        this.online = false;
        this.token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
    headers() {
        return {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': this.token,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }
    async call(method, path, body) {
        const res = await fetch(this.base + path, {
            method,
            headers: this.headers(),
            credentials: 'same-origin',
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        this.online = true;
        let data = null;
        try { data = await res.json(); } catch { /* non-json */ }
        if (!res.ok && data === null) throw new Error(`http ${res.status}`);
        return data ?? { ok: false, error: `http ${res.status}` };
    }
    get(p) { return this.call('GET', p); }
    post(p, b) { return this.call('POST', p, b); }
    patch(p, b) { return this.call('PATCH', p, b); }
    del(p) { return this.call('DELETE', p); }
}

/* ============================================================
   Terminal
   ============================================================ */
export class Terminal {
    constructor(root) {
        this.root = root;
        this.linesEl = root.querySelector('#lines');
        this.screenEl = root.querySelector('#screen');
        this.inputEl = root.querySelector('#cmd-input');
        this.textEl = root.querySelector('#input-text');
        this.ghostEl = root.querySelector('#ghost');
        this.promptEl = root.querySelector('#prompt');
        this.cmdEl = root.querySelector('#cmdline');

        this.sound = new Sound();
        this.api = new Api();

        this.commands = new Map();
        this.aliases = new Map();
        this.history = [];
        this.hIndex = 0;
        this.value = '';
        this.busy = false;
        this.locked = false;
        this.mode = 'command';       // 'command' | 'ask'
        this._askCtx = null;
        this.session = { user: null, host: 'samelectronics', cwd: '~', locale: 'ru' };
        this.env = { boot: window.__TERMINAL__ || {} };

        this._bindInput();
        this._startClock();
        this._bindGlobal();
        this.setPrompt();
    }

    /* ---------- registry ---------- */
    command(spec) {
        this.commands.set(spec.name, spec);
        (spec.aliases || []).forEach((a) => this.aliases.set(a, spec.name));
        return this;
    }
    resolve(name) {
        if (!name) return null;
        if (this.commands.has(name)) return this.commands.get(name);
        const target = this.aliases.get(name);
        return target ? this.commands.get(target) : null;
    }
    commandNames() { return [...this.commands.keys()].sort(); }
    completions() { return [...this.commands.keys(), ...this.aliases.keys()].sort(); }

    /* ---------- rendering ---------- */
    write(html = '', cls = 'line--out') {
        const div = document.createElement('div');
        div.className = `line ${cls}`;
        div.innerHTML = html === '' ? '&nbsp;' : html;
        this.linesEl.appendChild(div);
        this.scroll();
        return div;
    }
    blank() { return this.write('', 'line--blank'); }
    rule(label = '') {
        const cols = Math.max(24, Math.min(86, Math.floor(this.screenEl.clientWidth / 8)));
        if (!label) return this.write('─'.repeat(cols), 'line--rule');
        const text = `─ ${label} `;
        return this.write(text + '─'.repeat(Math.max(0, cols - text.length)), 'line--rule');
    }
    dim(x) { return this.write(esc(x), 'line--dim'); }
    faint(x) { return this.write(esc(x), 'line--faint'); }
    out(x) { return this.write(esc(x), 'line--out'); }
    info(x) { return this.write(esc(x), 'line--sys'); }
    ok(x) { return this.write('✔ ' + esc(x), 'line--ok'); }
    warn(x) { return this.write('! ' + esc(x), 'line--warn'); }
    err(x) { return this.write('✘ ' + esc(x), 'line--err'); }
    label(x) { return this.write(esc(x), 'line--label'); }
    h1(x) { return this.write(esc(x), 'line--h1'); }
    ascii(x) { return this.write(esc(x), 'line--ascii'); }
    glitch(x) { return this.write(esc(x), 'line--glitch'); }

    table(headers, rows, aligns = []) {
        const cols = headers.map((h, i) =>
            Math.min(40, Math.max(String(h).length, ...rows.map((r) => String(r[i] ?? '').length))));
        const line = (cells, cls) => this.write(cells.map((c, i) => padCols(c, cols[i], aligns[i] || 'left')).join('  '), cls);
        line(headers, 'line--table');
        line(headers.map((h, i) => '─'.repeat(cols[i])), 'line--rule');
        rows.forEach((r) => line(r, 'line--table'));
    }

    async progress(label, ms = 800) {
        const div = this.write(`${esc(label)} <span class="progress" style="--p:0%"></span>`, 'line--out');
        const bar = div.querySelector('.progress');
        const steps = 24;
        for (let i = 1; i <= steps; i++) {
            bar.style.setProperty('--p', `${Math.round((i / steps) * 100)}%`);
            await sleep(ms / steps);
        }
        bar.remove();
        div.innerHTML = `<span class="line--ok">✔ ${esc(label)}</span>`;
        return div;
    }

    clear() { this.linesEl.innerHTML = ''; }
    scroll() { requestAnimationFrame(() => { this.screenEl.scrollTop = this.screenEl.scrollHeight; }); }

    busyOn(label = 'working') {
        this.busy = true;
        this.cmdEl.classList.add('is-busy');
        this._busyLine = this.write(`<span class="line--dim">${esc(label)}…</span>`, 'line--dim');
        return this;
    }
    busyOff() {
        this.busy = false;
        this.cmdEl.classList.remove('is-busy');
        if (this._busyLine) { this._busyLine.remove(); this._busyLine = null; }
        return this;
    }

    /* ---------- prompt / status ---------- */
    setPrompt() {
        const user = this.session.user ? this.session.user.name : 'visitor';
        this.promptEl.textContent = `${user}@${this.session.host}:${this.session.cwd}$`;
        this.setStatus('status-user', this.session.user ? this.session.user.name : 'visitor');
        this.setStatus('status-path', this.session.cwd);
    }
    setStatus(id, text, cls) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = text;
        if (cls !== undefined) el.className = `statusbar__seg ${cls}`;
    }
    _startClock() {
        const tick = () => {
            const d = new Date();
            const p = (n) => String(n).padStart(2, '0');
            this.setStatus('status-clock', `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`);
        };
        tick();
        setInterval(tick, 1000);
    }
    _bindGlobal() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.keyboard') || e.target.closest('a')) return;
            this.focus();
        });
        window.addEventListener('resize', () => this.scroll());
    }
    focus() { try { this.inputEl.focus({ preventScroll: true }); } catch { /* noop */ } }

    /* ---------- input ---------- */
    _bindInput() {
        const inp = this.inputEl;

        inp.addEventListener('input', () => this.setValue(inp.value));

        inp.addEventListener('keydown', async (e) => {
            this.highlightPhysical(e);

            if (e.ctrlKey || e.metaKey) {
                const k = e.key.toLowerCase();
                if (k === 'l') { e.preventDefault(); this.clear(); this.sound.key(); return; }
                if (k === 'k') { e.preventDefault(); this.toggleKeyboard(); return; }
                if (k === 'c') { e.preventDefault(); this._cancel(true); return; }
                if (k === 'u') { e.preventDefault(); this.clearLine(); return; }
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.mode !== 'ask' && this.locked) {
                    this.sound.err();
                    this.cmdEl.classList.add('is-deny');
                    setTimeout(() => this.cmdEl.classList.remove('is-deny'), 400);
                    return;
                }
                this.sound.enter();
                const raw = this.value.trim();
                this.clearLine();
                if (this.mode === 'ask' && this._askCtx) { this._answerAsk(raw); return; }
                this.echoLine(raw);
                if (!raw) return;
                this._remember(raw);
                this._handle(raw);
                return;
            }
            if (e.key === 'Tab') { e.preventDefault(); this._complete(); return; }
            if (e.key === 'ArrowUp') { e.preventDefault(); this._history(-1); return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); this._history(1); return; }
            if (e.key === 'Escape') { e.preventDefault(); this._cancel(false); return; }
            if (e.key.length === 1) this.sound.key();
        });
    }

    setValue(v) {
        this.value = v;
        this.inputEl.value = v;
        this.textEl.textContent = v;
        this.ghostEl.textContent = '';
        this.scroll();
    }
    clearLine() { this.setValue(''); }
    echoLine(value, suffix = '') {
        const user = this.session.user ? this.session.user.name : 'visitor';
        const prompt = `${user}@${this.session.host}:${this.session.cwd}$`;
        return this.write(`<span class="line__prompt">${esc(prompt)}</span> ${esc(value)}${esc(suffix)}`, 'line--in');
    }
    _remember(raw) {
        if (this.history[this.history.length - 1] !== raw) this.history.push(raw);
        this.hIndex = this.history.length;
    }
    _history(dir) {
        if (!this.history.length) return;
        this.hIndex = Math.max(0, Math.min(this.history.length, this.hIndex + dir));
        this.setValue(this.hIndex >= this.history.length ? '' : this.history[this.hIndex]);
        this.sound.key();
    }
    _complete() {
        const v = this.value;
        const parts = v.split(/\s+/);
        if (parts.length <= 1 && !v.endsWith(' ')) {
            const typed = parts[0] || '';
            const matches = this.completions().filter((c) => c.startsWith(typed));
            if (matches.length === 1) return this.setValue(matches[0] + ' ');
            if (matches.length > 1) {
                const common = this._commonPrefix(matches);
                if (common.length > typed.length) return this.setValue(common);
                this.echoLine(v);
                this.write(matches.join('   '), 'line--dim');
                return this.setValue(v);
            }
            return;
        }
        const cmd = this.resolve(parts[0]);
        if (cmd && typeof cmd.complete === 'function') {
            const suggestions = (cmd.complete(parts.slice(1), v) || []).map(String);
            const typed = parts[parts.length - 1] || '';
            const matches = suggestions.filter((s) => s.startsWith(typed));
            if (matches.length === 1) { parts[parts.length - 1] = matches[0]; return this.setValue(parts.join(' ') + ' '); }
            if (matches.length > 1) {
                this.echoLine(v);
                this.write(matches.join('   '), 'line--dim');
                this.setValue(v);
            }
        }
    }
    _commonPrefix(arr) {
        if (!arr.length) return '';
        let p = arr[0];
        for (const s of arr) while (!s.startsWith(p)) p = p.slice(0, -1);
        return p;
    }
    _cancel(fromCtrlC) {
        if (this.mode === 'ask' && this._askCtx) {
            const ctx = this._askCtx;
            this._askCtx = null;
            this.mode = 'command';
            ctx.line.innerHTML += `<span class="line--err"> ^C</span>`;
            this.clearLine();
            ctx.reject(new Error('aborted'));
            return;
        }
        if (this.busy) { this.warn('aborted'); return; }
        if (this.value) { this.echoLine(this.value, '^C'); this.clearLine(); }
        else if (fromCtrlC) this.dim('^C');
    }

    /* ---------- interactive ask ---------- */
    ask(prompt, opts = {}) {
        const { mask = false, defaultValue = '', validate = null, cls = 'line--warn' } = opts;
        return new Promise((resolve, reject) => {
            this.mode = 'ask';
            const line = this.write(`<span class="k">${esc(prompt)}</span> `, cls);
            this._askCtx = { prompt, mask, defaultValue, validate, resolve, reject, cls, line };
            this.focus();
        });
    }
    _answerAsk(raw) {
        const ctx = this._askCtx;
        const value = raw === '' ? ctx.defaultValue : raw;
        if (ctx.validate) {
            const res = ctx.validate(value);
            if (res !== true) {
                ctx.line.innerHTML += `<span class="line--err"> ✘ ${esc(res || 'invalid')}</span>`;
                ctx.line = this.write(`<span class="k">${esc(ctx.prompt)}</span> `, ctx.cls);
                return;
            }
        }
        ctx.line.innerHTML = `<span class="k">${esc(ctx.prompt)}</span> <span class="v">${esc(ctx.mask ? '•'.repeat(String(value).length) : String(value))}</span>`;
        this._askCtx = null;
        this.mode = 'command';
        ctx.resolve(value);
    }
    confirm(prompt) {
        return this.ask(`${prompt} [y/N]`, { defaultValue: 'n' }).then((v) => /^y(es)?$/i.test(String(v).trim()));
    }

    /* ---------- dispatch ---------- */
    async _handle(raw) {
        const [name, ...args] = raw.split(/\s+/);
        const cmd = this.resolve(name);
        if (!cmd) {
            this.sound.err();
            this.err(`command not found: ${name}`);
            const sugg = this.commandNames().filter((c) => c.startsWith((name[0] || '')));
            if (sugg.length) this.dim(`did you mean: ${sugg.slice(0, 6).join(', ')} ?`);
            this.dim('type `help` to list commands');
            return;
        }
        this.locked = true;
        try {
            await cmd.run(this, args, raw);
        } catch (e) {
            if (e && e.message === 'aborted') return;
            this.sound.err();
            this.err(e?.message || String(e));
        } finally {
            this.locked = false;
            this.setPrompt();
        }
    }

    /* ---------- on-screen keyboard ---------- */
    setupKeyboard(rowsEl, modesEl) {
        this.kbdModes = {
            latin: [
                ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
                ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
                ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', "'"],
                ['z', 'x', 'c', 'v', 'b', 'n', 'm', '.', '/', '-'],
                [
                    { k: 'TAB', label: 'TAB', cls: 'key--mod', act: 'tab' },
                    { k: ' ', label: 'space', cls: 'key--space', act: 'space' },
                    { k: 'BACKSPACE', label: '⌫ del', cls: 'key--mod', act: 'back' },
                    { k: 'ENTER', label: '↵ enter', cls: 'key--mod key--wide', act: 'enter' },
                ],
            ],
            cyrillic: [
                ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
                ['й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з'],
                ['ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж'],
                ['я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', 'э'],
                [
                    { k: 'TAB', label: 'TAB', cls: 'key--mod', act: 'tab' },
                    { k: ' ', label: 'пробел', cls: 'key--space', act: 'space' },
                    { k: 'BACKSPACE', label: '⌫ del', cls: 'key--mod', act: 'back' },
                    { k: 'ENTER', label: '↵ enter', cls: 'key--mod key--wide', act: 'enter' },
                ],
            ],
            symbols: [
                ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')'],
                ['-', '_', '=', '+', '[', ']', '{', '}', '\\', '|'],
                [';', ':', '"', "'", '<', '>', ',', '.', '?', '/'],
                ['~', '`', '€', '«', '»', '—', '№', '·', '•', '§'],
                [
                    { k: 'TAB', label: 'TAB', cls: 'key--mod', act: 'tab' },
                    { k: ' ', label: 'space', cls: 'key--space', act: 'space' },
                    { k: 'BACKSPACE', label: '⌫ del', cls: 'key--mod', act: 'back' },
                    { k: 'ENTER', label: '↵ enter', cls: 'key--mod key--wide', act: 'enter' },
                ],
            ],
        };
        this.kbdMode = 'latin';
        this.kbdRowsEl = rowsEl;
        this._renderKeyboard();
        modesEl?.querySelectorAll('[data-kbd-mode]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.kbdMode = btn.dataset.kbdMode;
                modesEl.querySelectorAll('[data-kbd-mode]').forEach((b) => b.classList.toggle('is-active', b === btn));
                this._renderKeyboard();
                this.sound.key();
                this.focus();
            });
        });
        document.getElementById('keyboard-hide')?.addEventListener('click', (e) => { e.stopPropagation(); this.toggleKeyboard(); });
    }
    _renderKeyboard() {
        this.kbdRowsEl.innerHTML = '';
        this._keyMap = new Map();
        this.kbdModes[this.kbdMode].forEach((row) => {
            const r = document.createElement('div');
            r.className = 'krow';
            row.forEach((key) => {
                const spec = typeof key === 'string' ? { k: key, label: key, act: 'char' } : { act: 'char', ...key };
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `key ${spec.cls || ''}`;
                btn.textContent = spec.label ?? spec.k;
                btn.tabIndex = -1;
                btn.addEventListener('click', (e) => { e.stopPropagation(); this._keyPress(spec, btn); });
                if (spec.act === 'char') this._keyMap.set(spec.k, btn);
                if (spec.act === 'enter') this._keyMap.set('ENTER', btn);
                if (spec.act === 'tab') this._keyMap.set('TAB', btn);
                if (spec.act === 'back') this._keyMap.set('BACKSPACE', btn);
                if (spec.act === 'space') this._keyMap.set(' ', btn);
                r.appendChild(btn);
            });
            this.kbdRowsEl.appendChild(r);
        });
    }
    _keyPress(spec, btn) {
        this.focus();
        if (spec.act === 'char') { this._insert(spec.k); this.sound.key(); }
        else if (spec.act === 'space') { this._insert(' '); this.sound.key(); }
        else if (spec.act === 'tab') this._complete();
        else if (spec.act === 'back') { this.setValue(this.value.slice(0, -1)); this.sound.key(); }
        else if (spec.act === 'enter') this.inputEl.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
        if (btn) this._flashKey(btn);
    }
    _insert(ch) { this.setValue(this.value + ch); }
    _flashKey(btn) {
        btn.classList.add('is-hit');
        setTimeout(() => btn.classList.remove('is-hit'), 110);
    }
    highlightPhysical(e) {
        if (!this._keyMap) return;
        let key = null;
        if (e.key === 'Enter') key = 'ENTER';
        else if (e.key === 'Tab') key = 'TAB';
        else if (e.key === 'Backspace') key = 'BACKSPACE';
        else if (e.key === ' ') key = ' ';
        else if (e.key.length === 1) key = e.key.toLowerCase();
        const btn = key && this._keyMap.get(key);
        if (btn) this._flashKey(btn);
    }
    toggleKeyboard() {
        const kb = document.getElementById('keyboard');
        kb.classList.toggle('is-hidden');
        this.sound.key();
        const hint = document.getElementById('hints-hint');
        if (hint) hint.innerHTML = kb.classList.contains('is-hidden')
            ? 'kbd panel hidden · <b>Ctrl</b>+<b>K</b> to show'
            : 'type <b>help</b> and press enter';
        this.scroll();
    }

    /* ---------- prefs ---------- */
    setTheme(name) {
        this.root.dataset.theme = name;
        document.documentElement.dataset.theme = name;
        try { localStorage.setItem('sam.theme', name); } catch { /* noop */ }
    }
    setCrt(on) {
        this.root.dataset.crt = on ? 'on' : 'off';
        try { localStorage.setItem('sam.crt', on ? 'on' : 'off'); } catch { /* noop */ }
    }
    setSound(on) {
        this.sound.enabled = on;
        this.root.dataset.sound = on ? 'on' : 'off';
        try { localStorage.setItem('sam.sound', on ? 'on' : 'off'); } catch { /* noop */ }
    }
    setFontSize(px) {
        const v = Math.max(11, Math.min(22, px));
        document.documentElement.style.setProperty('--fs', `${v}px`);
        try { localStorage.setItem('sam.fs', String(v)); } catch { /* noop */ }
        return v;
    }
    setLocale(loc) {
        this.session.locale = loc;
        document.documentElement.lang = loc;
        try { localStorage.setItem('sam.locale', loc); } catch { /* noop */ }
    }
}
