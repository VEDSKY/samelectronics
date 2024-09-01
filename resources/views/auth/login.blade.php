{{--
<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
--}}

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>samelectronics : acp</title>


    <meta name="robots" content="noindex, nofollow">

    <!-- Stylesheets -->
    <!-- Fonts and OneUI framework -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" id="css-main" href="{{ asset('assets/css/oneui.min.css') }}">

    <!-- You can include a specific file from css/themes/ folder to alter the default color theme of the template. eg: -->
    <!-- <link rel="stylesheet" id="css-theme" href="assets/css/themes/amethyst.min.css"> -->
    <!-- END Stylesheets -->
</head>
<body>
<!-- Page Container -->
<!--
    Available classes for #page-container:

GENERIC

  'remember-theme'                            Remembers active color theme and dark mode between pages using localStorage when set through
                                              - Theme helper buttons [data-toggle="theme"],
                                              - Layout helper buttons [data-toggle="layout" data-action="dark_mode_[on/off/toggle]"]
                                              - ..and/or One.layout('dark_mode_[on/off/toggle]')

SIDEBAR & SIDE OVERLAY

  'sidebar-r'                                 Right Sidebar and left Side Overlay (default is left Sidebar and right Side Overlay)
  'sidebar-mini'                              Mini hoverable Sidebar (screen width > 991px)
  'sidebar-o'                                 Visible Sidebar by default (screen width > 991px)
  'sidebar-o-xs'                              Visible Sidebar by default (screen width < 992px)
  'sidebar-dark'                              Dark themed sidebar

  'side-overlay-hover'                        Hoverable Side Overlay (screen width > 991px)
  'side-overlay-o'                            Visible Side Overlay by default

  'enable-page-overlay'                       Enables a visible clickable Page Overlay (closes Side Overlay on click) when Side Overlay opens

  'side-scroll'                               Enables custom scrolling on Sidebar and Side Overlay instead of native scrolling (screen width > 991px)

HEADER

  ''                                          Static Header if no class is added
  'page-header-fixed'                         Fixed Header

HEADER STYLE

  ''                                          Light themed Header
  'page-header-dark'                          Dark themed Header

MAIN CONTENT LAYOUT

  ''                                          Full width Main Content if no class is added
  'main-content-boxed'                        Full width Main Content with a specific maximum width (screen width > 1200px)
  'main-content-narrow'                       Full width Main Content with a percentage width (screen width > 1200px)

DARK MODE

  'sidebar-dark page-header-dark dark-mode'   Enable dark mode (light sidebar/header is not supported with dark mode)
-->
<div id="page-container">
    <!-- Main Container -->
    <main id="main-container">
        <!-- Page Content -->
        <div class="bg-image" style="background-image: url('{{ asset('assets/media/photos/photo1.jpg')}}');">
            <div class="row g-0 bg-primary-dark-op">
                <!-- Meta Info Section -->
                <div class="hero-static col-lg-4 d-none d-lg-flex flex-column justify-content-center">
                    <div class="p-4 p-xl-5 flex-grow-1 d-flex align-items-center">
                        <div class="w-100">
                            <a class="link-fx fw-semibold fs-2 text-white" href="index.html">
                                samelectronics<span class="fw-normal"> : acp</span>
                            </a>
                            <p class="text-white-75 me-xl-8 mt-2">
                                Welcome to samelectronics admin control panel.
                            </p>
                        </div>
                    </div>
                    <div class="p-4 p-xl-5 d-xl-flex justify-content-between align-items-center fs-sm">
                        <p class="fw-medium text-white-50 mb-0">
                            <strong>vSpaceUI</strong> &copy; <span data-toggle="year-copy"></span>
                        </p>
                    </div>
                </div>
                <!-- END Meta Info Section -->

                <!-- Main Section -->
                <div class="hero-static col-lg-8 d-flex flex-column align-items-center bg-body-light">
                    <div class="p-3 w-100 d-lg-none text-center">
                        <a class="link-fx fw-semibold fs-3 text-dark" href="">
                            samelectronics
                        </a>
                    </div>
                    <div class="p-4 w-100 flex-grow-1 d-flex align-items-center">
                        <div class="w-100">
                            <!-- Header -->
                            <div class="text-center mb-5">
                                <p class="mb-3">
                                    <i class="fa fa-2x fa-user-circle text-primary-light"></i>
                                </p>
                                <h1 class="fw-bold mb-2">
                                    login
                                </h1>
                                <p class="fw-medium text-muted">
                                    Please login to your account.
                                </p>
                            </div>
                            <!-- END Header -->

                            <!-- Sign In Form -->
                            <!-- jQuery Validation (.js-validation-signin class is initialized in js/pages/op_auth_signin.min.js which was auto compiled from _js/pages/op_auth_signin.js) -->
                            <!-- For more info and examples you can check out https://github.com/jzaefferer/jquery-validation -->
                            <div class="row g-0 justify-content-center">
                                <div class="col-sm-8 col-xl-4">
                                    <form class="js-validation-signin" action="{{ route('login') }}" method="POST">
                                        @csrf
                                        <div class="mb-4">
                                            <input type="text" class="form-control form-control-lg form-control-alt py-3" id="login-username" name="email" value="{{ old('email') }}" placeholder="Username">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="mb-4">
                                            <input type="password" class="form-control form-control-lg form-control-alt py-3" id="login-password" name="password" value="{{ old('password') }}" placeholder="Password">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" value="" id="remember-me" name="remember_me">
                                            <label class="form-check-label" for="remember-me">Запомнить меня</label>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <a class="text-muted fs-sm fw-medium d-block d-lg-inline-block mb-1" href="{{ route('password.request') }}">
                                                    Forgot Password?
                                                </a>
                                            </div>
                                            <div>
                                                <button type="submit" class="btn btn-lg btn-alt-primary">
                                                    <i class="fa fa-fw fa-sign-in-alt me-1 opacity-50"></i> Sign In
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- END Sign In Form -->
                        </div>
                    </div>
                    <div class="px-4 py-3 w-100 d-lg-none d-flex flex-column flex-sm-row justify-content-between fs-sm text-center text-sm-start">
                        <p class="fw-medium text-black-50 py-2 mb-0">
                            <strong>vSpaceUI</strong> &copy; <span data-toggle="year-copy"></span>
                        </p>
                    </div>
                </div>
                <!-- END Main Section -->
            </div>
        </div>
        <!-- END Page Content -->
    </main>
    <!-- END Main Container -->
</div>
<!-- END Page Container -->

<!--
    OneUI JS

    Core libraries and functionality
    webpack is putting everything together at assets/_js/main/app.js
-->
<script src="{{ asset('assets/js/oneui.app.min.js') }}"></script>

<!-- jQuery (required for jQuery Validation plugin) -->
<script src="{{ asset('assets/js/lib/jquery.min.js') }}"></script>

<!-- Page JS Plugins -->
<script src="{{ asset('assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>

<!-- Page JS Code -->
<script src="{{ asset('assets/js/pages/op_auth_signin.min.js') }}"></script>
</body>
</html>
