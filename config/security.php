<?php

return [

    /*
    | Content-Security-Policy: tells browsers which sources the site may load scripts, styles, frames … from.
    | The admin panel (Livewire/Alpine) needs inline scripts + eval, so the policy focuses on blocking
    | foreign script/style hosts, framing by other sites, forms posting elsewhere, <base> and plugins.
    | Violations are reported to Admin → Settings → Error log.
    */
    'csp_enabled'     => env('CSP_ENABLED', true),
    'csp_report_only' => env('CSP_REPORT_ONLY', false), // true = only report violations, block nothing

    // Extra hosts allowed for scripts (rarely needed – analytics hosts are added automatically when configured)
    'csp_extra_script_hosts' => array_filter(explode(' ', (string) env('CSP_EXTRA_SCRIPT_HOSTS', ''))),
];
