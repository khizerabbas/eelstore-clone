<?php

namespace App\Http\Controllers;

use App\Support\ErrorRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Receives Content-Security-Policy violation reports from browsers → Error log (warning)
class CspReportController extends Controller
{
    public function store(Request $request)
    {
        $payload = json_decode((string) $request->getContent(), true);

        // Old format: {"csp-report": {...}} – Reporting API: [{"type": "csp-violation", "body": {...}}]
        $reports = isset($payload['csp-report'])
            ? [$payload['csp-report']]
            : collect(is_array($payload) ? $payload : [])->where('type', 'csp-violation')->pluck('body')->all();

        foreach (array_slice($reports, 0, 5) as $report) {
            if (! is_array($report)) {
                continue;
            }

            $blocked   = (string) ($report['blocked-uri'] ?? $report['blockedURL'] ?? '');
            $directive = (string) ($report['effective-directive'] ?? $report['violated-directive'] ?? $report['effectiveDirective'] ?? '');

            // Browser extensions inject their own code – not something the site can fix
            if (preg_match('/^(chrome|moz|safari|ms-browser)-extension|^about:/i', $blocked)) {
                continue;
            }

            ErrorRecorder::browser([
                'type'    => 'CSP violation',
                'level'   => 'warning',
                'message' => Str::limit("Blocked {$blocked} ({$directive})", 500),
                'page'    => Str::limit((string) ($report['document-uri'] ?? $report['documentURL'] ?? ''), 2000, ''),
                'source'  => (string) ($report['source-file'] ?? $report['sourceFile'] ?? ''),
                'line'    => $report['line-number'] ?? $report['lineNumber'] ?? null,
            ]);
        }

        return response()->noContent();
    }
}
