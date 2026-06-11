<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>Multi-Currency Payments</title>
        <link rel="icon" type="image/jpeg" href="/logo.jpg" />
        <link rel="apple-touch-icon" href="/logo.jpg" />
        @viteReactRefresh
        @vite('resources/js/main.tsx')
    </head>
    <body>
        <div id="app" data-props='@json(["name" => auth()->user()?->name])'></div>
    </body>
</html>
