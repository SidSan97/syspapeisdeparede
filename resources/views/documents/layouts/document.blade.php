<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        @include('documents.styles.reset') @include('documents.styles.document') @stack('styles')
    </style>
</head>

<body>

    <div class="document"
         style="max-width:800px">

        <main>
            @yield('content')
        </main>

    </div>

</body>

</html>
