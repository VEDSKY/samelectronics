/* ============================================================
   commands.js — the command registry
   ============================================================ */
import {
    LOGO, WORDMARK, TAGLINE, MOTD, I18N, SECTIONS, CONTENT,
    ALBUMS, TRACKS, PHOTOS, HELP_TOPICS, NAME_ART,
} from './content.js';
import { esc, sleep } from './core.js';

/* ---------- helpers ---------- */
const t = (term) => I18N[term.session.locale] || I18N.ru;

async function fetchContent(term, slug) {
    try {
        const res = await term.api.get(`/content/${slug}`);
        if (res && res.ok) return { data: res, offline: false };
    } catch { /* offline */ }
    return { data: CONTENT[slug] || null, offline: true };
}

async function fetchCatalog(term) {
    try {
        const res = await term.api.get('/catalog');
        if (res && res.ok) return { ...res, offline: false };
    } catch { /* offline */ }
    return { albums: ALBUMS, tracks: TRACKS, photos: PHOTOS, offline: true };
}

async function renderSection(term, slug) {
    slug = slug.toLowerCase();
    const { data, offline } = await fetchContent(term, slug);
    if (!data) {
        term.err(`${t(term).notFound}: ${slug}`);
        term.dim('try `ls` to see what exists');
        return;
    }
    term.session.cwd = `~/${slug}`;
    term.setPrompt();
    term.setStatus('status-path', term.session.cwd);
    term.blank();
    term.rule(data.title || slug);
    if (offline) term.faint('  (offline copy — server not reached)');

    if (data.tagline) term.write(`<span class="v">${esc(data.tagline)}</span>`);
    (data.body || []).forEach((l) => term.out(l));
    if (data.aspect) {
        term.blank();
        term.label('key aspect');
        data.aspect.forEach((l) => term.out(l));
    }
    if (data.note) term.dim(`// ${data.note}`);

    if (data.items) {
        term.blank();
        data.items.forEach((it) => {
            if (it.date && it.text) term.write(`<span class="k">${esc(it.date)}</span>  ${esc(it.text)}`);
            else if (it.year && it.title) term.write(`<span class="k">${esc(it.year)}</span>  <span class="em">${esc(it.title)}</span>  <span class="badge">${esc(it.type || '')}</span>`);
            else if (it.sku) term.write(`<span class="badge">${esc(it.sku)}</span>  ${esc(it.title)}  <span class="v">${esc(it.price)}</span>`);
            else if (it.url) term.write(`<span class="em">${esc(it.label)}</span>  <a class="lnk" href="${esc(it.url)}" target="_blank" rel="noopener">${esc(it.url)}</a>`);
            else term.out(JSON.stringify(it));
        });
    }
    if (data.channels) {
        term.blank();
        data.channels.forEach((c) => term.write(`<span class="em">${esc(c.label)}</span>  <a class="lnk" href="${esc(c.url)}" target="_blank" rel="noopener">${esc(c.value)}</a>`));
        term.blank();
        term.dim('no forms here — the shell stores no personal data');
    }
    term.blank();
    term.faint('↵ type `ls` · `open <section>` · `help`');
    term.blank();
}

function resourceNames() {
    return ['about', 'home', 'news', 'events', 'releases', 'albums', 'tracks', 'photos', 'galleries', 'sorries'];
}

/* demo dataset for offline admin */
const MOCK = {
    about: [{ id: 1, title: 'about', updated_at: '2024-08-18' }],
    news: [{ id: 1, title: 'shadow business', date: '2024-11-01' }],
    events: [{ id: 1, title: 'live set', date: 'TBA' }],
    releases: CONTENT.releases.items.map((r, i) => ({ id: i + 1, ...r })),
    albums: ALBUMS,
    tracks: TRACKS,
    photos: PHOTOS,
    galleries: [{ id: 1, title: 'crt study' }],
    sorries: [{ id: 1, title: 'sorry' }],
    home: [{ id: 1, title: 'start' }],
};

/* ============================================================
   registration
   ============================================================ */
