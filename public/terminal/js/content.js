/* ============================================================
   content.js — bundled copy, ascii marks, i18n, fixtures
   ============================================================ */

export const LOGO = String.raw`
   ███████╗ █████╗ ███╗   ███╗
   ██╔════╝██╔══██╗████╗ ████║
   ███████╗███████║██╔████╔██║
   ╚════██║██╔══██║██║╚██╔╝██║
   ███████║██║  ██║██║ ╚═╝ ██║
   ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝
`;

export const WORDMARK = 'e l e c t r o n i c s';

export const TAGLINE = 'metamodern gravity eaters and experimental dreamers';
export const TAGLINE_RU = 'метамодерн, пожиратели гравитации и экспериментальные мечтатели';

export const HOST = 'samelectronics';

export const MOTD = [
    ['label', 'samelectronics // phosphor os'],
    ['dim', 'experimental interdisciplinary art project · novgorod republic · est. 2016'],
    ['blank', ''],
    ['out', 'this website is a shell. you talk to it with commands.'],
    ['blank', ''],
    ['warn', 'start here:'],
    ['out', '  <span class="k">help</span>          list every command'],
    ['out', '  <span class="k">ls</span>            list sections'],
    ['out', '  <span class="k">open</span> <span class="v">about</span>    read a section'],
    ['out', '  <span class="k">boot</span>          replay the intro'],
    ['blank', ''],
    ['label', 'system notice'],
    ['out', '  this shell is running on a live, exposed copy of the site itself.'],
    ['out', '  type <span class="k">hack</span> to see what is open — and what happens when you break it.'],
    ['blank', ''],
    ['faint', 'press TAB to complete · ↑↓ for history · Ctrl+K for the keyboard'],
];

export const I18N = {
    ru: {
        notFound: 'раздел не найден',
        opening: 'открываю',
        sections: 'разделы',
        hint_help: 'введи help и нажми enter',
        contact_ok: 'сообщение записано, тикет',
        logged_in: 'доступ разрешён',
        logged_out: 'сессия завершена',
        admin_denied: 'доступ запрещён — сначала login',
        none: 'пусто',
    },
    en: {
        notFound: 'section not found',
        opening: 'opening',
        sections: 'sections',
        hint_help: 'type help and press enter',
        contact_ok: 'message stored, ticket',
        logged_in: 'access granted',
        logged_out: 'session closed',
        admin_denied: 'permission denied — run login first',
        none: 'empty',
    },
};

/* ---------- fallback content (used when the API is unreachable) ---------- */
export const SECTIONS = [
    { slug: 'home', title: 'start', blurb: 'who we are' },
    { slug: 'about', title: 'about', blurb: 'the project' },
    { slug: 'news', title: 'news', blurb: 'signal feed' },
    { slug: 'events', title: 'events', blurb: 'where to find us' },
    { slug: 'releases', title: 'releases', blurb: 'discography' },
    { slug: 'music', title: 'music', blurb: 'albums & tracks' },
    { slug: 'gallery', title: 'gallery', blurb: 'visual debris' },
    { slug: 'links', title: 'links', blurb: 'external nodes' },
    { slug: 'contact', title: 'contact', blurb: 'public channels' },
];

