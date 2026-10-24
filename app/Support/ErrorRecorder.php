<?php

namespace App\Support;

use App\Mail\ErrorAlert;
use App\Models\ErrorLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Saves errors to the error_logs table for the admin (Settings → Error log).
 * The same error (same source/type/message/place) is grouped into one row with a counter.
 * Recording must never break the page itself, so every failure here is swallowed.
 */
class ErrorRecorder
{
    private static bool $recording = false;

    public static function exception(Throwable $e, string $source = 'server', array $context = []): void
    {
        self::store([
            'source'  => $source,
            'level'   => 'error',
            'type'    => get_class($e),
            'message' => $e->getMessage() ?: class_basename($e),
            'file'    => self::relativePath($e->getFile()),
            'line'    => $e->getLine(),
            'trace'   => Str::limit($e->getTraceAsString(), 20000),
        ], $context);
    }

    public static function http(int $status, string $message, array $context = []): void
    {
        self::store([
            'source'  => 'http',
            'level'   => 'warning',
            'type'    => 'HTTP ' . $status,
            'message' => $message,
        ], $context, groupByUrl: true);
    }

    public static function browser(array $report): void
    {
        self::store([
            'source'  => 'browser',
            'level'   => ($report['level'] ?? 'error') === 'warning' ? 'warning' : 'error',
            'type'    => Str::limit((string) ($report['type'] ?? 'JavaScript error'), 100, ''),
            'message' => Str::limit((string) ($report['message'] ?? 'Unknown browser error'), 2000),
            'file'    => Str::limit((string) ($report['source'] ?? ''), 250, '') ?: null,
            'line'    => isset($report['line']) ? (int) $report['line'] : null,
            'trace'   => isset($report['stack']) ? Str::limit((string) $report['stack'], 8000) : null,
            'url'     => Str::limit((string) ($report['page'] ?? ''), 2000, '') ?: null,
        ], array_filter(['details' => isset($report['details']) ? self::shorten((array) $report['details']) : null]), groupByUrl: false, includeRequest: false);
    }

    private static function store(array $data, array $context = [], bool $groupByUrl = false, bool $includeRequest = true): void
    {
        if (self::$recording || ! config('errorlog.enabled')) {
            return;
        }

        self::$recording = true;

        try {
            $request = self::isHttp() ? request() : null;

            if ($includeRequest && $request) {
                $data['url'] ??= Str::limit($request->fullUrl(), 2000, '');
                $data['method'] = $request->method();
                $input = Arr::except($request->except(config('errorlog.hidden_fields')), ['files']);
                if ($input) {
                    $context['input'] = self::shorten($input);
                }
            }

            $data += [
                'user_id'    => auth()->id(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
                'context'    => $context ?: null,
            ];

            $fingerprint = hash('sha256', implode('|', [
                $data['source'], $data['type'], Str::limit($data['message'], 500, ''), $data['file'] ?? '', $data['line'] ?? '',
                $groupByUrl ? parse_url((string) ($data['url'] ?? ''), PHP_URL_PATH) : '',
            ]));

            $now = now();
            $existing = ErrorLog::where('fingerprint', $fingerprint)->first();

            if ($existing) {
                $wasResolved = $existing->resolved_at !== null;

                // Same error again: bump the counter, keep the latest details, reopen if it was resolved
                $existing->forceFill(Arr::only($data, ['url', 'method', 'context', 'user_id', 'ip_address', 'user_agent', 'trace']) + [
                    'occurrences'  => $existing->occurrences + 1,
                    'last_seen_at' => $now,
                    'resolved_at'  => null,
                ])->saveQuietly();

                if ($wasResolved) {
                    self::maybeAlert($existing, reopened: true);
                }
            } else {
                $log = ErrorLog::forceCreate($data + [
                    'fingerprint'   => $fingerprint,
                    'occurrences'   => 1,
                    'first_seen_at' => $now,
                    'last_seen_at'  => $now,
                ]);

                self::maybeAlert($log);
            }
        } catch (Throwable) {
            // e.g. database down – the normal laravel.log still has the error
        } finally {
            self::$recording = false;
        }
    }

    // Email the owner about new (or returning) real errors – limited per hour, never about its own failures
    private static function maybeAlert(ErrorLog $log, bool $reopened = false): void
    {
        $to = config('errorlog.alert_email') ?: config('orders.admin_email');

        if (! $to || ! config('errorlog.alerts_enabled') || $log->level !== 'error'
            || ! in_array($log->source, config('errorlog.alert_sources', []), true)
            || str_contains((string) ($log->context['job'] ?? ''), 'ErrorAlert')) {
            return;
        }

        if (RateLimiter::tooManyAttempts('error-alert-emails', (int) config('errorlog.alerts_per_hour', 10))) {
            return;
        }

        RateLimiter::hit('error-alert-emails', 3600);
        Mail::to($to)->queue(new ErrorAlert($log, $reopened));
    }

    // A real web request (the test runner also counts as "console", so check both)
    public static function isHttp(): bool
    {
        return ! app()->runningInConsole() || app()->runningUnitTests();
    }

    private static function shorten(array $input): array
    {
        return collect($input)->map(function ($value) {
            if (is_array($value)) {
                return self::shorten($value);
            }

            return is_string($value) ? Str::limit($value, 300) : $value;
        })->take(50)->all();
    }

    private static function relativePath(string $path): string
    {
        return Str::after(str_replace('\\', '/', $path), str_replace('\\', '/', base_path()) . '/');
    }
}
