<?php

namespace Coding1688\DevTools\Telemetry;

use Coding1688\DevTools\Redactor;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;

/**
 * Per-request HTTP client and cache telemetry. Counts and timings only; keys
 * and URLs are reduced to a safe shape.
 */
final class RequestTelemetry
{
    /** @var list<array<string, mixed>> */
    private array $http = [];

    /** @var array<int, int> */
    private array $started = [];

    /** @var array<string, int> */
    private array $cache = ['hit' => 0, 'miss' => 0, 'written' => 0, 'forgotten' => 0];

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof RequestSending => $this->start($event),
            $event instanceof ResponseReceived => $this->receive($event),
            $event instanceof ConnectionFailed => $this->fail($event),
            $event instanceof CacheHit => $this->cache['hit']++,
            $event instanceof CacheMissed => $this->cache['miss']++,
            $event instanceof KeyWritten => $this->cache['written']++,
            $event instanceof KeyForgotten => $this->cache['forgotten']++,
            default => null,
        };
    }

    private function start(RequestSending $event): void
    {
        $this->started[spl_object_id($event->request)] = hrtime(true);
    }

    private function receive(ResponseReceived $event): void
    {
        $id = spl_object_id($event->request);
        $started = $this->started[$id] ?? null;
        if ($started === null) {
            return;
        }
        unset($this->started[$id]);
        $this->http[] = [
            'method' => $event->request->method(),
            'url' => $this->maskUrl($event->request->url()),
            'status' => $event->response->status(),
            'ms' => round((hrtime(true) - $started) / 1e6, 2),
            'error' => null,
        ];
    }

    private function fail(ConnectionFailed $event): void
    {
        $id = spl_object_id($event->request);
        $started = $this->started[$id] ?? null;
        unset($this->started[$id]);
        $this->http[] = [
            'method' => $event->request->method(),
            'url' => $this->maskUrl($event->request->url()),
            'status' => null,
            'ms' => $started === null ? null : round((hrtime(true) - $started) / 1e6, 2),
            'error' => Str::limit(Redactor::text((string) $event->exception->getMessage()), 160),
        ];
    }

    private function maskUrl(string $url): string
    {
        return Redactor::url($url);
    }

    /** @return array{http: list<array<string, mixed>>, cache: array<string, int>} */
    public function snapshot(): array
    {
        return [
            'http' => array_slice($this->http, 0, max(1, (int) config('devtools.telemetry.max_http', 20))),
            'cache' => $this->cache,
        ];
    }
}