export const CONTENT = {
    home: {
        title: 'samelectronics',
        tagline: TAGLINE,
        body: [
            'experimental interdisciplinary art project from the novgorod republic.',
            'founded 2016 · music · sound · video · glitch · net art',
            'riding neural networks through the post-truth era.',
        ],
    },
    about: {
        title: 'about',
        body: [
            'samelectronics is an experimental interdisciplinary art project coming from the novgorod republic.',
            'founded in 2016 in one of the sleeping areas of novgorod as a purely musical project, today samelectronics',
            'has expanded its boundaries and is engaged in the exploration and transformation of reality and simulacra',
            'through the prism of music, sound art, video art, glitch art and other forms of media art.',
        ],
        aspect: [
            'a key aspect of our work is the use of artificial intelligence, which allows us to transmit experimental',
            'dreams and daydreams in a form previously inaccessible to the human imagination. we are riders of the',
            'metamodern, standing at the intersection of technology and art, convinced that neural networks not only do',
            'not harm art, but also reveal new sides of it, expanding the horizons of perception.',
        ],
    },
    news: {
        title: 'news',
        note: 'live signal lives in telegram',
        items: [
            { date: '2024-11', text: '«shadow business» — long-awaited album released' },
            { date: '2024-08', text: 'terminal v2 booted: the site became a shell' },
            { date: '2024-06', text: 'residency: sound + glitch experiments' },
            { date: '2016-01', text: 'project founded in novgorod' },
        ],
    },
    events: {
        title: 'events',
        note: 'schedule is announced in telegram',
        items: [
            { date: 'TBA', text: 'audio-visual live set' },
            { date: 'TBA', text: 'glitch art installation' },
        ],
    },
    releases: {
        title: 'releases',
        note: 'full discography on bandcamp',
        items: [
            { year: '2024', title: 'shadow business', type: 'album' },
            { year: '2023', title: 'dream transmission', type: 'ep' },
            { year: '2021', title: 'analog / digital', type: 'album' },
            { year: '2019', title: 'simulacra', type: 'album' },
        ],
    },
    links: {
        title: 'links',
        items: [
            { label: 'telegram', url: 'https://t.me/weltfuturisten' },
            { label: 'telegram (contact)', url: 'https://t.me/samelectronics1' },
            { label: 'bandcamp', url: 'https://samelectronics.bandcamp.com' },
            { label: 'spotify', url: 'https://open.spotify.com' },
            { label: 'apple music', url: 'https://music.apple.com' },
            { label: 'youtube', url: 'https://youtube.com' },
            { label: 'instagram', url: 'https://instagram.com' },
        ],
    },
    contact: {
        title: 'contact',
        note: 'or write directly in telegram',
        channels: [
            { label: 'telegram', value: '@samelectronics1', url: 'https://t.me/samelectronics1' },
            { label: 'telegram (community)', value: '@weltfuturisten', url: 'https://t.me/weltfuturisten' },
            { label: 'bandcamp', value: 'samelectronics.bandcamp.com', url: 'https://samelectronics.bandcamp.com' },
        ],
    },
};

export const ALBUMS = [
    { id: 1, title: 'shadow business', year: 2024, tracks: 9 },
    { id: 2, title: 'dream transmission', year: 2023, tracks: 5 },
    { id: 3, title: 'analog / digital', year: 2021, tracks: 11 },
    { id: 4, title: 'simulacra', year: 2019, tracks: 8 },
];

export const TRACKS = [
    { no: 1, title: 'gravity eater', album: 'shadow business', len: '04:12' },
    { no: 2, title: 'phosphor bloom', album: 'shadow business', len: '05:40' },
    { no: 3, title: 'sleeping area', album: 'shadow business', len: '03:58' },
    { no: 4, title: 'dream transmission pt.1', album: 'dream transmission', len: '06:21' },
    { no: 5, title: 'simulacrum', album: 'simulacra', len: '07:03' },
];

export const PHOTOS = [
    { id: 1, title: 'crt study 01', tag: 'glitch' },
    { id: 2, title: 'novgorod sleeping area', tag: 'doc' },
    { id: 3, title: 'signal bleed', tag: 'video' },
    { id: 4, title: 'neural daydream', tag: 'ai' },
];

export const HELP_TOPICS = {
    open: 'open <section> — render a section (about, news, events, releases, music, gallery, merch, links, contact).',
    ls: 'ls [section] — list sections, or rows inside a section (e.g. `ls releases`).',
    admin: 'admin — content console. requires login. try: admin · admin releases · admin releases 1 · admin rm releases 1.',
    contact: 'contact — the public channels of the project (telegram, bandcamp). no forms, no personal data.',
    login: 'login — authenticate into the console (admin). credentials are your site account.',
    theme: 'theme [phosphor|amber|ice] — switch the phosphor colour.',
    crt: 'crt [on|off] — toggle scanlines, glow and flicker.',
    sound: 'sound [on|off] — toggle key clicks.',
    font: 'font <11..22> — set the terminal font size.',
    lang: 'lang [ru|en] — switch interface language.',
    keyboard: 'keyboard — toggle the on-screen keyboard (or Ctrl+K).',
    clear: 'clear — wipe the scrollback (or Ctrl+L).',
    neofetch: 'neofetch — print system / project info.',
    history: 'history — show recently typed commands.',
};

export const NAME_ART = String.raw`
 ┌─┐┌─┐┌┬┐┌─┐┬  ┌─┐┌─┐┌┬┐┬─┐┌─┐┌┐┌┬┌─┐┌─┐
 └─┐├─┤│││├┤ │  ├┤ │   │ ├┬┘│ ││││││  └─┐
 └─┘┴ ┴┴ ┴└─┘┴─┘└─┘└─┘ ┴ ┴└─└─┘┘└┘┴└─┘└─┘
`;
