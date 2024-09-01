@extends('layouts.app')

@section('main_content')
    <div
        class="d-flex d-sm-flex d-md-flex d-lg-flex d-xl-flex d-xxl-flex flex-column justify-content-center align-items-center flex-sm-column flex-md-column flex-lg-column flex-xl-column justify-content-xl-center align-items-xl-center flex-xxl-column"
        style="background: rgb(21,21,21); min-height: 100vh;">
        <h1 class="display-6" style="font-size: 100px;">😢</h1>
        <h1 style="color: rgb(211,211,211);margin-right: 10px;margin-left: 10px;">прости, друг</h1>
        <p class="text-muted" style="margin-right: 5px;margin-left: 5px;text-align: center;">мы пока не готовы показать тебе эту
            страницу, но {{ $continued_message }}</p>
        <div
            class="d-flex d-sm-flex d-md-flex d-lg-flex d-xl-flex d-xxl-flex flex-column justify-content-center align-items-center flex-sm-column flex-md-row flex-lg-row flex-xl-row justify-content-xl-center align-items-xl-center flex-xxl-row"
            style="width: 101%;">
            <a
                class="btn btn-outline-light btn-lg d-xxl-flex justify-content-xxl-center align-items-xxl-center" role="button"
                target="_self"
                href="{{ url()->previous() }}"
                style="border-radius: 20px;font-size: 10px;margin: 3px;width: 100%;text-align: center;max-width: 150px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em"
                     fill="currentColor">
                    <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                    <path
                        d="M9.375 233.4l128-128c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25L109.3 224H480c17.69 0 32 14.31 32 32s-14.31 32-32 32H109.3l73.38 73.38c12.5 12.5 12.5 32.75 0 45.25c-12.49 12.49-32.74 12.51-45.25 0l-128-128C-3.125 266.1-3.125 245.9 9.375 233.4z"></path>
                </svg>
                 вернуться назад</a>
            <a
                class="btn btn-outline-light btn-lg d-xxl-flex justify-content-xxl-center align-items-xxl-center" role="button"
                target="_self"
                href="{{ route('homepage') }}"
                style="border-radius: 20px;font-size: 10px;margin: 3px;width: 100%;text-align: center;max-width: 150px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -32 576 576" width="1em" height="1em"
                     fill="currentColor">
                    <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                    <path
                        d="M511.8 287.6L512.5 447.7C512.5 450.5 512.3 453.1 512 455.8V472C512 494.1 494.1 512 472 512H456C454.9 512 453.8 511.1 452.7 511.9C451.3 511.1 449.9 512 448.5 512H392C369.9 512 352 494.1 352 472V384C352 366.3 337.7 352 320 352H256C238.3 352 224 366.3 224 384V472C224 494.1 206.1 512 184 512H128.1C126.6 512 125.1 511.9 123.6 511.8C122.4 511.9 121.2 512 120 512H104C81.91 512 64 494.1 64 472V360C64 359.1 64.03 358.1 64.09 357.2V287.6H32.05C14.02 287.6 0 273.5 0 255.5C0 246.5 3.004 238.5 10.01 231.5L266.4 8.016C273.4 1.002 281.4 0 288.4 0C295.4 0 303.4 2.004 309.5 7.014L416 100.7V64C416 46.33 430.3 32 448 32H480C497.7 32 512 46.33 512 64V185L564.8 231.5C572.8 238.5 576.9 246.5 575.8 255.5C575.8 273.5 560.8 287.6 543.8 287.6L511.8 287.6z"></path>
                </svg>
                 на главную</a>
            <a
                class="btn btn-outline-light btn-lg d-xxl-flex justify-content-xxl-center align-items-xxl-center" role="button"
                target="_blank"
                href="{{ $action_button_url }}"
                style="border-radius: 20px;font-size: 10px;margin: 3px;width: 100%;text-align: center;max-width: 150px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em"
                     fill="currentColor">
                    <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                    <path
                        d="M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM51.68 295.1L83.41 301.5C91.27 303.1 99.41 300.6 105.1 294.9L120.5 279.5C132 267.1 151.6 271.1 158.9 285.8L168.2 304.3C172.1 313.9 182.8 319.1 193.5 319.1C208.7 319.1 219.6 305.4 215.2 290.8L209.3 270.9C204.6 255.5 216.2 240 232.3 240H234.6C247.1 240 260.5 233.3 267.9 222.2L278.6 206.1C284.2 197.7 283.9 186.6 277.8 178.4L261.7 156.9C251.4 143.2 258.4 123.4 275.1 119.2L292.1 114.1C299.6 113.1 305.7 107.8 308.6 100.6L324.9 59.69C303.4 52.12 280.2 48 255.1 48C141.1 48 47.1 141.1 47.1 256C47.1 269.4 49.26 282.5 51.68 295.1L51.68 295.1zM450.4 300.4L434.6 304.9C427.9 306.7 420.8 304 417.1 298.2L415.1 295.1C409.1 285.7 398.7 279.1 387.5 279.1C376.4 279.1 365.1 285.7 359.9 295.1L353.8 304.6C352.4 306.8 350.5 308.7 348.2 309.1L311.1 330.1C293.9 340.2 286.5 362.5 294.1 381.4L300.5 393.8C309.1 413 331.2 422.3 350.1 414.9L353.5 413.1C363.6 410.2 374.8 411.8 383.5 418.1L385 419.2C422.2 389.7 449.1 347.8 459.4 299.7C456.4 299.4 453.4 299.6 450.4 300.4H450.4zM156.1 367.5L188.1 375.5C196.7 377.7 205.4 372.5 207.5 363.9C209.7 355.3 204.5 346.6 195.9 344.5L163.9 336.5C155.3 334.3 146.6 339.5 144.5 348.1C142.3 356.7 147.5 365.4 156.1 367.5V367.5zM236.5 328.1C234.3 336.7 239.5 345.4 248.1 347.5C256.7 349.7 265.4 344.5 267.5 335.9L275.5 303.9C277.7 295.3 272.5 286.6 263.9 284.5C255.3 282.3 246.6 287.5 244.5 296.1L236.5 328.1zM321.7 120.8L305.7 152.8C301.7 160.7 304.9 170.4 312.8 174.3C320.7 178.3 330.4 175.1 334.3 167.2L350.3 135.2C354.3 127.3 351.1 117.6 343.2 113.7C335.3 109.7 325.6 112.9 321.7 120.8V120.8z"></path>
                </svg>
                 {{ $action_button_text }}</a>
        </div>
    </div>
@endsection
