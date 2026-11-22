<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Sandbox — a small, path-safe filesystem engine over the throwaway tree at
 * `storage/app/sandbox`. The terminal's file commands talk to this, never to
 * the real project. Every path is normalised lexically and clamped to the
 * sandbox root, so `..` can never escape.
 */
class Sandbox
{
    public function __construct(
        private readonly Sentinel $sentinel,
        private readonly Project $project,
    ) {}

    public function root(): string
    {
        return rtrim($this->sentinel->livePath(), DIRECTORY_SEPARATOR);
    }

    /**
     * Lexically normalise an absolute path. `..` is resolved, never escaped.
     */
    public function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }

        return '/'.implode('/', $parts);
    }

    /**
     * Resolve a request path to an absolute path inside the sandbox.
     */
    public function full(string $path, ?string $cwd = null): string
    {
        $path = $this->absolute($path, $cwd);

        return $this->root().$this->normalize($path);
    }

    public function absolute(string $path, ?string $cwd = null): string
    {
        if ($path === '' || $path === '~') {
            return $path === '~' ? '/var/www/site' : ($cwd ?: '/');
        }
        if ($path[0] !== '/') {
            $base = $cwd && $cwd[0] === '/' ? $cwd : '/var/www/site';
            $path = rtrim($base, '/').'/'.$path;
        }

        return $this->normalize($path);
    }

    public function exists(string $path, ?string $cwd = null): bool
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->exists($abs);
        }

        return file_exists($this->full($path, $cwd));
    }

    public function isDir(string $path, ?string $cwd = null): bool
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->isDirectory($abs);
        }

        return is_dir($this->full($path, $cwd));
    }

    /**
     * @return array<string, mixed>
     */
    public function stat(string $path, ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        $full = $this->full($path, $cwd);

        if (! file_exists($full)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        $isDir = is_dir($full);

        return [
            'ok' => true,
            'path' => $abs,
            'name' => basename($abs) ?: '/',
            'type' => $isDir ? 'dir' : 'file',
            'size' => $isDir ? 0 : (int) filesize($full),
            'mode' => $this->mode($full, $isDir),
            'owner' => $this->owner($abs),
            'mtime' => date('Y-m-d H:i', (int) filemtime($full)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function list(string $path = '/', ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->list($abs);
        }

        $full = $this->full($path, $cwd);

        if (! file_exists($full)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }
        if (! is_dir($full)) {
            return $this->read($path, $cwd);
        }

        $entries = [];
        foreach (scandir($full) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $child = $full.'/'.$name;
            $childAbs = rtrim($abs, '/').'/'.$name;
            $isDir = is_dir($child);
            $entries[] = [
                'name' => $name,
                'type' => $isDir ? 'dir' : 'file',
                'size' => $isDir ? 0 : (int) filesize($child),
                'mode' => $this->mode($child, $isDir),
                'owner' => $this->owner($childAbs),
                'mtime' => date('Y-m-d H:i', (int) filemtime($child)),
            ];
        }

        usort($entries, fn ($a, $b) => $a['name'] <=> $b['name']);

        return ['ok' => true, 'path' => $abs, 'type' => 'dir', 'entries' => $entries];
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $path, ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->read($abs);
        }

        $full = $this->full($path, $cwd);

        if (! is_file($full)) {
            return ['ok' => false, 'error' => "cannot read: {$abs}"];
        }

        $content = (string) File::get($full);

        return [
            'ok' => true,
            'path' => $abs,
            'type' => 'file',
            'size' => strlen($content),
            'mode' => $this->mode($full, false),
            'owner' => $this->owner($abs),
            'mtime' => date('Y-m-d H:i', (int) filemtime($full)),
            'content' => $content,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function write(string $path, string $content, ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->write($abs, $content);
        }

        $full = $this->full($path, $cwd);

        if (is_dir($full)) {
            return ['ok' => false, 'error' => "is a directory: {$abs}"];
        }

        File::ensureDirectoryExists(dirname($full));
        File::put($full, $content);

        $verb = file_exists($full) ? 'modified' : 'created';

        return ['ok' => true, 'path' => $abs, 'bytes' => strlen($content), 'verb' => $verb];
    }

    /**
     * @return array<string, mixed>
     */
    public function mkdir(string $path, ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->mkdir($abs);
        }

        $full = $this->full($path, $cwd);

        if (file_exists($full)) {
            return ['ok' => false, 'error' => "already exists: {$abs}"];
        }

        File::ensureDirectoryExists($full);

        return ['ok' => true, 'path' => $abs, 'verb' => 'created'];
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(string $path, bool $recursive = false, ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->remove($abs, $recursive);
        }

        $full = $this->full($path, $cwd);

        if ($abs === '/' || $abs === '') {
            // wiping the whole root is allowed — the sentinel rebuilds it, and
            // the project overlay collapses into total databending
            $n = count(File::allFiles($full));
            foreach (File::directories($full) as $dir) {
                File::deleteDirectory($dir);
            }
            foreach (File::files($full) as $file) {
                File::delete($file);
            }
            $this->project->wipeAll();

            return ['ok' => true, 'path' => $abs, 'removed' => $n + 1, 'verb' => 'wiped'];
        }

        if (! file_exists($full)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        if (is_dir($full)) {
            if (! $recursive) {
                return ['ok' => false, 'error' => "{$abs} is a directory (use -r)"];
            }
            $n = count(File::allFiles($full));
            File::deleteDirectory($full);

            return ['ok' => true, 'path' => $abs, 'removed' => $n, 'verb' => 'removed'];
        }

        File::delete($full);

        return ['ok' => true, 'path' => $abs, 'removed' => 1, 'verb' => 'removed'];
    }

    /**
     * @return array<string, mixed>
     */
    public function chmod(string $path, string $mode, ?string $cwd = null): array
    {
        $abs = $this->absolute($path, $cwd);
        if ($this->project->owns($abs)) {
            return $this->project->chmod($abs, $mode);
        }

        $full = $this->full($path, $cwd);

        if (! file_exists($full)) {
            return ['ok' => false, 'error' => "no such file or directory: {$abs}"];
        }

        $octal = intval($mode, 8) & 0777;
        @chmod($full, $octal);

        // persist the change in the sidecar (filesystems without ownership
        // ignore chmod, so the sandbox keeps its own record)
        $modes = $this->modes();
        $modes[$this->relative($full)] = $octal;
        File::put($this->sentinel->modesPath(), json_encode($modes, JSON_PRETTY_PRINT));

        return ['ok' => true, 'path' => $abs, 'mode' => $this->format($octal, is_dir($full)), 'verb' => 'chmod'];
    }

    private function mode(string $full, bool $isDir): string
    {
        $rel = $this->relative($full);
        $modes = $this->modes();
        $octal = $modes[$rel] ?? ($isDir ? 0755 : 0644);

        return $this->format($octal, $isDir);
    }

    private function relative(string $full): string
    {
        return ltrim(str_replace($this->root(), '', $full), '/');
    }

    /**
     * @return array<string, int>
     */
    private function modes(): array
    {
        $path = $this->sentinel->modesPath();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [];
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

    private function owner(string $abs): string
    {
        return match (true) {
            str_starts_with($abs, '/root'), str_starts_with($abs, '/etc'),
            str_starts_with($abs, '/usr') => 'root',
            str_starts_with($abs, '/var/www') => 'www-data',
            default => 'root',
        };
    }
}
