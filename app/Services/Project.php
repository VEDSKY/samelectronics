<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Project — the "real site" as the visitor sees it.
 *
 * Reads resolve straight to the actual repository (`base_path()`), so the shell
 * exposes the whole project: app/, config/, routes/, vendor/, composer.json and
 * so on. Writes never touch the repository: they land in a throwaway overlay
 * (`storage/app/sandbox.overlay`) that is served back as if it were the file,
 * and the sentinel wipes on every pulse.
 *
 * That split is what makes the performance safe: the visitor gets a complete,
 * believable project to read and "destroy", yet a successful edit can never
 * replace executing code, so nobody can escape the site (no RCE, no path out).
 *
 * A short manifest of critical spots (`/.sentinel/critical.json`) plus a decoy
 * DB row track the damage. Corrupt or delete them and the shell reports real
 * PHP/Laravel exceptions; wipe them all and the page collapses into databending.
 */
class Project
{
    /** Critical relative paths whose damage degrades the running shell. */
    public const CANARIES = [
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

    /** Paths that are hidden from the visitor on purpose (secrets / privacy). */
    private const SHIELDED = ['.git', 'storage', 'node_modules'];

    public function root(): string
    {
        return '/var/www/site';
    }

    public function owns(string $abs): bool
    {
        return $abs === $this->root() || str_starts_with($abs, $this->root().'/');
    }

    public function relative(string $abs): string
    {
        if ($abs === $this->root()) {
            return '';
        }

        return trim(substr($abs, strlen($this->root())), '/');
    }

    public function absolute(string $rel): string
    {
        $rel = trim($rel, '/');

        return $rel === '' ? $this->root() : $this->root().'/'.$rel;
    }

    // ------------------------------------------------------------- storage

    public function overlayPath(): string
    {
        return storage_path('app/sandbox.overlay');
    }

    public function tombstonesPath(): string
    {
        return storage_path('app/sandbox.tombstones.json');
    }

    public function modesPath(): string
    {
        return storage_path('app/sandbox.project.modes.json');
    }

    // ------------------------------------------------------------- guards

    public function shielded(string $rel): bool
    {
        $rel = trim($rel, '/');
        $base = basename($rel);

        // let the example env through; hide every real env file
        if ($base === '.env.example') {
            return false;
        }
        // `.env` itself is never served from disk — a decoy is shown instead
        if ($base === '.env') {
            return false;
        }
        if (str_starts_with($base, '.env.')) {
            return true;
        }
        if (str_ends_with($rel, '.sqlite') || str_ends_with($rel, '.sqlite-journal')) {
            return true;
        }
        if (str_contains($rel, '.sqlite')) {
            return true;
        }

        foreach (self::SHIELDED as $prefix) {
            if ($rel === $prefix || str_starts_with($rel, $prefix.'/')) {
                return true;
            }
        }

        return false;
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
        if (! is_array($decoded)) {
            return ['wiped' => false, 'paths' => []];
        }

        return [
            'wiped' => (bool) ($decoded['wiped'] ?? false),
            'paths' => is_array($decoded['paths'] ?? null) ? $decoded['paths'] : [],
        ];
    }

    /**
     * @param  array{wiped: bool, paths: array<string, bool>}  $tombstones
     */
    private function saveTombstones(array $tombstones): void
    {
        File::ensureDirectoryExists(dirname($this->tombstonesPath()));
        File::put($this->tombstonesPath(), json_encode($tombstones, JSON_PRETTY_PRINT));
    }

    public function hidden(string $rel): bool
    {
        $rel = trim($rel, '/');
        $tombstones = $this->tombstones();

        if ($tombstones['wiped']) {
            return true;
        }

        if ($rel === '') {
            return false;
        }

        $segments = explode('/', $rel);
        $walk = '';
        foreach ($segments as $segment) {
            $walk = $walk === '' ? $segment : $walk.'/'.$segment;
            if (isset($tombstones['paths'][$walk])) {
                return true;
            }
        }

        return false;
    }

    public function wiped(): bool
    {
        return $this->tombstones()['wiped'];
    }

    // ----------------------------------------------------------- decoys

    /**
     * Synthetic files that must never expose the real thing.
     *
     * @return array<string, string>
     */
    private function decoys(): array
    {
        return [
            '.env' => implode("\n", [
                'APP_NAME=samelectronics',
                'APP_ENV=production',
                'APP_KEY=base64:NOTREALKEYcGhvc3Bob3Jvc09TPT09PQ==',
                'APP_DEBUG=false',
                'DB_CONNECTION=sqlite',
                'DB_DATABASE=/var/www/site/database/sandbox.db',
                'SESSION_DRIVER=file',
                'SENTINEL_KEY=SAM{th3_w4tch3r_n3v3r_sl33ps}',
                'MAIL_PASSWORD=hunter2-but-not-really',
                'TELEGRAM_BOT_TOKEN=123456:AAH-decoy-decoy-decoy',
                '',
            ]),
            '.sentinel/critical.json' => json_encode([
                'watchdog' => 'sentinel',
                'interval_ms' => 8000,
                'note' => 'these are the load-bearing files. damage them and the shell starts to bleed.',
                'critical' => self::CANARIES,
                'record' => 'sandbox.sqlite::sentinel(id=1)',
                'wipe' => 'destroy this manifest (or every canary) and the page itself comes apart.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function decoy(string $rel): ?string
    {
        return $this->decoys()[$rel] ?? null;
    }

    // ------------------------------------------------------------ overlay

    private function overlayFull(string $rel): string
    {
        $rel = trim($rel, '/');

        return rtrim($this->overlayPath(), '/').($rel === '' ? '' : '/'.$rel);
    }

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

    private function overlayExists(string $rel): bool
    {
        return file_exists($this->overlayFull($rel));
    }

    // --------------------------------------------------------- resolution

    public function exists(string $abs): bool
    {
        $rel = $this->relative($abs);
        if ($this->shielded($rel) || $this->hidden($rel)) {
            return false;
        }

        return $this->overlayExists($rel)
            || $this->decoy($rel) !== null
            || is_file(base_path($rel))
            || is_dir(base_path($rel));
    }

    public function isDirectory(string $abs): bool
    {
        $rel = $this->relative($abs);
        if ($this->shielded($rel) || $this->hidden($rel)) {
            return false;
        }

        return $this->overlayIsDir($rel) || is_dir(base_path($rel)) || $this->isDecoyDir($rel);
    }

    // ------------------------------------------------------------- listing

    /**
     * @return array<string, mixed>
     */
    public function list(string $abs): array
    {
        $rel = $this->relative($abs);

        if ($this->shielded($rel)) {
            return ['ok' => false, 'error' => "permission denied: {$abs} (sentinel lock)"];
        }
        if ($this->hidden($rel)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        if ($rel !== '' && ! is_dir(base_path($rel)) && ! $this->overlayIsDir($rel)) {
            if ($this->overlayExists($rel) || is_file(base_path($rel)) || $this->decoy($rel) !== null) {
                return $this->read($abs);
            }

            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        $names = [];

        // real directory entries
        $realDir = base_path($rel);
        if (is_dir($realDir)) {
            foreach (scandir($realDir) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $names[$name] = true;
            }
        }

        // overlay entries
        $overlayDir = $this->overlayFull($rel);
        if (is_dir($overlayDir)) {
            foreach (scandir($overlayDir) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $names[$name] = true;
            }
        }

        // synthetic / decoy entries
        foreach (array_keys($this->decoys()) as $decoyRel) {
            $prefix = $rel === '' ? '' : $rel.'/';
            if ($prefix !== '' && ! str_starts_with($decoyRel, $prefix)) {
                continue;
            }
            $rest = substr($decoyRel, strlen($prefix));
            if ($rest === '') {
                continue;
            }
            $names[explode('/', $rest)[0]] = true;
        }

        $entries = [];
        foreach (array_keys($names) as $name) {
            $childRel = $rel === '' ? $name : $rel.'/'.$name;

            if ($this->shielded($childRel) || $this->hidden($childRel)) {
                continue;
            }

            $childAbs = $this->absolute($childRel);
            $isDir = $this->overlayIsDir($childRel) || is_dir(base_path($childRel)) || $this->isDecoyDir($childRel);

            $size = 0;
            $mtime = time();
            if (! $isDir) {
                $overlay = $this->overlayFull($childRel);
                if (is_file($overlay)) {
                    $size = (int) filesize($overlay);
                    $mtime = (int) filemtime($overlay);
                } elseif (is_file(base_path($childRel))) {
                    $size = (int) filesize(base_path($childRel));
                    $mtime = (int) filemtime(base_path($childRel));
                } elseif (($decoy = $this->decoy($childRel)) !== null) {
                    $size = strlen($decoy);
                }
            }

            $entries[] = [
                'name' => $this->overlayIsDir($childRel) || is_dir(base_path($childRel)) || $this->isDecoyDir($childRel)
                    ? $name
                    : $name,
                'type' => $isDir ? 'dir' : 'file',
                'size' => $isDir ? 0 : $size,
                'mode' => $this->mode($childRel, $isDir),
                'owner' => 'www-data',
                'mtime' => date('Y-m-d H:i', $mtime),
            ];
        }

        usort($entries, fn ($a, $b) => strcmp((string) $a['name'], (string) $b['name']));

        return ['ok' => true, 'path' => $abs, 'type' => 'dir', 'entries' => $entries];
    }

    private function overlayIsDir(string $rel): bool
    {
        return is_dir($this->overlayFull($rel));
    }

    private function isDecoyDir(string $rel): bool
    {
        $prefix = $rel === '' ? '' : $rel.'/';
        foreach (array_keys($this->decoys()) as $decoyRel) {
            if ($prefix === '' || str_starts_with($decoyRel, $prefix)) {
                $rest = substr($decoyRel, strlen($prefix));
                if ($rest !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    // ------------------------------------------------------------- reading

    /**
     * @return array<string, mixed>
     */
    public function read(string $abs): array
    {
        $rel = $this->relative($abs);

        if ($this->shielded($rel)) {
            return ['ok' => false, 'error' => "permission denied: {$abs} (sentinel lock)"];
        }
        if ($this->hidden($rel)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        $overlay = $this->overlayFull($rel);
        if (is_file($overlay)) {
            return $this->filePayload($abs, (string) File::get($overlay), (int) filesize($overlay));
        }

        if (($decoy = $this->decoy($rel)) !== null) {
            return $this->filePayload($abs, $decoy, strlen($decoy));
        }

        $real = base_path($rel);
        if (is_file($real)) {
            $size = (int) filesize($real);
            $content = (string) File::get($real, false);
            $truncated = false;
            if (strlen($content) > 400_000) {
                $content = substr($content, 0, 400_000);
                $truncated = true;
            }

            $payload = $this->filePayload($abs, $content, $size);
            if ($truncated) {
                $payload['truncated'] = true;
            }

            return $payload;
        }

        return ['ok' => false, 'error' => "cannot read: {$abs}"];
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(string $abs, string $content, int $size): array
    {
        $rel = $this->relative($abs);

        return [
            'ok' => true,
            'path' => $abs,
            'type' => 'file',
            'size' => $size,
            'mode' => $this->mode($rel, false),
            'owner' => 'www-data',
            'mtime' => date('Y-m-d H:i', time()),
            'content' => $content,
        ];
    }

    // ------------------------------------------------------------- writing

    /**
     * @return array<string, mixed>
     */
    public function write(string $abs, string $content): array
    {
        $rel = $this->relative($abs);

        if ($this->shielded($rel)) {
            return ['ok' => false, 'error' => "permission denied: {$abs} (sentinel lock)"];
        }

        if (is_dir(base_path($rel)) || $this->overlayIsDir($rel)) {
            return ['ok' => false, 'error' => "is a directory: {$abs}"];
        }

        $existed = $this->exists($abs);

        $target = $this->overlayFull($rel);
        File::ensureDirectoryExists(dirname($target));
        File::put($target, $content);

        // writing over a tombstoned path revives it
        $tombstones = $this->tombstones();
        foreach (array_keys($tombstones['paths']) as $dead) {
            if ($dead === $rel || str_starts_with($rel, $dead.'/')) {
                unset($tombstones['paths'][$dead]);
            }
        }
        $this->saveTombstones($tombstones);

        return [
            'ok' => true,
            'path' => $abs,
            'bytes' => strlen($content),
            'verb' => $existed ? 'modified' : 'created',
            'overlay' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mkdir(string $abs): array
    {
        $rel = $this->relative($abs);

        if ($this->shielded($rel)) {
            return ['ok' => false, 'error' => "permission denied: {$abs} (sentinel lock)"];
        }
        if ($this->exists($abs)) {
            return ['ok' => false, 'error' => "already exists: {$abs}"];
        }

        File::ensureDirectoryExists($this->overlayFull($rel));

        return ['ok' => true, 'path' => $abs, 'verb' => 'created', 'overlay' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(string $abs, bool $recursive = false): array
    {
        $rel = $this->relative($abs);

        if ($rel === '') {
            return $this->wipeAll();
        }

        if ($this->shielded($rel)) {
            return ['ok' => false, 'error' => "permission denied: {$abs} (sentinel lock)"];
        }
        if ($this->hidden($rel)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        $real = base_path($rel);
        $isRealDir = is_dir($real);
        $isOverlayDir = $this->overlayIsDir($rel);
        $exists = $isRealDir || $isOverlayDir || is_file($real) || $this->overlayExists($rel) || $this->decoy($rel) !== null;

        if (! $exists) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        if (($isRealDir || $isOverlayDir) && ! $recursive) {
            return ['ok' => false, 'error' => "{$abs} is a directory (use -r)"];
        }

        $removed = 0;
        if ($isRealDir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $removed++;
                }
            }
        }

        // drop any overlay content under this path
        $overlay = $this->overlayFull($rel);
        if (is_dir($overlay)) {
            $removed += count(File::allFiles($overlay));
            File::deleteDirectory($overlay);
        } elseif (is_file($overlay)) {
            File::delete($overlay);
            $removed++;
        }

        // hide the real file/dir with a tombstone
        $tombstones = $this->tombstones();
        $tombstones['paths'][$rel] = true;
        $this->saveTombstones($tombstones);

        return ['ok' => true, 'path' => $abs, 'removed' => max(1, $removed), 'verb' => 'removed', 'overlay' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public function wipeAll(): array
    {
        File::deleteDirectory($this->overlayPath());
        File::ensureDirectoryExists($this->overlayPath());
        $this->saveTombstones(['wiped' => true, 'paths' => []]);

        return ['ok' => true, 'path' => $this->root(), 'removed' => count(self::CANARIES), 'verb' => 'wiped', 'overlay' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public function chmod(string $abs, string $mode): array
    {
        $rel = $this->relative($abs);

        if ($this->shielded($rel)) {
            return ['ok' => false, 'error' => "permission denied: {$abs} (sentinel lock)"];
        }
        if (! $this->exists($abs)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        $octal = intval($mode, 8) & 0777;
        $modes = $this->modes();
        $modes[$rel] = $octal;
        File::put($this->modesPath(), json_encode($modes, JSON_PRETTY_PRINT));

        return ['ok' => true, 'path' => $abs, 'mode' => $this->format($octal, $this->overlayIsDir($rel) || is_dir(base_path($rel))), 'verb' => 'chmod'];
    }

    // ------------------------------------------------------------- integrity

    /**
     * @return array<string, mixed>
     */
    public function integrity(): array
    {
        $tombstones = $this->tombstones();
        $wiped = $tombstones['wiped'];

        $broken = [];
        foreach (self::CANARIES as $rel) {
            if ($wiped || $this->hidden($rel)) {
                $broken[] = ['path' => $rel, 'state' => 'deleted'];
                continue;
            }
            if ($this->overlayExists($rel)) {
                $broken[] = [
                    'path' => $rel,
                    'state' => 'corrupted',
                    'lint' => str_ends_with($rel, '.php') ? $this->lint($rel) : null,
                ];
            }
        }

        $manifestBroken = $wiped || $this->hidden('.sentinel/critical.json') || $this->overlayExists('.sentinel/critical.json');
        $dbBroken = ! $this->dbCanaryOk();

        $level = count($broken) + ($manifestBroken ? 1 : 0) + ($dbBroken ? 1 : 0);
        $total = count(self::CANARIES) + 2;
        $catastrophic = $wiped || $manifestBroken || $level >= $total;

        return [
            'ok' => true,
            'at' => now()->toIso8601String(),
            'level' => $level,
            'total' => $total,
            'wiped' => $wiped,
            'manifest_broken' => $manifestBroken,
            'db_broken' => $dbBroken,
            'catastrophic' => $catastrophic,
            'broken' => $broken,
            'errors' => $this->errorsFor($broken, $manifestBroken, $dbBroken),
        ];
    }

    private function dbCanaryOk(): bool
    {
        try {
            return DB::connection('sandbox')->table('sentinel')->where('id', 1)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function lint(string $rel): ?string
    {
        $path = $this->overlayFull($rel);
        if (! is_file($path) || ! function_exists('exec')) {
            return null;
        }

        $php = PHP_BINARY ?: 'php';
        $output = [];
        $code = 0;
        @exec(escapeshellarg($php).' -l '.escapeshellarg($path).' 2>&1', $output, $code);

        if ($code === 0) {
            return null;
        }

        return trim(implode("\n", $output));
    }

    /**
     * Build authentic-looking PHP/Laravel errors from real exception objects.
     *
     * @param  list<array<string, mixed>>  $broken
     * @return list<array<string, mixed>>
     */
    private function errorsFor(array $broken, bool $manifestBroken, bool $dbBroken): array
    {
        $errors = [];

        if ($dbBroken) {
            $pdo = new \PDOException('SQLSTATE[HY000]: General error: 1 no such table: sentinel');
            $errors[] = $this->describe(new QueryException('sandbox', 'select * from sentinel where id = 1', [], $pdo));
        }

        if ($manifestBroken) {
            $errors[] = $this->describe(new \RuntimeException(
                'file_get_contents(/var/www/site/.sentinel/critical.json): Failed to open stream: No such file or directory'
            ), '/var/www/site/.sentinel/critical.json', 1);
        }

        foreach (array_slice($broken, 0, 8) as $item) {
            $rel = (string) $item['path'];
            $file = '/var/www/site/'.$rel;

            if (($item['state'] ?? '') === 'deleted') {
                if (str_ends_with($rel, '.php')) {
                    $errors[] = $this->describe(new \Error('Class "'.$this->classFor($rel).'" not found'), $file, 1);
                } else {
                    $errors[] = $this->describe(new \RuntimeException(
                        "file_get_contents({$file}): Failed to open stream: No such file or directory"
                    ), $file, 1);
                }
                continue;
            }

            $lint = $item['lint'] ?? null;
            if (is_string($lint) && $lint !== '') {
                $line = 1;
                if (preg_match('/on line (\d+)/', $lint, $m)) {
                    $line = (int) $m[1];
                }
                $lines = array_values(array_filter(
                    preg_split('/\R/', $lint) ?: [],
                    fn ($l) => trim($l) !== '' && ! str_starts_with(trim($l), 'Errors parsing')
                ));
                $first = $lines[0] ?? $lint;
                $message = preg_replace('/\s+in\s+\/.*$/', '', $first) ?: $first;
                $errors[] = $this->describe(new \ParseError(trim($message)), $file, $line);
            } else {
                $errors[] = $this->describe(new \RuntimeException(
                    "integrity violation: {$file} was modified outside the sentinel"
                ), $file, 1);
            }
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(Throwable $e, ?string $file = null, ?int $line = null): array
    {
        $trace = explode("\n", str_replace(base_path(), '/var/www/site', $e->getTraceAsString()));

        return [
            'class' => $e::class,
            'message' => $e->getMessage(),
            'file' => $file ?? str_replace(base_path(), '/var/www/site', $e->getFile()),
            'line' => $line ?? $e->getLine(),
            'trace' => array_slice(array_values(array_filter($trace, fn ($l) => $l !== '')), 0, 6),
        ];
    }

    private function classFor(string $rel): string
    {
        $path = str_replace(['app/', '.php'], ['', ''], $rel);

        return 'App\\'.str_replace('/', '\\', $path);
    }

    // ------------------------------------------------------------- modes

    /**
     * @return array<string, int>
     */
    private function modes(): array
    {
        $path = $this->modesPath();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mode(string $rel, bool $isDir): string
    {
        $modes = $this->modes();
        $octal = $modes[$rel] ?? null;

        if ($octal === null) {
            $octal = $isDir ? 0755 : (str_ends_with($rel, '.sh') ? 0755 : (str_ends_with($rel, '.env') || $rel === '.env' ? 0600 : 0644));
        }

        return $this->format((int) $octal, $isDir);
    }

    private function format(int $octal, bool $isDir): string
    {
        $bits = '';
        for ($i = 2; $i >= 0; $i--) {
            $bits .= (($octal >> ($i * 3)) & 4) ? 'r' : '-';
            $bits .= (($octal >> ($i * 3)) & 2) ? 'w' : '-';
            $bits .= (($octal >> ($i * 3)) & 1) ? 'x' : '-';
        }

        return ($isDir ? 'd' : '-').$bits;
    }
}