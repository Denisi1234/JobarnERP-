<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title ?? 'FrontDesk OS — JOBARN' }} — JOBARN ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="/js/alpine.min.js"></script>
    @stack('head')
</head>
<body class="govuk-frontend-supported" style="margin:0; background:#f4f4f4; font-family:'IBM Plex Sans','Inter',sans-serif;">
    <x-reception.sidebar />

    <div class="pl-64" style="padding-left:16rem;">
        <main id="main-content" class="w-full min-h-screen px-6 py-6" style="background:#f4f4f4;">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <footer role="contentinfo" style="margin-left:16rem; border-top:1px solid #e0e0e0; background:#ffffff; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#525252; font-family:'IBM Plex Sans',sans-serif;">
        <span>© {{ date('Y') }} JOBARN — Production</span>
        <span style="display:flex; gap:1rem;"><a href="#" style="color:#0f62fe; text-decoration:none;">Privacy</a><a href="#" style="color:#0f62fe; text-decoration:none;">Support</a></span>
    </footer>
            <script src="/js/live-handoff.js"></script>
    @stack('scripts')
</body>
</html>
