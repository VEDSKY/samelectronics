<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>samelectronics</title>
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ABeeZee.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Aldrich.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Atkinson%20Hyperlegible.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Audiowide.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/B612%20Mono.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Noto%20Sans%20Carian.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Noto%20Sans%20Cherokee.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/RocknRoll%20One.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/iframe-iframe.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Navbar-Centered-Links-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Simple-Slider-Simple-Slider.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/Team-Horizontal-images.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/6.4.8/swiper-bundle.min.css">
    <!-- Pixel Code - https://ci45171.tw1.ru/ -->
    <script defer src="https://ci45171.tw1.ru/pixel/eoi36hWcFf0lu3X3" data-ignore-dnt="true"></script>
    <!-- END Pixel Code -->
</head>
<body
    @if(Route::current()->getName() == 'homepage')
        style="min-height: 100vh;  background: rgb(20,20,20); animation: animate-bg 10s infinite; animation-direction: alternate-reverse;"
    @else
        style="min-height: 100vh; background: rgb(21,21,21);color: rgb(188,188,188);"
    @endif
>
@include('layouts.qnav')
@include('layouts.user_navigation')
@yield('main_content')
@include('layouts.footer')
@include('components.modals')
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/bs-init.js') }}"></script>
<script src="{{ asset('assets/js/Simple-Slider.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/6.4.8/swiper-bundle.min.js"></script>
</body>

</html>
