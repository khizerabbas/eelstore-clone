<?php

namespace App\Http\Controllers;

use App\Support\ErrorRecorder;
use Illuminate\Http\Request;

// Receives error reports from visitors' browsers (resources/js/app.js) → Error log
class ClientErrorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'    => ['nullable', 'string', 'max:100'],
            'level'   => ['nullable', 'in:error,warning'],
            'message' => ['required', 'string', 'max:2000'],
            'source'  => ['nullable', 'string', 'max:500'],
            'line'    => ['nullable', 'integer', 'min:0'],
            'stack'   => ['nullable', 'string', 'max:8000'],
            'page'    => ['nullable', 'string', 'max:2000'],
            'details' => ['nullable', 'array', 'max:20'],
        ]);

        // Browser noise we can't act on (extensions, cross-origin scripts)
        if (preg_match('/^(Script error\.?|ResizeObserver loop)/i', $data['message'])) {
            return response()->noContent();
        }

        ErrorRecorder::browser($data);

        return response()->noContent();
    }
}
