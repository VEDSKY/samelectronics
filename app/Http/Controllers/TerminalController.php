<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TerminalController extends Controller
{
    /**
     * Resources exposed through the terminal admin console.
     * slug => [model class, human title, icon]
     *
     * @var array<string, array{0: class-string, 1: string, 2: string}>
     */
    private const RESOURCES = [
        'about' => [\App\Models\About::class, 'About', 'i'],
        'home' => [\App\Models\HomePage::class, 'Home pages', 'H'],
        'news' => [\App\Models\News::class, 'News', 'N'],
        'events' => [\App\Models\Event::class, 'Events', 'E'],
        'releases' => [\App\Models\Releases::class, 'Releases', 'R'],
        'albums' => [\App\Models\Album::class, 'Albums', 'A'],
        'tracks' => [\App\Models\Track::class, 'Tracks', 'T'],
        'photos' => [\App\Models\Photo::class, 'Photos', 'P'],
        'galleries' => [\App\Models\Gallery::class, 'Galleries', 'G'],
        'sorries' => [\App\Models\Sorry::class, 'Sorry pages', 'S'],
    ];

    /** The terminal shell. */
    public function index(): View|\Illuminate\Http\Response
    {
        return response()
            ->view('terminal', [
                'boot' => [
                    'app' => config('app.name', 'samelectronics'),
                    'version' => '2.0.0-phosphor',
                    'locale' => app()->getLocale(),
                ],
            ])
            // never serve a cached page: its CSRF token must match the session
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Initial handshake: identity, environment, menu map. */
    public function bootstrap(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'host' => gethostname() ?: 'samelectronics',
            'app' => config('app.name', 'samelectronics'),
            'version' => '2.0.0-phosphor',
            'env' => config('app.env'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'locale' => app()->getLocale(),
            'locales' => ['ru', 'en'],
            'user' => $this->userPayload($request),
            'menu' => $this->sections(),
            'resources' => array_keys(self::RESOURCES),
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    /** Curated section content; falls back to bundled copy when DB is empty. */
    public function content(string $section): JsonResponse
    {
        $section = Str::lower($section);
        $payload = $this->contents()[$section] ?? null;

        if ($payload === null) {
            return response()->json(['ok' => false, 'error' => "no such section: {$section}"], 404);
        }

        return response()->json(['ok' => true] + $payload);
    }

    /** Music / gallery catalog. */
    public function catalog(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'albums' => $this->albums(),
            'tracks' => $this->tracks(),
            'photos' => $this->photos(),
        ]);
    }

    /** Persist a contact message is no longer supported: the shell keeps no PII. */
    // (removed on purpose — see README)

    /** Terminal login using the standard web guard. */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            return response()->json(['ok' => false, 'error' => 'authentication failed'], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'user' => $this->userPayload($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    /** Public self-registration was removed — the shell has no user accounts. */
    // (removed on purpose — see README)

    /** Admin: aggregate counters. */
    public function adminStats(Request $request): JsonResponse
    {
        if ($deny = $this->guardUser($request)) {
            return $deny;
        }

        $rows = [];
        foreach (self::RESOURCES as $slug => [$model, $title, $icon]) {
            $rows[] = [
                'slug' => $slug,
                'title' => $title,
                'icon' => $icon,
                'count' => $this->safeCount($model),
            ];
        }

        return response()->json(['ok' => true, 'resources' => $rows]);
    }

    /** Admin: list rows of a resource. */
    public function adminIndex(Request $request, string $resource): JsonResponse
    {
        if ($deny = $this->guardUser($request)) {
            return $deny;
        }

        [$model, $title] = $this->resource($resource);

        try {
            $rows = $model::query()->latest('id')->limit(50)->get();
        } catch (Throwable $e) {
            return response()->json(['ok' => true, 'resource' => $resource, 'title' => $title, 'rows' => []], 200);
        }

        return response()->json([
            'ok' => true,
            'resource' => $resource,
            'title' => $title,
            'rows' => $rows->map(fn ($row) => $this->presentRow($row))->all(),
        ]);
    }

    public function adminShow(Request $request, string $resource, string $id): JsonResponse
    {
        if ($deny = $this->guardUser($request)) {
            return $deny;
        }

        [$model, $title] = $this->resource($resource);

        try {
            $row = $model::query()->findOrFail($id);
        } catch (Throwable) {
            return response()->json(['ok' => false, 'error' => "record {$resource}#{$id} not found"], 404);
        }

        return response()->json([
            'ok' => true,
            'resource' => $resource,
            'title' => $title,
            'row' => $this->presentRow($row, full: true),
        ]);
    }

    public function adminStore(Request $request, string $resource): JsonResponse
    {
        if ($deny = $this->guardUser($request)) {
            return $deny;
        }

        [$model] = $this->resource($resource);
        $attributes = (array) $request->input('attributes', []);

        try {
            $row = $model::query()->create($attributes);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'create failed: '.$e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'row' => $this->presentRow($row, full: true)], 201);
    }

    public function adminUpdate(Request $request, string $resource, string $id): JsonResponse
    {
        if ($deny = $this->guardUser($request)) {
            return $deny;
        }

        [$model] = $this->resource($resource);
        $attributes = (array) $request->input('attributes', []);

        try {
            $row = $model::query()->findOrFail($id);
            $row->fill($attributes)->save();
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'update failed: '.$e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'row' => $this->presentRow($row, full: true)]);
    }

    public function adminDestroy(Request $request, string $resource, string $id): JsonResponse
    {
        if ($deny = $this->guardUser($request)) {
            return $deny;
        }

        [$model] = $this->resource($resource);

        try {
            $deleted = $model::query()->whereKey($id)->delete();
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'delete failed: '.$e->getMessage()], 422);
        }

        return response()->json(['ok' => $deleted > 0, 'deleted' => $deleted]);
    }

    // -----------------------------------------------------------------
    // helpers
    // -----------------------------------------------------------------

    /** Reject unauthenticated API calls with JSON instead of a redirect. */
    private function guardUser(Request $request): ?JsonResponse
    {
        if ($request->user()) {
            return null;
        }

        return response()->json([
            'ok' => false,
            'error' => 'unauthenticated',
            'hint' => 'run `login` in the terminal',
        ], 401);
    }

    /** @return array{0: class-string, 1: string} */
    private function resource(string $slug): array    {
        $slug = Str::lower($slug);
        abort_unless(isset(self::RESOURCES[$slug]), 404, "unknown resource: {$slug}");
        [$model, $title] = self::RESOURCES[$slug];

        return [$model, $title];
    }

    private function safeCount(string $model): int
    {
        try {
            return (int) $model::query()->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /** @return array<string, mixed> */
    private function presentRow(object $row, bool $full = false): array
    {
        $attributes = $row->getAttributes();
        if (isset($attributes['password'])) {
            $attributes['password'] = '••••••••';
        }
        if (! $full) {
            $attributes = array_slice($attributes, 0, 6, true);
        }

        return ['id' => $row->getKey()] + $attributes;
    }

    /** @return array<string, mixed>|null */
    private function userPayload(Request $request): ?array
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'admin' => true,
        ];
    }

    /** @return list<array{slug: string, title: string, blurb: string}> */
    private function sections(): array
    {
        return [
            ['slug' => 'home', 'title' => 'start', 'blurb' => 'who we are'],
            ['slug' => 'about', 'title' => 'about', 'blurb' => 'the project'],
            ['slug' => 'news', 'title' => 'news', 'blurb' => 'signal feed'],
            ['slug' => 'events', 'title' => 'events', 'blurb' => 'where to find us'],
            ['slug' => 'releases', 'title' => 'releases', 'blurb' => 'discography'],
            ['slug' => 'music', 'title' => 'music', 'blurb' => 'albums & tracks'],
            ['slug' => 'gallery', 'title' => 'gallery', 'blurb' => 'visual debris'],
            ['slug' => 'links', 'title' => 'links', 'blurb' => 'external nodes'],
            ['slug' => 'contact', 'title' => 'contact', 'blurb' => 'public channels'],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function contents(): array
    {
        return [
            'home' => [
                'title' => 'samelectronics',
                'tagline' => 'metamodern gravity eaters and experimental dreamers',
                'body' => [
                    'experimental interdisciplinary art project from the novgorod republic.',
                    'founded 2016 · music · sound · video · glitch · net art',
                    'riding neural networks through the post-truth era.',
                ],
            ],
            'about' => [
                'title' => 'about',
                'body' => [
                    'samelectronics is an experimental interdisciplinary art project coming from the novgorod republic.',
                    'founded in 2016 in one of the sleeping areas of novgorod as a purely musical project, today samelectronics',
                    'has expanded its boundaries and is engaged in the exploration and transformation of reality and simulacra',
                    'through the prism of music, sound art, video art, glitch art and other forms of media art.',
                ],
                'aspect' => [
                    'a key aspect of our work is the use of artificial intelligence, which allows us to transmit experimental',
                    'dreams and daydreams in a form previously inaccessible to the human imagination. we are riders of the',
                    'metamodern, standing at the intersection of technology and art, convinced that neural networks not only do',
                    'not harm art, but also reveal new sides of it, expanding the horizons of perception.',
                ],
            ],
            'news' => [
                'title' => 'news',
                'note' => 'live signal lives in telegram',
                'items' => [
                    ['date' => '2024-11', 'text' => '«shadow business» — long-awaited album released'],
                    ['date' => '2024-08', 'text' => 'terminal v2 booted: the site became a shell'],
                    ['date' => '2024-06', 'text' => 'residency: sound + glitch experiments'],
                    ['date' => '2016-01', 'text' => 'project founded in novgorod'],
                ],
            ],
            'events' => [
                'title' => 'events',
                'note' => 'schedule is announced in telegram',
                'items' => [
                    ['date' => 'TBA', 'text' => 'audio-visual live set'],
                    ['date' => 'TBA', 'text' => 'glitch art installation'],
                ],
            ],
            'releases' => [
                'title' => 'releases',
                'note' => 'full discography on bandcamp',
                'items' => [
                    ['year' => '2024', 'title' => 'shadow business', 'type' => 'album'],
                    ['year' => '2023', 'title' => 'dream transmission', 'type' => 'ep'],
                    ['year' => '2021', 'title' => 'analog / digital', 'type' => 'album'],
                    ['year' => '2019', 'title' => 'simulacra', 'type' => 'album'],
                ],
            ],
            'contact' => [
                'title' => 'contact',
                'note' => 'or write directly in telegram',
                'channels' => [
                    ['label' => 'telegram', 'value' => '@samelectronics1', 'url' => 'https://t.me/samelectronics1'],
                    ['label' => 'telegram (community)', 'value' => '@weltfuturisten', 'url' => 'https://t.me/weltfuturisten'],
                    ['label' => 'bandcamp', 'value' => 'samelectronics.bandcamp.com', 'url' => 'https://samelectronics.bandcamp.com'],
                ],
            ],
            'links' => [
                'title' => 'links',
                'items' => [
                    ['label' => 'telegram', 'url' => 'https://t.me/weltfuturisten'],
                    ['label' => 'telegram (contact)', 'url' => 'https://t.me/samelectronics1'],
                    ['label' => 'bandcamp', 'url' => 'https://samelectronics.bandcamp.com'],
                    ['label' => 'spotify', 'url' => 'https://open.spotify.com'],
                    ['label' => 'apple music', 'url' => 'https://music.apple.com'],
                    ['label' => 'youtube', 'url' => 'https://youtube.com'],
                    ['label' => 'instagram', 'url' => 'https://instagram.com'],
                ],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function albums(): array
    {
        return [
            ['id' => 1, 'title' => 'shadow business', 'year' => 2024, 'tracks' => 9],
            ['id' => 2, 'title' => 'dream transmission', 'year' => 2023, 'tracks' => 5],
            ['id' => 3, 'title' => 'analog / digital', 'year' => 2021, 'tracks' => 11],
            ['id' => 4, 'title' => 'simulacra', 'year' => 2019, 'tracks' => 8],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function tracks(): array
    {
        return [
            ['no' => 1, 'title' => 'gravity eater', 'album' => 'shadow business', 'len' => '04:12'],
            ['no' => 2, 'title' => 'phosphor bloom', 'album' => 'shadow business', 'len' => '05:40'],
            ['no' => 3, 'title' => 'sleeping area', 'album' => 'shadow business', 'len' => '03:58'],
            ['no' => 4, 'title' => 'dream transmission pt.1', 'album' => 'dream transmission', 'len' => '06:21'],
            ['no' => 5, 'title' => 'simulacrum', 'album' => 'simulacra', 'len' => '07:03'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function photos(): array
    {
        return [
            ['id' => 1, 'title' => 'crt study 01', 'tag' => 'glitch'],
            ['id' => 2, 'title' => 'novgorod sleeping area', 'tag' => 'doc'],
            ['id' => 3, 'title' => 'signal bleed', 'tag' => 'video'],
            ['id' => 4, 'title' => 'neural daydream', 'tag' => 'ai'],
        ];
    }
}
