@extends('layouts.app')

@section('main_content')
    <!-- Start: 1 Row 2 Columns -->
    <div class="container">
        <div class="row d-xxl-flex justify-content-xxl-center align-items-xxl-center" style="min-height: 100vh;margin-top: 10px;">
            <div class="col-md-6">
                <h1 class="d-xxl-flex align-items-xxl-center" style="font-family: 'Noto Sans Cherokee', sans-serif;">{{ __('headings.about_us') }}</h1>
                <p style="font-size: 20px;">{{ __('project.about_main') }}<br></p>
                <p style="font-size: 20px;">{{ __('project.key_aspect') }}</p>
            </div>
            <div class="col-md-6" style="min-height: 500px;">
                <!-- Start: Simple Slider -->
                <div class="simple-slider">
                    <!-- Start: Slideshow -->
                    <div class="swiper-container" style="border-radius: 10px;">
                        <!-- Start: Slide Wrapper -->
                        <div class="swiper-wrapper">
                            <!-- Start: Slide -->
                            <div class="swiper-slide" style="background: url(&quot;assets/img/iwlwtst1.jpg&quot;) center / cover no-repeat;"></div><!-- End: Slide -->
                            <!-- Start: Slide -->
                            <div class="swiper-slide" style="background: url(&quot;assets/img/kantele_experiments.jpg&quot;) center / cover no-repeat;"></div><!-- End: Slide -->
                            <!-- Start: Slide -->
                            <div class="swiper-slide" style="background: url(&quot;assets/img/iwlwtst3.jpg&quot;) center / cover no-repeat;"></div><!-- End: Slide -->
                        </div><!-- End: Slide Wrapper -->
                        <!-- Start: Previous -->
                        <div class="text-white swiper-button-prev" style="border-right-color: rgb(201,201,201);width: 30px;"></div><!-- End: Previous -->
                        <!-- Start: Next -->
                        <div class="text-white swiper-button-next" style="width: 30px;"></div><!-- End: Next -->
                    </div><!-- End: Slideshow -->
                </div><!-- End: Simple Slider -->
            </div>
        </div>
    </div>
    <!-- End: 1 Row 2 Columns -->
@endsection
