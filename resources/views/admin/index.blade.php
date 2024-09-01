@extends('layouts.admin')
@section('main_content')
    <div class="container">
        <div class="row">
            <div class="col-lg-7 col-xl-8">
                <div class="card text-light bg-dark shadow mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background: var(--bs-gray-900);">
                        <h6 class="text-white fw-bold m-0">Мониторинг трафика</h6>
                        <div class="dropdown no-arrow"><button class="btn btn-link btn-sm dropdown-toggle" aria-expanded="false" data-bs-toggle="dropdown" type="button"><i class="fas fa-ellipsis-v text-gray-400"></i></button>
                            <div class="dropdown-menu shadow dropdown-menu-end animated--fade-in">
                                <p class="text-center dropdown-header">TMD Options</p><a class="dropdown-item" href="#">Обновить</a><a class="dropdown-item" href="#">Детализация</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item" href="#" style="color: rgb(255,15,0);">Остановить сбор статистики</a>
                                <div class="dropdown-divider"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="filter: blur(13px);"><canvas height="320" style="display: block; width: 572px; height: 320px;" width="572"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 col-xl-4">
                <div class="card shadow mb-4" style="background: #292929;">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background: #292929;">
                        <h6 class="text-light fw-bold m-0">Источники трафика</h6>
                        <div class="dropdown no-arrow"><button class="btn btn-link btn-sm dropdown-toggle" aria-expanded="false" data-bs-toggle="dropdown" type="button"><i class="fas fa-ellipsis-v text-gray-400"></i></button>
                            <div class="dropdown-menu shadow dropdown-menu-end animated--fade-in">
                                <p class="text-center dropdown-header">dropdown header:</p><a class="dropdown-item" href="#"> Action</a><a class="dropdown-item" href="#"> Another action</a>
                                <div class="dropdown-divider"></div><a class="dropdown-item" href="#"> Something else here</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="filter: blur(0px);"><canvas style="display: block;" width="257" height="320"></canvas></div>
                        <div class="text-center small mt-4" style="filter: blur(14px);"><span class="me-2"><i class="fas fa-circle text-primary"></i>  playnflate.com</span><span class="me-2"><i class="fas fa-circle text-success"></i> Социальные сети</span><span class="me-2"><i class="fas fa-circle text-info"></i> Другие источники</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
