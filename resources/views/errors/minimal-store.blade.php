{{-- Server-side failures (500, 503): plain HTML with inline styles – no database, settings or built assets,
     because those may be exactly what is broken --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $heading }} – {{ config('app.name') }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px;
             font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:#f8fafc;color:#0f172a}
        main{max-width:30rem;text-align:center}
        .code{font-size:3.5rem;font-weight:800;color:#94a3b8;margin:0}
        h1{font-size:1.5rem;margin:.5rem 0}
        p{color:#475569;line-height:1.6;margin:.75rem 0}
        a{display:inline-block;margin-top:1rem;padding:.65rem 1.25rem;border-radius:.375rem;background:#0f172a;color:#fff;
          text-decoration:none;font-weight:600;font-size:.9rem}
    </style>
</head>
<body>
<main>
    <p class="code">{{ $code }}</p>
    <h1>{{ $heading }}</h1>
    <p>{{ $message }}</p>
    <a href="{{ url('/') }}">Back to the home page</a>
</main>
</body>
</html>
