<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1"
          name="viewport">

    <!-- CSRF Token -->
    <meta content="{{ csrf_token() }}"
          name="csrf-token">
    <meta content="{{ url('/api') }}"
          name="api-base-url" />

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link crossorigin="anonymous"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
          integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
          referrerpolicy="no-referrer"
          rel="stylesheet" />

    <script>
        window.LaravelApp = @json($scriptVariables);
    </script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body data-bs-theme="auto">
    <div class="wrapper"
         id="app">
        <router-view></router-view>
    </div>
</body>

</html>