export function registerCommands(term) {
    const has = (u) => Boolean(term.session.user);

    /* ---------------- help ---------------- */
    term.command({
        name: 'help',
        aliases: ['?', 'h'],
        about: 'list commands or explain one',
        complete: () => term.commandNames(),
        run: (term, args) => {
            if (args[0]) return runMan(term, args[0]);
            term.blank();
            term.rule('help');
            const groups = {
                'navigate': ['ls', 'open', 'cat', 'search', 'banner', 'boot', 'motd'],
                'content': ['about', 'news', 'events', 'releases', 'music', 'gallery', 'links', 'contact'],
                'session': ['login', 'logout', 'whoami'],
                'console': ['admin'],
                'system': ['neofetch', 'history', 'date', 'uptime', 'ping', 'echo', 'clear', 'theme', 'crt', 'sound', 'font', 'lang', 'keyboard', 'man'],
                'recon': ['hack', 'nmap', 'sqlmap', 'nc', 'ps', 'top', 'inject', 'exploit', 'journal', 'critical', 'flags', 'reset'],
                'misc': ['matrix', 'glitch', 'coffee', 'sudo', 'su', 'rm', 'exit'],
            };
            for (const [group, names] of Object.entries(groups)) {
                term.label(group);
                names.forEach((n) => {
                    const cmd = term.resolve(n);
                    if (cmd) term.write(`  <span class="k">${esc(n.padEnd(10))}</span> <span class="line--dim">${esc(cmd.about || '')}</span>`);
                });
                term.blank();
            }
            term.dim('help <command> or man <command> for details · TAB completes · ↑↓ history');
            term.blank();
        },
    });

    const runMan = (term, name) => {
        const cmd = term.resolve(name);
        if (!cmd) {
            term.err(`no entry for ${name}`);
            return;
        }
        term.blank();
        term.label(`man ${cmd.name}`);
        term.write(`<span class="em">${esc(cmd.about || '')}</span>`);
        if (cmd.aliases?.length) term.dim(`aliases: ${cmd.aliases.join(', ')}`);
        const topic = HELP_TOPICS[cmd.name];
        if (topic) term.out(topic);
        if (cmd.usage) term.write(`<span class="k">usage:</span> ${esc(cmd.usage)}`);
        term.blank();
    };
    term.command({ name: 'man', about: 'show a detailed manual entry', usage: 'man <command>', complete: () => term.commandNames(), run: runMan });

    /* ---------------- navigation ---------------- */
    term.command({
        name: 'ls',
        aliases: ['dir', 'll'],
        about: 'list sections (or rows in a section)',
        usage: 'ls [section]',
        complete: () => [...SECTIONS.map((s) => s.slug), ...resourceNames()],
        run: async (term, args) => {
            const target = args[0];
            if (!target) {
                term.blank();
                term.rule('~/samelectronics');
                SECTIONS.forEach((s) => {
                    term.write(
                        `<span class="line--ok">drwxr-xr-x</span>  <span class="v">${esc((s.slug + '/').padEnd(12))}</span> <span class="line--dim">${esc(s.blurb)}</span>`
                    );
                });
                term.blank();
                term.dim(`${SECTIONS.length} sections · run \`open <section>\``);
                term.blank();
                return;
            }
            if (target === 'music' || target === 'catalog') {
                const c = await fetchCatalog(term);
                term.blank();
                term.label('albums');
                term.table(['id', 'title', 'year', 'tracks'], c.albums.map((a) => [a.id, a.title, a.year, a.tracks]), ['right', 'left', 'right', 'right']);
                term.blank();
                return;
            }
            if (target === 'gallery') {
                const c = await fetchCatalog(term);
                term.blank();
                term.label('photos');
                term.table(['id', 'title', 'tag'], c.photos.map((p) => [p.id, p.title, p.tag]), ['right', 'left', 'left']);
                term.blank();
                return;
            }
            const { data } = await fetchContent(term, target);
            if (!data) { term.err(`${t(term).notFound}: ${target}`); return; }
            term.blank();
            term.rule(`~/${target}`);
            (data.items || data.channels || []).forEach((it, i) => {
                const label = it.title || it.text || it.label || it.sku || `#${i + 1}`;
                term.write(`<span class="line--dim">${String(i + 1).padStart(2, '0')}</span>  <span class="v">${esc(label)}</span>`);
            });
            term.blank();
        },
    });

    const openCmd = {
        name: 'open',
        aliases: ['cd', 'o'],
        about: 'render a section',
        usage: 'open <section>',
        complete: () => SECTIONS.map((s) => s.slug),
        run: async (term, args) => {
            let slug = args[0];
            if (!slug) {
                term.dim('usage: open <section> · try `ls`');
                return;
            }
            if (slug === '..' || slug === '~' || slug === '/') {
                term.session.cwd = '~';
                term.setPrompt();
                term.setStatus('status-path', '~');
                term.ok('back to ~');
                return;
            }
            if (slug === 'music' || slug === 'gallery') {
                const cmd = term.resolve(slug);
                if (cmd) return cmd.run(term, []);
            }
            await renderSection(term, slug);
        },
    };
    term.command(openCmd);

    term.command({
        name: 'cat',
        about: 'print a section raw',
        usage: 'cat <section>',
        complete: () => SECTIONS.map((s) => s.slug),
        run: async (term, args) => {
            const { data } = await fetchContent(term, args[0] || 'about');
            if (!data) return term.err(`${t(term).notFound}: ${args[0]}`);
            term.blank();
            [...(data.body || []), ...(data.aspect || [])].forEach((l) => term.out(l));
            term.blank();
        },
    });

    term.command({
        name: 'search',
        aliases: ['grep', 'find'],
        about: 'search bundled content',
        usage: 'search <query>',
        run: (term, args) => {
            const q = args.join(' ').toLowerCase();
            if (!q) return term.dim('usage: search <query>');
            const hits = [];
            for (const [slug, sec] of Object.entries(CONTENT)) {
                const text = JSON.stringify(sec).toLowerCase();
                if (text.includes(q)) hits.push(slug);
            }
            term.blank();
            if (!hits.length) term.warn(`no matches for “${q}”`);
            else {
                term.label(`matches for “${q}”`);
                hits.forEach((h) => term.write(`  <span class="v">${esc(h)}</span>  <span class="line--dim">/open ${esc(h)}</span>`));
            }
            term.blank();
        },
    });

    /* section shortcut commands — read-only, no personal data */
    ['about', 'news', 'events', 'releases', 'links', 'contact'].forEach((slug) => {
        term.command({
            name: slug,
            about: `open the ${slug} section`,
            run: (term) => renderSection(term, slug),
        });
    });

    term.command({
        name: 'music',
        aliases: ['sounds', 'discography'],
        about: 'albums & tracks',
        run: async (term) => {
            const c = await fetchCatalog(term);
            term.blank();
            term.rule('music');
            term.label('albums');
            term.table(['id', 'title', 'year', 'tracks'], c.albums.map((a) => [a.id, a.title, a.year, a.tracks]), ['right', 'left', 'right', 'right']);
            term.blank();
            term.label('tracks');
            term.table(['#', 'title', 'album', 'len'], c.tracks.map((x) => [x.no, x.title, x.album, x.len]), ['right', 'left', 'left', 'right']);
            term.blank();
            term.dim('full discography → https://samelectronics.bandcamp.com');
            term.blank();
        },
    });

    term.command({
        name: 'gallery',
        aliases: ['photos'],
        about: 'visual debris',
        run: async (term) => {
            const c = await fetchCatalog(term);
            term.blank();
            term.rule('gallery');
            term.table(['id', 'title', 'tag'], c.photos.map((p) => [p.id, p.title, p.tag]), ['right', 'left', 'left']);
            term.blank();
            term.dim('visual archive is being mounted into the shell');
            term.blank();
        },
    });

    /* ---------------- intro ---------------- */
    const showBanner = async (term) => {
        term.ascii(LOGO);
        term.h1('  ' + WORDMARK);
        term.write(`  <span class="line--dim">${esc(TAGLINE)}</span>`);
    };

    term.command({
        name: 'banner',
        aliases: ['logo'],
        about: 'print the wordmark',
        run: async (term) => { term.blank(); await showBanner(term); term.blank(); },
    });

    term.command({
        name: 'motd',
        aliases: ['home', 'start'],
        about: 'print the message of the day',
        run: (term) => printMotd(term),
    });

    term.command({
        name: 'boot',
        about: 'replay the boot sequence',
        run: async (term) => { await bootSequence(term); printMotd(term); },
    });

    /* ---------------- session ---------------- */
    term.command({
        name: 'login',
        aliases: ['signin'],
        about: 'authenticate into the console',
        run: async (term) => {
            const email = await term.ask('login:', { validate: (v) => v.includes('@') || 'enter a valid email' });
            const password = await term.ask('password:', { mask: true });
            term.busyOn('authenticating');
            let user = null, offline = false;
            try {
                const res = await term.api.post('/login', { email, password });
                if (res.ok) user = res.user;
                else { term.busyOff(); term.sound.err(); return term.err(res.error || 'authentication failed'); }
            } catch {
                offline = true;
                if (password.length < 3) { term.busyOff(); return term.err('authentication failed'); }
                user = { id: 0, name: email.split('@')[0] || 'operator', email, admin: true };
            }
            await sleep(350);
            term.busyOff();
            term.session.user = user;
            term.setPrompt();
            term.setStatus('status-user', user.name);
            term.sound.ok();
            term.ok(`${t(term).logged_in}: ${user.name}`);
            if (offline) term.warn('offline demo mode — session is local only');
            term.dim('you can now run `admin`');
        },
    });

    term.command({
        name: 'logout',
        aliases: ['exit-session'],
        about: 'close the console session',
        run: async (term) => {
            try { await term.api.post('/logout'); } catch { /* offline */ }
            term.session.user = null;
            term.session.cwd = '~';
            term.setPrompt();
            term.setStatus('status-user', 'visitor');
            term.ok(t(term).logged_out);
        },
    });

    term.command({
        name: 'whoami',
        about: 'print the current identity',
        run: (term) => {
            const u = term.session.user;
            term.blank();
            if (!u) { term.out('visitor (not authenticated)'); term.dim('run `login` to enter the console'); }
            else {
                term.write(`<span class="em">${esc(u.name)}</span> <span class="line--dim">&lt;${esc(u.email)}&gt;</span>`);
                term.write(`role: <span class="k">${u.admin ? 'administrator' : 'member'}</span>`);
            }
            term.blank();
        },
    });

    /* ---------------- admin console ---------------- */
    term.command({
        name: 'admin',
        aliases: ['acp', 'console'],
        about: 'content console (requires login)',
        usage: 'admin | admin <resource> | admin <resource> <id> | admin rm <resource> <id> | admin new <resource> | admin edit <resource> <id>',
        complete: (args) => [...resourceNames(), 'rm', 'new', 'edit'],
        run: async (term, args) => {
            if (!has()) {
                term.sound.err();
                term.err(t(term).admin_denied);
                term.dim('run `login`');
                return;
            }
            const [sub, a, b] = args;
            if (!sub) return adminDashboard(term);
            if (sub === 'rm') {
                if (!a || !b) return term.dim('usage: admin rm <resource> <id>');
                const okc = await term.confirm(`delete ${a} #${b}?`);
                if (!okc) return term.warn('cancelled');
                term.busyOn('deleting');
                try {
                    const res = await term.api.del(`/admin/${a}/${b}`);
                    term.busyOff();
                    if (!res.ok) term.warn(res.error || 'nothing deleted');
                    else term.ok(`deleted ${a} #${b}`);
                } catch {
                    term.busyOff();
                    term.warn('demo mode — delete is disabled offline');
                }
                return;
            }
            if (sub === 'new' || sub === 'edit') {
                if (!a) return term.dim(`usage: admin ${sub} <resource>`);
                const id = sub === 'edit' ? b : null;
                if (sub === 'edit' && !id) return term.dim('usage: admin edit <resource> <id>');
                term.dim('enter attributes as JSON, e.g. {"title":"hello"}');
                const json = await term.ask('attributes:', { defaultValue: '{}' });
                let attributes;
                try { attributes = JSON.parse(json); } catch { return term.err('invalid JSON'); }
                term.busyOn(sub === 'new' ? 'creating' : 'updating');
                try {
                    const res = id
                        ? await term.api.patch(`/admin/${a}/${id}`, { attributes })
                        : await term.api.post(`/admin/${a}`, { attributes });
                    term.busyOff();
                    if (!res.ok) return term.err(res.error || 'failed');
                    term.ok(`${sub === 'new' ? 'created' : 'updated'} ${a}${id ? ' #' + id : ''}`);
                    adminRow(term, res.row);
                } catch {
                    term.busyOff();
                    term.warn('demo mode — write is disabled offline');
                }
                return;
            }
            // admin <resource> [id]
            if (!a) return adminDashboard(term);
            term.busyOn('loading');
            try {
                if (b) {
                    const res = await term.api.get(`/admin/${a}/${b}`);
                    term.busyOff();
                    if (!res.ok) return term.err(res.error || 'not found');
                    term.blank();
                    term.rule(`${a} #${b}`);
                    adminRow(term, res.row, true);
                    term.blank();
                } else {
                    const res = await term.api.get(`/admin/${a}`);
                    term.busyOff();
                    adminList(term, a, res.rows || [], false);
                }
            } catch {
                term.busyOff();
                const rows = MOCK[a] || [];
                if (b) {
                    const row = rows.find((r) => String(r.id) === String(b));
                    if (!row) return term.err(`${a} #${b} not found (demo)`);
                    term.blank(); term.rule(`${a} #${b} (demo)`); adminRow(term, row, true); term.blank();
                } else {
                    adminList(term, a, rows, true);
                }
            }
        },
    });

    /* ---------------- system ---------------- */
    term.command({
        name: 'neofetch',
        aliases: ['info', 'sysinfo'],
        about: 'system & project info',
        run: (term) => {
            const b = term.env.boot || {};
            const art = NAME_ART.trimEnd().split('\n');
            const info = [
                ['user', term.session.user ? term.session.user.name : 'visitor'],
                ['host', HOST_SAFE(term)],
                ['machine', b.host || 'local'],
                ['os', 'phosphor os 2.0 (crt)'],
                ['kernel', 'sam-sh 2.0.0'],
                ['shell', 'sam-sh'],
                ['theme', term.root.dataset.theme],
                ['terminal', 'sam-key kbd-01'],
                ['locale', term.session.locale],
                ['php', b.php || '—'],
                ['laravel', b.laravel || '—'],
                ['uptime', uptimeLabel()],
            ];
            term.blank();
            const max = Math.max(art.length, info.length);
            for (let i = 0; i < max; i++) {
                const left = art[i] || ' '.repeat(33);
                const meta = info[i];
                const right = meta ? `<span class="k">${esc(meta[0].padEnd(9))}</span><span class="v">${esc(meta[1])}</span>` : '';
                term.write(`<span class="line--ascii" style="font-size:inherit">${esc(left)}</span>  ${right}`, 'line--out');
            }
            term.blank();
        },
    });

    term.command({
        name: 'history',
        about: 'show recently typed commands',
        run: (term) => {
            term.blank();
            if (!term.history.length) term.dim('(no history)');
            term.history.slice(-30).forEach((h, i) => term.write(`<span class="line--dim">${String(i + 1).padStart(3)}</span>  ${esc(h)}`));
            term.blank();
        },
    });

    term.command({
        name: 'date',
        about: 'print the current date/time',
        run: (term) => term.out(new Date().toString()),
    });

    term.command({
        name: 'uptime',
        about: 'session uptime',
        run: (term) => term.out(`up ${uptimeLabel()} · 1 visitor · load ${(Math.random() * 0.4).toFixed(2)}`),
    });

    term.command({
        name: 'ping',
        about: 'probe a host',
        run: async (term, args) => {
            const host = args[0] || 'samelectronics';
            for (let i = 0; i < 4; i++) {
                await sleep(180);
                term.write(`64 bytes from ${esc(host)}: icmp_seq=${i + 1} ttl=64 time=${(Math.random() * 40 + 8).toFixed(1)} ms`);
            }
            term.ok(`4 packets transmitted, 4 received, 0% loss`);
        },
    });

    term.command({
        name: 'echo',
        about: 'echo text back',
        run: (term, args) => term.out(args.join(' ')),
    });

    term.command({
        name: 'clear',
        aliases: ['cls'],
        about: 'wipe the scrollback (Ctrl+L)',
        run: (term) => term.clear(),
    });

    term.command({
        name: 'theme',
        aliases: ['colors'],
        about: 'phosphor colour: phosphor | amber | ice',
        complete: () => ['phosphor', 'amber', 'ice'],
        run: (term, args) => {
            const themes = ['phosphor', 'amber', 'ice'];
            const name = args[0] && themes.includes(args[0]) ? args[0] : themes[(themes.indexOf(term.root.dataset.theme) + 1) % themes.length];
            term.setTheme(name);
            term.ok(`theme → ${name}`);
        },
    });

    term.command({
        name: 'crt',
        about: 'scanlines / glow / flicker: on | off',
        complete: () => ['on', 'off'],
        run: (term, args) => {
            const on = args[0] ? args[0] === 'on' : term.root.dataset.crt !== 'on';
            term.setCrt(on);
            term.ok(`crt effects → ${on ? 'on' : 'off'}`);
        },
    });

    term.command({
        name: 'sound',
        aliases: ['beep'],
        about: 'key clicks: on | off',
        complete: () => ['on', 'off'],
        run: (term, args) => {
            const on = args[0] ? args[0] === 'on' : !term.sound.enabled;
            term.setSound(on);
            term.ok(`sound → ${on ? 'on' : 'off'}`);
        },
    });

    term.command({
        name: 'font',
        aliases: ['fontsize'],
        about: 'set the terminal font size (11..22)',
        usage: 'font <11..22>',
        run: (term, args) => {
            const n = parseInt(args[0], 10);
            if (Number.isNaN(n)) return term.warn('usage: font <11..22>');
            const v = term.setFontSize(n);
            term.ok(`font size → ${v}px`);
        },
    });

    term.command({
        name: 'lang',
        aliases: ['locale', 'language'],
        about: 'interface language: ru | en',
        complete: () => ['ru', 'en'],
        run: (term, args) => {
            const loc = args[0] === 'en' ? 'en' : args[0] === 'ru' ? 'ru' : term.session.locale === 'ru' ? 'en' : 'ru';
            term.setLocale(loc);
            term.ok(`language → ${loc}`);
            term.dim(t(term).hint_help);
        },
    });

    term.command({
        name: 'keyboard',
        aliases: ['kbd'],
        about: 'toggle the on-screen keyboard (Ctrl+K)',
        run: (term) => term.toggleKeyboard(),
    });

    /* ---------------- easter eggs ---------------- */
    term.command({
        name: 'matrix',
        about: 'digital rain',
        run: async (term) => {
            const chars = 'ｱｲｳｴｵｶｷｸｹｺ01ｻｼｽｾｿﬁß01αβγΔ01';
            const height = 14;
            const rows = Array.from({ length: height }, () => Array(64).fill(' '));
            for (let step = 0; step < 60; step++) {
                for (let r = 0; r < height; r++) rows[r].shift();
                const col = Array.from({ length: height }, () => chars[Math.floor(Math.random() * chars.length)]);
                for (let r = 0; r < height; r++) rows[r].push(col[r]);
                term.lastLine?.remove();
                term.lastLine = term.write(rows.map((r) => esc(r.join(''))).join('\n'), 'line--ascii');
                term.lastLine.style.fontSize = '12px';
                term.lastLine.style.lineHeight = '1.05';
                await sleep(45);
            }
            term.lastLine?.remove();
            term.ok('wake up, neo...');
        },
    });

    term.command({
        name: 'glitch',
        about: 'corrupt the tube briefly',
        run: async (term) => {
            term.root.style.filter = 'hue-rotate(12deg) saturate(1.4)';
            term.glitch('░▒▓ SIGNAL CORRUPTED ▓▒░');
            for (let i = 0; i < 6; i++) {
                term.root.style.transform = `translateX(${(Math.random() - 0.5) * 6}px)`;
                await sleep(40);
            }
            term.root.style.transform = '';
            term.root.style.filter = '';
            term.ok('signal restored');
        },
    });

    term.command({
        name: 'coffee',
        about: 'brew a cup',
        run: async (term) => {
            term.blank();
            term.out('      (');
            term.out('       )     )');
            term.out('      (     (');
            term.out('     ________)');
            term.out('    |        |]');
            term.out('    \\        /');
            term.out("     `-.__.-\\");
            term.blank();
            await sleep(300);
            term.warn('HTTP 418: I am a teapot');
        },
    });

    term.command({
        name: 'sudo',
        about: 'try to become root',
        run: async (term, args) => {
            if (!args.length) return term.warn('usage: sudo <command>');
            term.err(`${term.session.user ? term.session.user.name : 'visitor'} is not in the sudoers file. this incident will be reported.`);
            await sleep(200);
            term.dim('...just kidding.');
        },
    });

    term.command({
        name: 'rm',
        about: 'remove files',
        run: async (term, args) => {
            if (args.join(' ').includes('-rf')) {
                term.glitch('attempting mass deletion…');
                await sleep(500);
                term.err('nice try. the archives are read-only.');
            } else {
                term.warn('usage: rm <file>   (and no, rm -rf / will not work)');
            }
        },
    });

    term.command({
        name: 'exit',
        aliases: ['quit', 'q'],
        about: 'leave the terminal',
        run: async (term) => {
            term.blank();
            const bye = [
                'connection to samelectronics closed.',
                'thank you for visiting. find us in the signal.',
                '  telegram → https://t.me/weltfuturisten',
                '  bandcamp → https://samelectronics.bandcamp.com',
                '',
                'press any key to reconnect…',
            ];
            for (const line of bye) { term.dim(line); await sleep(120); }
            const once = () => {
                document.removeEventListener('keydown', once);
                term.clear();
                bootSequence(term).then(() => printMotd(term));
            };
            document.addEventListener('keydown', once, { once: true });
        },
    });
}

