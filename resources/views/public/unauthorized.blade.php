<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Unauthorized</title>@include('partials.favicon')@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-gray-50 text-gray-900"><main class="flex min-h-screen items-center justify-center p-6"><div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-xl text-red-600">!</div><h1 class="mt-4 text-xl font-bold">Access denied</h1><p class="mt-2 text-sm text-gray-500">You do not have permission to view this page.</p><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mt-6 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Continue</a></div></main></body>
</html>
