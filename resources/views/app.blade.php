<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Record a video today. We seal it for a year, then deliver it with one question: did you do it? $5. No take-backs.">
        <meta name="theme-color" content="#f4efe6">
        <meta property="og:title" content="Said You Would">
        <meta property="og:description" content="Put five dollars on your word. A sealed video, delivered one year later.">
        <meta property="og:type" content="website">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <title inertia>{{ config('app.name', 'Said You Would') }}</title>
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
