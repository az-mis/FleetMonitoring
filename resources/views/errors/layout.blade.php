<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Fleet Management System</title>
    @include('partials.favicon')
    {{-- Self-contained on purpose: error pages must render even when assets or the app are failing --}}
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; background: #0a351b; background-image: radial-gradient(circle at top, #14532d 0%, #0a351b 60%); font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #0f172a; }
        main { width: 100%; max-width: 440px; background: #ffffff; border-radius: 20px; padding: 32px 28px; text-align: center; box-shadow: 0 20px 40px rgba(0, 0, 0, .25); }
        .code { display: inline-block; margin: 0 0 12px; padding: 4px 12px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 12px; font-weight: 700; letter-spacing: .05em; }
        h1 { margin: 0 0 8px; font-size: 22px; line-height: 1.3; }
        p { margin: 0; font-size: 14px; line-height: 1.6; color: #475569; }
        .detail { margin-top: 12px; padding: 10px 12px; border-radius: 12px; background: #fffbeb; color: #92400e; font-size: 13px; }
        .actions { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 8px 18px; border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; font-family: inherit; }
        .btn:hover { background: #f8fafc; }
        .btn-primary { border-color: #047857; background: #047857; color: #ffffff; }
        .btn-primary:hover { background: #065f46; }
        .btn:focus-visible { outline: 3px solid #34d399; outline-offset: 2px; }
        .foot { margin-top: 24px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <main>
        <p class="code">Error @yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        @hasSection('detail')
            <p class="detail" role="note">@yield('detail')</p>
        @endif
        <div class="actions">
            {{-- The dashboard sends signed-out visitors to the sign-in page, so this works for everyone --}}
            <a class="btn btn-primary" href="{{ url('/dashboard') }}">Go to Dashboard</a>
            <button type="button" class="btn" onclick="history.back()">Go Back</button>
        </div>
        <p class="foot">DA-MIMAROPA Fleet Management System</p>
    </main>
</body>
</html>
