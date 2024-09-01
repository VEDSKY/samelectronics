@extends('layouts.app')

@section('main_content')
    <div class="container d-xxl-flex justify-content-xxl-center align-items-xxl-center" style="min-height: 100vh;">
        <div class="row g-0 justify-content-center align-items-center"
             style="width: 100%;height: 100%;margin-top: 15px;">
            <div class="col-md-6 text-start d-flex d-md-flex flex-column align-items-xl-start" style="margin-top: 5px;">
                <h1 class="text-truncate fs-1 text-start text-white d-xl-flex typewriter"
                    style="font-family: Audiowide, serif;width: 100%;border-right-width: 1px;border-right-style: none;">{{ config('app.name') }}</h1>
                <p class="lead fs-2 text-start"
                   style="font-size: 30px;font-family: 'Noto Sans Carian', sans-serif;color: var(--bs-gray-600);">{{ __('project.slug') }}</p>
                <div class="card"
                     style="width: 100%;border-radius: 10px;background: rgb(25,25,25);margin: 4px;box-shadow: 0px 0px 20px 1px rgb(38,38,38);margin-top: 10px;border-style: groove;border-color: var(--bs-gray-800);">
                    <div class="card-body d-flex d-xl-flex flex-row justify-content-xl-start align-items-xl-center"
                         style="width: 100%;padding: 0px;"><img
                            style="width: 100px;height: 100px;margin: 4px;padding: 8px;background: #ffffff;border-radius: 5px;"
                            src="assets/img/QR-Code.png">
                        <div class="text-start d-xl-flex flex-column justify-content-xl-center align-items-xl-start"
                             style="width: 100%;">
                            <p class="text-lowercase text-start"
                               style="font-size: 19px;margin-left: 10px;color: rgb(129,129,129);font-family: 'Noto Sans Carian', sans-serif;margin-right: 4px;">
                                несколько фактов о недавно вышедшем shadow business: этот альбом на сегодняшний день
                                является одним из «долгостроев»...<span style="color: rgb(173, 173, 173);">&nbsp;</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="-8 0 512 512" width="1em" height="1em"
                                     fill="currentColor" style="margin-right: 2px;margin-left: 2px;">
                                    <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                                    <path
                                        d="M248,8C111.033,8,0,119.033,0,256S111.033,504,248,504,496,392.967,496,256,384.967,8,248,8ZM362.952,176.66c-3.732,39.215-19.881,134.378-28.1,178.3-3.476,18.584-10.322,24.816-16.948,25.425-14.4,1.326-25.338-9.517-39.287-18.661-21.827-14.308-34.158-23.215-55.346-37.177-24.485-16.135-8.612-25,5.342-39.5,3.652-3.793,67.107-61.51,68.335-66.746.153-.655.3-3.1-1.154-4.384s-3.59-.849-5.135-.5q-3.283.746-104.608,69.142-14.845,10.194-26.894,9.934c-8.855-.191-25.888-5.006-38.551-9.123-15.531-5.048-27.875-7.717-26.8-16.291q.84-6.7,18.45-13.7,108.446-47.248,144.628-62.3c68.872-28.647,83.183-33.623,92.511-33.789,2.052-.034,6.639.474,9.61,2.885a10.452,10.452,0,0,1,3.53,6.716A43.765,43.765,0,0,1,362.952,176.66Z"></path>
                                </svg>
                                <a href="https://t.me/weltfuturisten" target="_blank"><span
                                        style="color: rgb(198, 198, 198);">and more in&nbsp;telegram</span></a><br></p>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <a class="btn btn-outline-light" role="button" target="_blank"
                       style="margin: 3px;font-family: Aldrich, sans-serif;"><i class="fab fa-bandcamp"
                                                                                style="margin: 4px;"></i>Bandcamp</a><a
                        class="btn btn-outline-light" role="button" target="_blank"
                        style="margin: 3px;font-family: Aldrich, sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="-64 0 512 512" width="1em" height="1em"
                             fill="currentColor" style="margin: 4px;">
                            <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                            <path
                                d="M381.9 388.2c-6.4 27.4-27.2 42.8-55.1 48-24.5 4.5-44.9 5.6-64.5-10.2-23.9-20.1-24.2-53.4-2.7-74.4 17-16.2 40.9-19.5 76.8-25.8 6-1.1 11.2-2.5 15.6-7.4 6.4-7.2 4.4-4.1 4.4-163.2 0-11.2-5.5-14.3-17-12.3-8.2 1.4-185.7 34.6-185.7 34.6-10.2 2.2-13.4 5.2-13.4 16.7 0 234.7 1.1 223.9-2.5 239.5-4.2 18.2-15.4 31.9-30.2 39.5-16.8 9.3-47.2 13.4-63.4 10.4-43.2-8.1-58.4-58-29.1-86.6 17-16.2 40.9-19.5 76.8-25.8 6-1.1 11.2-2.5 15.6-7.4 10.1-11.5 1.8-256.6 5.2-270.2.8-5.2 3-9.6 7.1-12.9 4.2-3.5 11.8-5.5 13.4-5.5 204-38.2 228.9-43.1 232.4-43.1 11.5-.8 18.1 6 18.1 17.6.2 344.5 1.1 326-1.8 338.5z"></path>
                        </svg>
                        Apple Music</a><a class="btn btn-outline-light" role="button" target="_blank"
                                          style="margin: 3px;font-family: Aldrich, sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="-8 0 512 512" width="1em" height="1em"
                             fill="currentColor" style="margin: 4px;">
                            <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                            <path
                                d="M248 8C111.1 8 0 119.1 0 256s111.1 248 248 248 248-111.1 248-248S384.9 8 248 8zm100.7 364.9c-4.2 0-6.8-1.3-10.7-3.6-62.4-37.6-135-39.2-206.7-24.5-3.9 1-9 2.6-11.9 2.6-9.7 0-15.8-7.7-15.8-15.8 0-10.3 6.1-15.2 13.6-16.8 81.9-18.1 165.6-16.5 237 26.2 6.1 3.9 9.7 7.4 9.7 16.5s-7.1 15.4-15.2 15.4zm26.9-65.6c-5.2 0-8.7-2.3-12.3-4.2-62.5-37-155.7-51.9-238.6-29.4-4.8 1.3-7.4 2.6-11.9 2.6-10.7 0-19.4-8.7-19.4-19.4s5.2-17.8 15.5-20.7c27.8-7.8 56.2-13.6 97.8-13.6 64.9 0 127.6 16.1 177 45.5 8.1 4.8 11.3 11 11.3 19.7-.1 10.8-8.5 19.5-19.4 19.5zm31-76.2c-5.2 0-8.4-1.3-12.9-3.9-71.2-42.5-198.5-52.7-280.9-29.7-3.6 1-8.1 2.6-12.9 2.6-13.2 0-23.3-10.3-23.3-23.6 0-13.6 8.4-21.3 17.4-23.9 35.2-10.3 74.6-15.2 117.5-15.2 73 0 149.5 15.2 205.4 47.8 7.8 4.5 12.9 10.7 12.9 22.6 0 13.6-11 23.3-23.2 23.3z"></path>
                        </svg>
                        Spotify</a>
                    <a class="btn btn-outline-light" role="button" target="_blank"
                       style="margin: 3px;font-family: Aldrich, sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                             stroke-linejoin="round" class="icon icon-tabler icon-tabler-brand-tidal"
                             style="margin: 4px;">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 7l-2.5 2.5l2.5 2.5l2.5 -2.5z"></path>
                            <path d="M12 14l-2.5 2.5l2.5 2.5l2.5 -2.5z"></path>
                            <path d="M19 7l-2.5 2.5l2.5 2.5l2.5 -2.5z"></path>
                            <path d="M5 7l-2.5 2.5l2.5 2.5l2.5 -2.5z"></path>
                        </svg>
                        Tidal</a><a class="btn btn-outline-light" role="button" target="_blank"
                                    style="margin: 3px;font-family: Aldrich, sans-serif;"><i class="fas fa-play"
                                                                                             style="margin: 4px;"></i>PLAYNFLATE</a><a
                        class="btn btn-outline-light" role="button" target="_blank"
                        style="margin: 3px;font-family: Aldrich, sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em"
                             fill="currentColor" style="margin: 4px;">
                            <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                            <path
                                d="M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM51.68 295.1L83.41 301.5C91.27 303.1 99.41 300.6 105.1 294.9L120.5 279.5C132 267.1 151.6 271.1 158.9 285.8L168.2 304.3C172.1 313.9 182.8 319.1 193.5 319.1C208.7 319.1 219.6 305.4 215.2 290.8L209.3 270.9C204.6 255.5 216.2 240 232.3 240H234.6C247.1 240 260.5 233.3 267.9 222.2L278.6 206.1C284.2 197.7 283.9 186.6 277.8 178.4L261.7 156.9C251.4 143.2 258.4 123.4 275.1 119.2L292.1 114.1C299.6 113.1 305.7 107.8 308.6 100.6L324.9 59.69C303.4 52.12 280.2 48 255.1 48C141.1 48 47.1 141.1 47.1 256C47.1 269.4 49.26 282.5 51.68 295.1L51.68 295.1zM450.4 300.4L434.6 304.9C427.9 306.7 420.8 304 417.1 298.2L415.1 295.1C409.1 285.7 398.7 279.1 387.5 279.1C376.4 279.1 365.1 285.7 359.9 295.1L353.8 304.6C352.4 306.8 350.5 308.7 348.2 309.1L311.1 330.1C293.9 340.2 286.5 362.5 294.1 381.4L300.5 393.8C309.1 413 331.2 422.3 350.1 414.9L353.5 413.1C363.6 410.2 374.8 411.8 383.5 418.1L385 419.2C422.2 389.7 449.1 347.8 459.4 299.7C456.4 299.4 453.4 299.6 450.4 300.4H450.4zM156.1 367.5L188.1 375.5C196.7 377.7 205.4 372.5 207.5 363.9C209.7 355.3 204.5 346.6 195.9 344.5L163.9 336.5C155.3 334.3 146.6 339.5 144.5 348.1C142.3 356.7 147.5 365.4 156.1 367.5V367.5zM236.5 328.1C234.3 336.7 239.5 345.4 248.1 347.5C256.7 349.7 265.4 344.5 267.5 335.9L275.5 303.9C277.7 295.3 272.5 286.6 263.9 284.5C255.3 282.3 246.6 287.5 244.5 296.1L236.5 328.1zM321.7 120.8L305.7 152.8C301.7 160.7 304.9 170.4 312.8 174.3C320.7 178.3 330.4 175.1 334.3 167.2L350.3 135.2C354.3 127.3 351.1 117.6 343.2 113.7C335.3 109.7 325.6 112.9 321.7 120.8V120.8z"></path>
                        </svg>
                        Yandex Music</a><a class="btn btn-outline-light" role="button" target="_blank"
                                           style="margin: 3px;font-family: Aldrich, sans-serif;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em"
                             fill="currentColor" style="margin: 4px;">
                            <!--! Font Awesome Free 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2022 Fonticons, Inc. -->
                            <path
                                d="M512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM51.68 295.1L83.41 301.5C91.27 303.1 99.41 300.6 105.1 294.9L120.5 279.5C132 267.1 151.6 271.1 158.9 285.8L168.2 304.3C172.1 313.9 182.8 319.1 193.5 319.1C208.7 319.1 219.6 305.4 215.2 290.8L209.3 270.9C204.6 255.5 216.2 240 232.3 240H234.6C247.1 240 260.5 233.3 267.9 222.2L278.6 206.1C284.2 197.7 283.9 186.6 277.8 178.4L261.7 156.9C251.4 143.2 258.4 123.4 275.1 119.2L292.1 114.1C299.6 113.1 305.7 107.8 308.6 100.6L324.9 59.69C303.4 52.12 280.2 48 255.1 48C141.1 48 47.1 141.1 47.1 256C47.1 269.4 49.26 282.5 51.68 295.1L51.68 295.1zM450.4 300.4L434.6 304.9C427.9 306.7 420.8 304 417.1 298.2L415.1 295.1C409.1 285.7 398.7 279.1 387.5 279.1C376.4 279.1 365.1 285.7 359.9 295.1L353.8 304.6C352.4 306.8 350.5 308.7 348.2 309.1L311.1 330.1C293.9 340.2 286.5 362.5 294.1 381.4L300.5 393.8C309.1 413 331.2 422.3 350.1 414.9L353.5 413.1C363.6 410.2 374.8 411.8 383.5 418.1L385 419.2C422.2 389.7 449.1 347.8 459.4 299.7C456.4 299.4 453.4 299.6 450.4 300.4H450.4zM156.1 367.5L188.1 375.5C196.7 377.7 205.4 372.5 207.5 363.9C209.7 355.3 204.5 346.6 195.9 344.5L163.9 336.5C155.3 334.3 146.6 339.5 144.5 348.1C142.3 356.7 147.5 365.4 156.1 367.5V367.5zM236.5 328.1C234.3 336.7 239.5 345.4 248.1 347.5C256.7 349.7 265.4 344.5 267.5 335.9L275.5 303.9C277.7 295.3 272.5 286.6 263.9 284.5C255.3 282.3 246.6 287.5 244.5 296.1L236.5 328.1zM321.7 120.8L305.7 152.8C301.7 160.7 304.9 170.4 312.8 174.3C320.7 178.3 330.4 175.1 334.3 167.2L350.3 135.2C354.3 127.3 351.1 117.6 343.2 113.7C335.3 109.7 325.6 112.9 321.7 120.8V120.8z"></path>
                        </svg>
                        A2B2 Radio</a></div>
            </div>
            <div
                class="col-md-6 col-xl-6 col-xxl-6 d-xl-flex d-xxl-flex justify-content-xl-center justify-content-xxl-center"
                style="margin-top: 5px;">
                <div class="d-md-flex justify-content-md-center" style="width: 350px;height: 442px;">
                    <iframe
                        style="border: 0; width: 350px; height: 442px; border-radius: 10px; box-shadow: 0px 0px 20px 1px rgb(38,38,38); background: rgb(20,20,20)"
                        src="https://bandcamp.com/EmbeddedPlayer/track=3015903642/size=large/bgcol=333333/linkcol=ffffff/tracklist=false/transparent=true/"
                        seamless><a href="https://samelectronics.bandcamp.com/track/world-of-dreams">world of dreams by
                            samelectronics</a></iframe>
                </div>
            </div>
        </div>
    </div><!-- End: 1 Row 2 Columns -->
@endsection

