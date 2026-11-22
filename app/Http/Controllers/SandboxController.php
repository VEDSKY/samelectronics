<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Project;
use App\Services\Sandbox;
use App\Services\Sentinel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * The "hackable" surface of the terminal.
 *
 * Every endpoint here operates on the throwaway sandbox only: file reads and
 * writes hit `storage/app/sandbox`, the SQL endpoint talks to a separate
 * throwaway sqlite file, and the reflect endpoint is a deliberate XSS sink
 * that the client renders inside a sandboxed iframe. The sentinel restores
 * everything on every pulse, so nothing done here is permanent.
 */
class SandboxController extends Controller
{
    public function __construct(
        private readonly Sandbox $fs,
        private readonly Sentinel $sentinel,
        private readonly Project $project,
    ) {}

    // ----------------------------------------------------------------- fs

    public function fsList(Request $request): JsonResponse
    {
        $path = (string) $request->query('path', '/');
        $cwd = $request->query('cwd');
        $cwd = is_string($cwd) ? $cwd : null;

        return response()->json($this->fs->list($path, $cwd));
    }

    public function fsRead(Request $request): JsonResponse
    {
        $path = (string) $request->query('path', '');
        $cwd = $request->query('cwd');
        $cwd = is_string($cwd) ? $cwd : null;

        return response()->json($this->fs->read($path, $cwd));
    }

    public function fsWrite(Request $request): JsonResponse
    {
        $result = $this->fs->write(
            (string) $request->input('path', ''),
            (string) $request->input('content', ''),
            $this->cwd($request)
        );

        if ($result['ok'] ?? false) {
            $this->intrusion(sprintf('write %s (%d bytes)', $result['path'], $result['bytes']));
        }

        return response()->json($result);
    }

    public function fsMkdir(Request $request): JsonResponse
    {
        $result = $this->fs->mkdir((string) $request->input('path', ''), $this->cwd($request));

        if ($result['ok'] ?? false) {
            $this->intrusion('mkdir '.$result['path']);
        }

        return response()->json($result);
    }

    public function fsRemove(Request $request): JsonResponse
    {
        $result = $this->fs->remove(
            (string) $request->input('path', ''),
            (bool) $request->boolean('recursive'),
            $this->cwd($request)
        );

        if ($result['ok'] ?? false) {
            $this->intrusion(sprintf('rm %s (%d removed)', $result['path'], $result['removed']));
        }

        return response()->json($result);
    }

    public function fsChmod(Request $request): JsonResponse
    {
        $result = $this->fs->chmod(
            (string) $request->input('path', ''),
            (string) $request->input('mode', '644'),
            $this->cwd($request)
        );

        if ($result['ok'] ?? false) {
            $this->intrusion(sprintf('chmod %s %s', $result['path'], $result['mode']));
        }

        return response()->json($result);
    }

    // --------------------------------------------------------------- xss

    /**
     * A deliberately vulnerable reflection sink. The value is returned
     * verbatim and the client renders it inside an isolated iframe.
     */
    public function reflect(Request $request): JsonResponse
    {
        $payload = (string) $request->input('payload', '');

        $this->intrusion('reflected payload ('.strlen($payload).' bytes)');

        return response()->json([
            'ok' => true,
            'server' => 'samelectronics/2.0-phosphor',
            'powered' => 'PHP '.PHP_VERSION,
            'reflected' => $payload,
            'headers' => [
                'Set-Cookie: sam_session='.Str::random(40).'; path=/; HttpOnly',
                'X-Powered-By: phosphor-os/2.0',
            ],
            'note' => 'input reflected verbatim into an html context — classic reflected XSS',
        ]);
    }

    // --------------------------------------------------------------- sql

    /**
     * Run a raw query against the throwaway sandbox database. The query is
     * concatenated on purpose so injection actually works — but it can only
     * ever reach the decoy database, never the application's own data.
     */
    public function sql(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('query', ''));

        if ($query === '') {
            return response()->json(['ok' => false, 'error' => 'empty query']);
        }

        $started = microtime(true);

        try {
            $rows = DB::connection('sandbox')->select($query);
            $this->intrusion('sql select: '.Str::limit($query, 120));

            return response()->json([
                'ok' => true,
                'rows' => array_map(fn ($row) => (array) $row, $rows),
                'count' => count($rows),
                'ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        } catch (Throwable $e) {
            // fall back to a non-select statement so `drop`/`update` etc. also work
            try {
                $affected = DB::connection('sandbox')->statement($query);
                $this->intrusion('sql exec: '.Str::limit($query, 120));

                return response()->json(['ok' => true, 'rows' => [], 'count' => 0, 'affected' => $affected]);
            } catch (Throwable $inner) {
                return response()->json([
                    'ok' => false,
                    'error' => $inner->getMessage(),
                    'sql' => $query,
                ], 200);
            }
        }
    }

    // ---------------------------------------------------------- sentinel

    /**
     * Current damage to the project's critical spots. The client turns this
     * into glitches, real exception traces and, at the limit, databending.
     */
    public function integrity(): JsonResponse
    {
        return response()->json($this->project->integrity());
    }

    public function sentinelStatus(): JsonResponse
    {
        return response()->json($this->sentinel->status());
    }

    public function sentinelLog(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'log' => $this->sentinel->log((int) $request->query('limit', 60)),
        ]);
    }

    public function sentinelPulse(): JsonResponse
    {
        $result = $this->sentinel->restore('pulse');

        return response()->json($result);
    }

    // ------------------------------------------------------------ helpers

    private function cwd(Request $request): ?string
    {
        $cwd = $request->input('cwd');

        return is_string($cwd) && $cwd !== '' ? $cwd : null;
    }

    private function intrusion(string $event): void
    {
        $this->sentinel->append('['.now()->format('H:i:s').'] intrusion: '.$event);
    }
}
