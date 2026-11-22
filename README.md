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
| project | `Project` — real-repo reads + overlay writes + the canary/integrity manifest |
| sandbox | `SandboxController` + `Sandbox` + `Sentinel` — the deliberately hackable playground |
| data | Laravel (SQLite by default) — sections, releases, tracks, albums, photos |

Everything you can do in the original site is now a command. Nothing is a page reload.
The shell stores no personal data: there are no accounts to register, no message forms,
and no user-facing profile sections.

## Commands

```
help                 list every command                  ls [section|path] list sections / files
open <section>       render a section (cd/o)             cat <section|path> print raw / file
music · gallery      catalog                            search <query>   grep bundled copy
about news events releases links contact                 section shortcuts
login · logout · whoami                                 session control
admin                content console (requires login)     theme [phosphor|amber|ice]
crt [on|off]         scanlines / glow / flicker          sound [on|off]   key clicks
font <11..22>        font size                            lang [ru|en]     interface language
keyboard             toggle on-screen kbd (Ctrl+K)        neofetch         system info
history · date · uptime · ping · echo · clear            matrix · glitch · coffee · exit
```

### The hackable surface

This is a performance piece: the site invites you to break it, and it means it. The
shell opens the **real project** at `/var/www/site` — controllers, routes, config,
`vendor/`, `composer.json` — plus a decoy server around it and a small database.
Run `hack` for the story; the essentials:

```
hack                                 start here — objectives + how it heals
ls /var/www/site · cat <file>        read the real project, top to bottom
ls / · cat /root/flag.txt            the decoy server around it
inject <payload>                     reflected XSS / SQL injection
sql <query>                          raw SQL against the decoy database
edit <path> [content]                modify anything in the project
mkdir · rm [-r] · chmod · chown      file surgery, up to `rm -rf /`
ps · kill · nmap · nc · exploit       process / network theatre
journal · follow · critical           sentinel log, load-bearing files, integrity
sudo · su · flags · reset             escalation, secrets, manual heal
```

`glitch` (alias `resync`) claims to re-sync the display, then hands the prompt to
the shell itself for ~10–22s: it types its own commands into the input line, runs
them, edits and deletes the project, drops database tables, leaks lines with no
prompt and spills fragments across the interface. Each run is assembled at random
(how it starts, what it does, whether it goes as far as wiping the site) so it is
never quite the same twice; the sentinel and the integrity poll put it back.

**Critical spots.** `/var/www/site/.sentinel/critical.json` (and a `sentinel` row in
the decoy DB) lists the load-bearing files the shell stands on. Damage or delete one
and the terminal bleeds **real PHP/Laravel exceptions**: a real `ParseError` produced
by `php -l` for a corrupted file, a class-not-found `Error` for a deleted controller,
a `QueryException` for a dropped record — each with a real file, line and trace.
Destroy the manifest or the whole project (`rm -rf /`) and the page itself comes
apart: **databending** — canvas feedback, torn scanlines, scrambled text, layout
jitter, phantom stack traces — until the next sentinel pulse. `critical` prints the
current damage; the client polls `/api/terminal/shell/integrity` every 3.5s to escalate
the glitch tier.

**Safety boundary.** Reads are real; writes are not. Every edit, delete, mkdir or
chmod under `/var/www/site` lands in a write-overlay (`storage/app/sandbox.overlay`)
plus a tombstone list and is served back as if it were the file. The executing
application is never replaced, so nothing the visitor writes can run: there is no RCE
and no path off the site. `storage/`, `.git`, `node_modules` and `*.sqlite` are
shielded, and any real `.env` is replaced by a decoy — no real secret or personal data
is exposed. The SQL endpoint only opens the decoy `sqlite` connection; XSS payloads
render inside an iframe with `sandbox="allow-scripts allow-modals allow-forms"`, in an
opaque origin.

### Sentinel — self-healing

`App\Services\Sentinel` keeps a pristine snapshot (`storage/app/sandbox.pristine`,
`sandbox.db.pristine`) and restores the live tree from it on every pulse. The same
pulse clears the project write-overlay, revives everything tombstoned and reseals the
canaries. It also writes a bounded intrusion log to `storage/app/sandbox.intrusion.log`
and records virtual unix modes in `storage/app/sandbox.modes.json` /
`sandbox.project.modes.json` (because the workspace volume may be exFAT, where `chmod`
is a no-op).

```
php artisan terminal:sandbox install --force   # build the pristine snapshot
php artisan terminal:sandbox restore           # one restore
php artisan terminal:sandbox status            # files / anomalies / log
php artisan sentinel                           # watchdog loop, 8s pulse
php artisan sentinel --once                    # single pulse
scripts/sentinel.sh                            # standalone loop (INTERVAL=8)
scripts/sentinel.sh --once                     # single restore
```

The browser pulses `POST /api/terminal/sentinel/pulse` every 8 seconds and shows the
result in the SENTINEL strip above the terminal; when something was repaired, a
`RESTORING` overlay flashes and the shell logs the heal.

### ACP console

```
admin                                  dashboard with per-resource counters
admin <resource>                       list rows
admin <resource> <id>                  inspect one row
admin new <resource>                   create (attributes as JSON)
admin edit <resource> <id>             update (attributes as JSON)
admin rm <resource> <id>               delete (with confirmation)
```

Resources: `about home news events releases albums tracks photos galleries sorries`.

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
php artisan migrate --seed              # creates sqlite db + demo content
php artisan terminal:sandbox install    # build the hackable sandbox snapshot
php artisan serve                       # → http://127.0.0.1:8000
```

Run `php artisan sentinel` (or `scripts/sentinel.sh`) in the background to keep the
sandbox self-healing, or rely on the browser's own pulse.

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
