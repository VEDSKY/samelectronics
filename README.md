# samelectronics — terminal edition

Official website of the art project **samelectronics**, rebuilt as a **CRT phosphor
terminal**. The whole site — public pages, auth and the admin console — is operated
by typing commands into a shell, with an on-screen keyboard on top.

> metamodern gravity eaters and experimental dreamers · novgorod republic · est. 2016

## Concept

The site is no longer a set of pages. It is a single shell (`sam-sh`) you talk to:

| layer | what it is |
| --- | --- |
| shell | one Blade view (`resources/views/terminal.blade.php`) + a CRT stylesheet + an ES-module kernel |
| kernel | `public/terminal/js/core.js` — renderer, input, history, completion, sound, keyboard, API client |
| commands | `public/terminal/js/commands.js` — the command registry |
| api | `TerminalController` — JSON endpoints for content, auth and ACP CRUD |
| data | Laravel (SQLite by default) — sections, releases, tracks and contact messages |

Everything you can do in the original site is now a command. Nothing is a page reload.

## Commands

```
help                 list every command                  ls [section]     list sections / rows
open <section>       render a section (cd/o)             cat <section>    print raw
music · gallery      catalog                            search <query>   grep bundled copy
about news events releases merch links contact           section shortcuts
login · logout · register · whoami                      session control
admin                content console (requires login)     theme [phosphor|amber|ice]
crt [on|off]         scanlines / glow / flicker          sound [on|off]   key clicks
font <11..22>        font size                            lang [ru|en]     interface language
keyboard             toggle on-screen kbd (Ctrl+K)        neofetch         system info
history · date · uptime · ping · echo · clear            matrix · glitch · coffee · sudo · exit
```

### ACP console

```
admin                                  dashboard with per-resource counters
admin <resource>                       list rows
admin <resource> <id>                  inspect one row
admin new <resource>                   create (attributes as JSON)
admin edit <resource> <id>             update (attributes as JSON)
admin rm <resource> <id>               delete (with confirmation)
```

Resources: `about home news events releases albums tracks photos galleries contacts sorries users`.

## Keyboard

- **physical** — TAB completes, `↑`/`↓` walk history, `Ctrl+L` clears, `Ctrl+K`
  toggles the on-screen keyboard, `Ctrl+C` aborts, `Esc` cancels a prompt.
- **on-screen (`sam-key kbd-01`)** — latin / cyrillic / symbols layouts, clickable,
  and it highlights the key you press on your real keyboard.
- every keystroke is a soft phosphor click (toggle with `sound off`).

## Getting started

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # creates sqlite db + demo content
php artisan serve               # → http://127.0.0.1:8000
```

Demo admin account (seeded): `operator@samelectronics` / `terminal`.
Log in from the terminal with the `login` command, then run `admin`.

The legacy Vite/Tailwind frontend is no longer used by the terminal: the shell ships
as plain ES modules under `public/terminal/`, so there is no build step to view it.

## Offline preview

`public/terminal/preview.html` is a static, server-less copy of the shell (used for
design review). Open it directly in a browser; content falls back to the bundled copy.

## Theming

`phosphor` (green), `amber` and `ice` — switch with `theme <name>`, persisted in
`localStorage` along with CRT effects, sound, font size and locale.
