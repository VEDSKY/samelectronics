<!DOCTYPE html>
<html lang="ru" data-theme="phosphor">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">
    <meta name="description" content="samelectronics // experimental interdisciplinary art project — terminal edition">
    <title>samelectronics — /bin/sh</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,300;0,400;0,500;0,600;1,400&family=VT323&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('terminal/styles.css') }}">
    <script>
        window.__TERMINAL__ = @json($boot);
    </script>
</head>
<body>
    <div id="crt" class="crt" data-theme="phosphor" data-crt="on" data-sound="on">
        <div class="crt__glow" aria-hidden="true"></div>
        <div class="crt__scanlines" aria-hidden="true"></div>
        <div class="crt__flicker" aria-hidden="true"></div>
        <div class="crt__vignette" aria-hidden="true"></div>
        <div class="crt__bezel" aria-hidden="true"></div>

        <header class="statusbar" id="statusbar">
            <span class="statusbar__seg statusbar__brand" id="status-brand">samelectronics</span>
            <span class="statusbar__seg" id="status-user">visitor</span>
            <span class="statusbar__seg statusbar__grow" id="status-path">~</span>
            <span class="statusbar__seg" id="status-net">◉ LINK</span>
            <span class="statusbar__seg" id="status-clock">--:--:--</span>
        </header>

        <div class="sentinel" id="sentinel" data-state="ok" aria-live="polite">
            <span class="sentinel__dot" aria-hidden="true">◉</span>
            <span class="sentinel__name">SENTINEL</span>
            <span class="sentinel__msg" id="sentinel-msg">linking to watchdog…</span>
            <span class="sentinel__grow"></span>
            <span class="sentinel__next" id="sentinel-next">⟲ 08s</span>
        </div>

        <main class="screen" id="screen" role="log" aria-live="polite" aria-label="terminal output">
            <div class="screen__inner" id="lines"></div>
        </main>

        <div class="cmdline" id="cmdline">
            <span class="cmdline__prompt" id="prompt">visitor@samelectronics:~$</span>
            <div class="cmdline__field">
                <span class="cmdline__text" id="input-text"></span><span class="cmdline__caret" id="caret" aria-hidden="true">█</span>
                <span class="cmdline__ghost" id="ghost"></span>
            </div>
            <input
                id="cmd-input"
                class="cmdline__capture"
                type="text"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                aria-label="command input"
            >
        </div>

        <section class="keyboard" id="keyboard" aria-label="on-screen keyboard">
            <div class="keyboard__head">
                <span class="keyboard__label">// sam-key kbd-01</span>
                <div class="keyboard__modes" id="keyboard-modes">
                    <button type="button" class="kbd-chip is-active" data-kbd-mode="latin">latin</button>
                    <button type="button" class="kbd-chip" data-kbd-mode="cyrillic">кириллица</button>
                    <button type="button" class="kbd-chip" data-kbd-mode="symbols">sym</button>
                </div>
                <button type="button" class="keyboard__hide" id="keyboard-hide" aria-label="hide keyboard">▾ hide</button>
            </div>
            <div class="keyboard__rows" id="keyboard-rows"></div>
        </section>

        <footer class="hints" id="hints">
            <span><b>TAB</b> completo</span>
            <span><b>↑↓</b> history</span>
            <span><b>Ctrl</b>+<b>K</b> keyboard</span>
            <span><b>Ctrl</b>+<b>L</b> clear</span>
            <span><b>Ctrl</b>+<b>C</b> abort</span>
            <span class="hints__spacer"></span>
            <span id="hints-hint">type <b>help</b> and press enter</span>
        </footer>

        <noscript>
            <div class="noscript">
                <p>this terminal requires javascript.</p>
                <p>meanwhile: samelectronics — experimental interdisciplinary art project (novgorod, since 2016).</p>
                <p><a href="https://t.me/weltfuturisten">telegram</a> · <a href="https://samelectronics.bandcamp.com">bandcamp</a></p>
            </div>
        </noscript>

        <div class="recovery" id="recovery" aria-hidden="true">
            <div class="recovery__inner">
                <div class="recovery__title">⟲ RESTORING</div>
                <div class="recovery__sub">sentinel · rebuilding from pristine snapshot</div>
            </div>
        </div>
    </div>

    <script type="module" src="{{ asset('terminal/js/app.js') }}"></script>
</body>
</html>
