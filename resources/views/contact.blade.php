@extends('layouts.app')

@section('main_content')
    <section class="position-relative py-4 py-xl-5 d-xxl-flex justify-content-xxl-center align-items-xxl-center" style="min-height: 100vh; height: auto;">
        <div class="container position-relative">
            <div class="row mb-5">
                <div class="col-md-8 col-xl-6 text-center mx-auto" style="color: rgb(255,255,255);">
                    <h2 style="font-family: 'Noto Sans Carian', sans-serif;">{{ __('nav.contacts') }}</h2>
                    <p class="w-lg-50" style="font-family: 'Noto Sans Carian', sans-serif;">we are always open for dialogue. if you want to learn more about our project or discuss potential collaboration ideas, feel free to contact us.</p>
                </div>
            </div>
            <div class="row d-flex justify-content-center">
                <div class="col-md-6 col-lg-4 col-xl-4">
                    <div class="d-flex flex-column justify-content-center align-items-start h-100" style="color: rgb(255,255,255);">
                        <div class="d-flex align-items-center p-3">
                            <div class="bs-icon-md bs-icon-rounded bs-icon-primary d-flex flex-shrink-0 justify-content-center align-items-center d-inline-block bs-icon" style="background: rgb(31,31,31);"><i class="fab fa-telegram-plane"></i></div>
                            <div class="px-2">
                                <h6 class="mb-0">telegram</h6>
                                <p class="text-light mb-0"><a href="https://t.me/samelectronics1"><span style="color: rgb(146, 146, 146);">@samelectronics1</span></a></p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center p-3">
                            <div class="bs-icon-md bs-icon-rounded bs-icon-primary d-flex flex-shrink-0 justify-content-center align-items-center d-inline-block bs-icon" style="background: rgb(31,31,31);"><svg class="bi bi-envelope" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"></path>
                                </svg></div>
                            <div class="px-2">
                                <h6 class="mb-0">email</h6>
                                <p class="mb-0"><a href="mailto:weltfuturisten@gmail.com" target="_blank"><span style="color: rgb(146, 146, 146);">weltfuturisten@gmail.com</span></a></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div>
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert"><span><strong>
                                <ul>
                                @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                @endforeach
                                </ul>
                            </strong></span></div>
                        @endif
                        <form class="p-3 p-xl-4" method="POST" action="{{ route('contact-us') }}">
                            @csrf
                            <div class="mb-3"><input id="name" class="form-control" type="text" name="name" placeholder="Name" style="background: rgb(27,27,27);border-style: solid;border-color: rgb(48,48,48);color: rgb(255,255,255);" /></div>
                            <div class="mb-3"><input id="reply_mail" class="form-control" type="email" name="reply_mail" placeholder="Email" style="background: rgb(27,27,27);border-style: solid;border-color: rgb(48,48,48);color: rgb(255,255,255);" /></div>
                            <div class="mb-3"><input id="subject" class="form-control" type="text" name="subject" placeholder="Subject" style="background: rgb(27,27,27);border-style: solid;border-color: rgb(48,48,48);color: rgb(255,255,255);" /></div>
                            <div class="mb-3"><textarea id="message" class="form-control" name="message" rows="6" placeholder="Message" style="background: rgb(27,27,27);border-style: solid;border-color: rgb(48,48,48);color: rgb(255,255,255);" spellcheck="true" wrap="soft"></textarea></div>
                            <div><button class="btn btn-outline-secondary d-block w-100" type="submit" style="border-color: rgb(42,42,42);">Send </button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
