<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Sentinel — the self-healing watchdog of the terminal's "hackable" surface.
 *
 * Everything the visitor is allowed to break lives inside a throwaway tree
 * (`storage/app/sandbox` + a separate sqlite file). A pristine snapshot is
 * kept next to it and the sentinel restores the live tree from that snapshot
 * on every pulse, so no matter what was done to the sandbox the site comes
 * back exactly as it was.
 *
 * Nothing outside the sandbox is ever touched, and the sandbox is never
 * reachable by the application itself.
 */
class Sentinel
{
    public function livePath(): string
    {
        return storage_path('app/sandbox');
    }

    public function pristinePath(): string
    {
        return storage_path('app/sandbox.pristine');
    }

    public function dbLivePath(): string
    {
        return storage_path('app/sandbox.db');
    }

    public function dbPristinePath(): string
    {
        return storage_path('app/sandbox.db.pristine');
    }

    public function logPath(): string
    {
        return storage_path('app/sandbox.intrusion.log');
    }

    /**
     * Permissions live in a sidecar file because the sandbox may sit on a
     * filesystem without unix ownership (e.g. exfat), where chmod is a no-op.
     */
    public function modesPath(): string
    {
        return storage_path('app/sandbox.modes.json');
    }

    /** The write-overlay for the real project (edits are never applied). */
    public function overlayPath(): string
    {
        return storage_path('app/sandbox.overlay');
    }

    /** Deletions in the project tree (the sentinel clears these on a pulse). */
    public function tombstonesPath(): string
    {
        return storage_path('app/sandbox.tombstones.json');
    }

    public function projectModesPath(): string
    {
        return storage_path('app/sandbox.project.modes.json');
    }

    /**
     * Build (or rebuild) the pristine snapshot and reset the live copy.
     *
     * @return array<string, mixed>
     */
    public function install(bool $force = false): array
    {
        $pristine = $this->pristinePath();

        if ($force && is_dir($pristine)) {
            File::deleteDirectory($pristine);
        }

        File::ensureDirectoryExists($pristine);
        $this->writePristineFiles($pristine);
        $this->buildDatabase($this->dbPristinePath());

        return $this->restore('install');
    }

    /**
     * Restore the live tree from the pristine snapshot.
     *
     * @return array<string, mixed>
     */
    public function restore(string $reason = 'pulse'): array
    {
        $started = microtime(true);
        $live = $this->livePath();
        $pristine = $this->pristinePath();

        if (! is_dir($pristine)) {
            $this->install(force: true);
        }

        $anomalies = $this->anomalies();

        File::deleteDirectory($live);
        File::ensureDirectoryExists($live);
        if (is_dir($pristine)) {
            File::copyDirectory($pristine, $live);
        }

        if (is_file($this->dbPristinePath())) {
            File::copy($this->dbPristinePath(), $this->dbLivePath());
            // force the sandbox connection to reopen the replaced file
            DB::purge('sandbox');
        }

        // drop the project write-overlay and revive everything that was deleted
        File::deleteDirectory($this->overlayPath());
        File::ensureDirectoryExists($this->overlayPath());
        File::put($this->tombstonesPath(), json_encode(['wiped' => false, 'paths' => []], JSON_PRETTY_PRINT));
        if (is_file($this->projectModesPath())) {
            File::delete($this->projectModesPath());
        }

        $files = $this->countFiles($live);
        $ms = (int) round((microtime(true) - $started) * 1000);
        $this->seal($live);

        $this->append(sprintf(
            '%s integrity restored by sentinel · %d anomalies repaired · %d files · %dms (%s)',
            $this->stamp(),
            $anomalies,
            $files,
            $ms,
            $reason
        ));

        return [
            'ok' => true,
            'at' => now()->toIso8601String(),
            'anomalies' => $anomalies,
            'files' => $files,
            'ms' => $ms,
            'reason' => $reason,
        ];
    }

    /**
     * How many files differ from the pristine snapshot right now.
     */
    public function anomalies(): int
    {
        $live = $this->mapFiles($this->livePath());
        $pristine = $this->mapFiles($this->pristinePath());

        $count = 0;
        foreach ($pristine as $rel => $hash) {
            if (! isset($live[$rel]) || $live[$rel] !== $hash) {
                $count++;
            }
        }
        foreach ($live as $rel => $hash) {
            if (! isset($pristine[$rel])) {
                $count++;
            }
        }

        // project overlay writes and deletions are anomalies too
        $count += count($this->overlayFiles());

        $tombstones = $this->tombstones();
        if ($tombstones['wiped']) {
            $count++;
        }
        $count += count($tombstones['paths']);

        return $count;
    }

