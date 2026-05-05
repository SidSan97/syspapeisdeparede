<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1"
          name="viewport">
    <title>Catálogo — {{ $appName }}</title>

    <!-- Bootstrap 5 -->
    <link crossorigin="anonymous"
          href="https://cdn.jsdelivr.net/npm/fastbootstrap@2.2.0/dist/css/fastbootstrap.min.css"
          integrity="sha256-V6lu+OdYNKTKTsVFBuQsyIlDiRWiOmtC8VQ8Lzdm2i4="
          rel="stylesheet">

    <!-- Font Awesome -->
    <link crossorigin="anonymous"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
          integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
          referrerpolicy="no-referrer"
          rel="stylesheet" />

    <script>
        window.CatalogConfig = {
            appName: @json($appName),
            apiBaseUrl: @json($apiBaseUrl),
        };
    </script>

    @vite(['resources/js/catalog.js'])
</head>

<body>
    <div id="catalog-app"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
