<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bienvenido a Medius</title>
</head>
<body>
    <p style="white-space: pre-line">
        Hola {{ $user->name }}!

        Nos complace saludarte y darte la bienvenida al sistema Medius.

        Por favor, para confirmar tu cuenta te pedimos ingreses al siguiente link:

        {{ config('services.url_front') }}/confirmacion-cuenta/{{ Crypt::encrypt($user->email) }}

        Ante cualquier duda podes escribirnos a ayuda@medius.com
        Muchas gracias!
    </p>
</body>
</html>