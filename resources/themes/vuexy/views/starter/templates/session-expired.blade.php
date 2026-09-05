<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $loginUrl }}">
    <title>Sesi Berakhir | {{ config('app.name') }}</title>
</head>
<body>
    <script>
        window.location.replace(@json($loginUrl));
    </script>
</body>
</html>