    /**
     * @return list<string>
     */
    public function overlayFiles(): array
    {
        $root = $this->overlayPath();
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            }
        }

        return $files;
    }

    /**
     * @return array{wiped: bool, paths: array<string, bool>}
     */
    private function tombstones(): array
    {
        $path = $this->tombstonesPath();
        if (! is_file($path)) {
            return ['wiped' => false, 'paths' => []];
        }

        $decoded = json_decode((string) File::get($path), true);

        return [
            'wiped' => (bool) ($decoded['wiped'] ?? false),
            'paths' => is_array($decoded['paths'] ?? null) ? $decoded['paths'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'ok' => true,
            'watching' => $this->countFiles($this->pristinePath()),
            'files' => $this->countFiles($this->livePath()),
            'anomalies' => $this->anomalies(),
            'installed' => is_dir($this->pristinePath()),
            'db' => is_file($this->dbLivePath()),
            'overlay' => count($this->overlayFiles()),
            'log' => $this->log(40),
        ];
    }

    /**
     * @return list<string>
     */
    public function log(int $limit = 60): array
    {
        if (! is_file($this->logPath())) {
            return [];
        }
        $lines = preg_split('/\R/', (string) File::get($this->logPath())) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        return array_slice($lines, -$limit);
    }

    public function append(string $line): void
    {
        File::ensureDirectoryExists(dirname($this->logPath()));
        File::append($this->logPath(), $line.PHP_EOL);

        // keep the intrusion log bounded
        $lines = preg_split('/\R/', (string) File::get($this->logPath())) ?: [];
        if (count($lines) > 800) {
            File::put($this->logPath(), implode(PHP_EOL, array_slice($lines, -600)).PHP_EOL);
        }
    }

    private function stamp(): string
    {
        return '['.now()->format('H:i:s').']';
    }

    /**
     * Give the tree believable unix permissions and cache them in a sidecar
     * file, so the terminal can display (and mutate) them on any filesystem.
     */
    private function seal(string $root): void
    {
        $modes = [];

        if (is_dir($root)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $path = $item->getPathname();
                $rel = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
                $octal = $item->isDir() ? 0755 : $this->defaultFileMode($rel);
                $modes[$rel] = $octal;
                @chmod($path, $octal); // best effort; ignored where unsupported
            }
        }

        File::put($this->modesPath(), json_encode($modes, JSON_PRETTY_PRINT));
    }

    private function defaultFileMode(string $rel): int
    {
        if (str_starts_with($rel, 'root/') || $rel === 'etc/shadow') {
            return 0600;
        }
        if (str_ends_with($rel, '.sh')) {
            return 0755;
        }

        return 0644;
    }

    /**
     * @return array<string, string> relative path => md5
     */
    private function mapFiles(string $root): array
    {
        $out = [];
        if (! is_dir($root)) {
            return $out;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $rel = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $out[$rel] = md5_file($file->getPathname()) ?: '';
        }

        return $out;
    }

    private function countFiles(string $root): int
    {
        return count($this->mapFiles($root));
    }

    private function put(string $base, string $relative, string $content): void
    {
        $target = $base.'/'.$relative;
        File::ensureDirectoryExists(dirname($target));
        File::put($target, $content);
    }

    /**
     * The pristine content: synthetic "server" files plus real excerpts of
     * the project's own source, so viewing models/vendor feels genuine.
     */
    private function writePristineFiles(string $base): void
    {
        // --- directory skeleton -------------------------------------------------
        foreach (['tmp', 'var/lib/sentinel', 'var/www/site', 'home/sam'] as $dir) {
            File::ensureDirectoryExists($base.'/'.$dir);
        }

        // --- synthetic system files --------------------------------------------
        $files = [
            'etc/passwd' => <<<'TXT'
            root:x:0:0:root:/root:/bin/sh
            daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin
            www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin
            sentinel:x:1337:1337:integrity daemon:/var/lib/sentinel:/usr/sbin/nologin
            sam:x:1000:1000:samelectronics:/home/sam:/bin/sam-sh
            TXT,
            'etc/shadow' => <<<'TXT'
            root:$6$Ph0sph0r$REDACTED.bY.SENTINEL.ON.EVERY.PULSE:19000:0:99999:7:::
            www-data:!:19000:0:99999:7:::
            sam:$6$crt$this.hash.is.a.decoy.for.the.playground:19000:0:99999:7:::
            sentinel:*:19000:0:99999:7:::
            TXT,
            'etc/hosts' => <<<'TXT'
            127.0.0.1    localhost
            127.0.0.1    samelectronics.local
            ::1          localhost ip6-localhost
            10.13.37.7   sentinel.internal   # integrity watchdog
            TXT,
            'etc/os-release' => <<<'TXT'
            NAME="phosphor os"
            VERSION="2.0 (crt)"
            ID=sam
            ID_LIKE=unix
            PRETTY_NAME="phosphor os 2.0 (crt)"
            HOME_URL="https://samelectronics.bandcamp.com"
            TXT,
            'etc/sudoers' => <<<'TXT'
            # this file is watched by sentinel(1). any edit is reverted within one pulse.
            Defaults        env_reset
            root    ALL=(ALL:ALL) ALL
            sam     ALL=(ALL) NOPASSWD: /usr/local/bin/sentinel.sh
            # hint: `sudo -l` then `sudo /usr/local/bin/sentinel.sh --once`
            TXT,
            'usr/local/bin/sentinel.sh' => <<<'TXT'
            #!/bin/sh
            # sentinel(1) — restores the playground from its pristine snapshot.
            # this is the script that puts the site back, whatever you did to it.
            set -eu
            ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
            PRISTINE="$ROOT/storage/app/sandbox.pristine"
            LIVE="$ROOT/storage/app/sandbox"
            DB="$ROOT/storage/app/sandbox.db"
            DB_PRISTINE="$ROOT/storage/app/sandbox.db.pristine"
            echo "[sentinel] sealing $LIVE"
            rm -rf "$LIVE"
            mkdir -p "$LIVE"
            cp -R "$PRISTINE/." "$LIVE/"
            [ -f "$DB_PRISTINE" ] && cp "$DB_PRISTINE" "$DB"
            echo "[sentinel] integrity restored"
            TXT,
            'root/flag.txt' => <<<'TXT'
            SAM{ph0sph0r_1n_th3_sh3ll}

            nice. the sentinel will re-seal this file in a few seconds.
            enjoy it while it lasts.
            TXT,
            'root/.ssh/id_rsa' => <<<'TXT'
            -----BEGIN OPENSSH PRIVATE KEY-----
            b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
            QyNTUxOQAAACBkZWNveS1ub3QtYS1yZWFsLWtleS1tYXRlcmlhbC1hdC1hbGwAAAAK
            (this is a decoy — no real key material is stored anywhere)
            -----END OPENSSH PRIVATE KEY-----
            TXT,
            'var/lib/sentinel/policy.json' => <<<'JSON'
            {
              "daemon": "sentinel",
              "pid": 1337,
              "interval_ms": 8000,
              "strategy": "snapshot-restore",
              "snapshot": "/var/www/site/storage/app/sandbox.pristine",
              "live": "/var/www/site/storage/app/sandbox",
              "protects": ["files", "sqlite", "manifest"],
              "note": "the sentinel is not killable from userland"
            }
            JSON,
        ];

        foreach ($files as $relative => $content) {
            $this->put($base, $relative, $this->dedent($content));
        }
    }

    /**
     * Strip the indentation that heredocs force on us.
     */
    private function dedent(string $text): string
    {
        $lines = preg_split('/\R/', $text) ?: [];
        $min = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $min = $min === null ? $indent : min($min, $indent);
        }
        if ($min === null || $min === 0) {
            return $text;
        }

        return implode("\n", array_map(
            fn ($line) => trim($line) === '' ? '' : substr($line, $min),
            $lines
        ));
    }

    private function buildDatabase(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
        if (is_file($path)) {
            File::delete($path);
        }

        $pdo = new \PDO('sqlite:'.$path);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE vault (id INTEGER PRIMARY KEY, label TEXT, token TEXT, secret TEXT)');
        $pdo->exec('CREATE TABLE staff (id INTEGER PRIMARY KEY, handle TEXT, role TEXT, note TEXT)');
        $pdo->exec('CREATE TABLE audit (id INTEGER PRIMARY KEY, at TEXT, event TEXT)');
        // the load-bearing record: drop this row and the shell starts to bleed
        $pdo->exec('CREATE TABLE sentinel (id INTEGER PRIMARY KEY, key TEXT, value TEXT, seal TEXT)');
        $pdo->exec("INSERT INTO sentinel (id, key, value, seal) VALUES (1, 'integrity', 'locked', 'SAM{s3al_0f_th3_w4tch3r}')");

        // deliberately fictional data — no real credentials, no personal data
        $vault = [
            [1, 'bandcamp payout', 'tok_live_51H8xDECOY', 'sam{s0m3_cr3d3nt14l}'],
            [2, 'telegram bot', '123456:AAH-decoy', 'n0t4r34lt0k3n'],
            [3, 'sentinel master', 'SAM{w4tch3r_k3y}', 'rotate-on-pulse'],
        ];
        $stmt = $pdo->prepare('INSERT INTO vault (id, label, token, secret) VALUES (?, ?, ?, ?)');
        foreach ($vault as $row) {
            $stmt->execute($row);
        }

        $staff = [
            [1, 'root', 'system', 'uid 0'],
            [2, 'sam', 'operator', 'samelectronics'],
            [3, 'sentinel', 'integrity', 'watching you'],
        ];
        $stmt = $pdo->prepare('INSERT INTO staff (id, handle, role, note) VALUES (?, ?, ?, ?)');
        foreach ($staff as $row) {
            $stmt->execute($row);
        }

        $stmt = $pdo->prepare('INSERT INTO audit (at, event) VALUES (?, ?)');
        $stmt->execute(['2026-11-22 03:14:07', 'boot']);
        $stmt->execute(['2026-11-22 03:14:09', 'sentinel online']);
    }
}