/* ============================================================
   shared renderers
   ============================================================ */
function HOST_SAFE(term) { return term.session.host || 'samelectronics'; }

const __start = Date.now();
function uptimeLabel() {
    const s = Math.floor((Date.now() - __start) / 1000);
    const m = Math.floor(s / 60);
    return `${m}m ${s % 60}s`;
}

function printMotd(term) {
    MOTD.forEach(([kind, text]) => {
        if (text === '') return term.blank();
        term.write(text, `line--${kind}`);
    });
}

async function adminDashboard(term) {
    term.blank();
    term.rule('ACP · content console');
    term.busyOn('reading database');
    let resources = null, offline = false;
    try {
        const res = await term.api.get('/admin/stats');
        if (res.ok) resources = res.resources;
    } catch {
        offline = true;
    }
    await sleep(280);
    term.busyOff();

    if (!resources) {
        offline = true;
        resources = resourceNames().map((slug) => ({ slug, title: slug, icon: slug[0].toUpperCase(), count: (MOCK[slug] || []).length }));
    }

    term.label('resources');
    term.table(
        ['', 'resource', 'count', 'command'],
        resources.map((r) => [r.icon || '•', r.title, r.count, `admin ${r.slug}`]),
        ['left', 'left', 'right', 'left']
    );
    term.blank();
    if (offline) term.warn('offline demo mode — counts are illustrative');
    term.write(`<span class="em">${esc(term.session.user.name)}</span> <span class="line--dim">· administrator</span>`);
    term.dim('admin <resource> · admin <resource> <id> · admin rm <resource> <id> · admin new <resource> · admin edit <resource> <id>');
    term.blank();
}

function adminList(term, slug, rows, offline) {
    term.blank();
    term.rule(`ACP · ${slug}${offline ? ' (demo)' : ''}`);
    if (!rows.length) {
        term.warn('no records');
        term.dim(`create one with: admin new ${slug}`);
        term.blank();
        return;
    }
    const keys = Object.keys(rows[0]).slice(0, 5);
    term.table(
        keys,
        rows.slice(0, 50).map((r) => keys.map((k) => String(r[k] ?? '').slice(0, 28))),
        keys.map((k) => (k === 'id' ? 'right' : 'left'))
    );
    term.blank();
    term.dim(`${rows.length} row(s) · open one with: admin ${slug} <id>`);
    term.blank();
}

function adminRow(term, row, full = false) {
    if (!row) return term.warn('no data');
    const entries = Object.entries(row);
    const width = Math.max(...entries.map(([k]) => k.length));
    entries.forEach(([k, v]) => {
        if (v && typeof v === 'object') v = JSON.stringify(v);
        term.write(`<span class="k">${esc(String(k).padEnd(width))}</span>  <span class="v">${esc(String(v))}</span>`);
    });
}

/* ============================================================
   boot sequence
   ============================================================ */
export async function bootSequence(term, { fast = false } = {}) {
    const speed = fast ? 0 : 1;
    term.blank();
    const posts = [
        ['line--dim', 'phosphor-bios v2.0 — self test'],
        ['line--out', `memory ok · 640k ought to be enough`],
        ['line--out', `mounting /dev/crt0 … <span class="line--ok">ok</span>`],
        ['line--out', `loading sam-sh kernel … <span class="line--ok">ok</span>`],
        ['line--out', `linking content modules … <span class="line--ok">ok</span>`],
        ['line--out', `handshake with samelectronics … <span class="line--ok">ok</span>`],
    ];
    for (const [cls, line] of posts) {
        term.write(line, cls);
        if (speed) await sleep(120);
    }
    if (speed) await sleep(140);
    term.blank();
    term.ascii(LOGO);
    term.h1('  ' + WORDMARK);
    term.write(`  <span class="line--dim">${esc(TAGLINE)}</span>`);
    if (speed) await sleep(160);
}

export { printMotd, renderSection };
