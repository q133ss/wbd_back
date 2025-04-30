@extends('layouts.main')
@section('title','Главная')
@section('content')
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 borr-30 p-2">
                <ul class="nav nav-pills mb-3" id="chartTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-tab="revenue" href="#">Выручка</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-tab="withdraw" href="#">Выводы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-tab="sellers"  href="#">Продавцы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-tab="buyers" href="#">Покупатели</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-tab="buyback_success" href="#">Выкупы успешные</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-tab="buyback_process" href="#">Выкупы процесс</a>
                    </li>
                </ul>
                <div class="tab-content" id="chartContent">
                    <div class="tab" id="revenue" role="tabpanel" aria-labelledby="revenue-tab">
                        <div id="revenueChart">
                            <h3 class="pt-3">Выручка</h3>
                            <div class="chart-container">
                                <canvas id="revenueChartCanvas"></canvas>
                            </div>
                            <div class="w-100 d-flex justify-content-center">
                                <div class="mt-3 d-flex" style="color: #666666; gap: 15px">
                                    <p>Транзакций: <span class="text-white font-weight-bold">580</span></p>
                                    <p>Выручка: <span class="text-white font-weight-bold">36000</span></p>
                                    <p>Ср. чек: <span class="text-white font-weight-bold">3000</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-none tab" id="withdraw" role="tabpanel" aria-labelledby="withdraw-tab">
                        <div id="withdrawChart">
                            <h3 class="pt-3">Выводы</h3>
                            <div class="chart-container">
                                <canvas id="withdrawChartCanvas"></canvas>
                            </div>
                            <div class="w-100 d-flex justify-content-center">
                                <div class="mt-3 d-flex" style="color: #666666; gap: 15px">
                                    <p>Транзакций: <span class="text-white font-weight-bold">580</span></p>
                                    <p>Выручка: <span class="text-white font-weight-bold">36000</span></p>
                                    <p>Ср. чек: <span class="text-white font-weight-bold">3000</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-none tab" id="sellers" role="tabpanel" aria-labelledby="sellers-tab">
                        <div id="sellersChart">
                            <h3 class="pt-3">Продавцы</h3>
                            <div class="chart-container">
                                <canvas id="sellersChartCanvas"></canvas>
                            </div>
                            <div class="w-100 d-flex justify-content-center">
                                <div class="mt-3 d-flex" style="color: #666666; gap: 15px">
                                    <p>Транзакций: <span class="text-white font-weight-bold">580</span></p>
                                    <p>Выручка: <span class="text-white font-weight-bold">36000</span></p>
                                    <p>Ср. чек: <span class="text-white font-weight-bold">3000</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-none tab" id="buyers" role="tabpanel" aria-labelledby="buyers-tab">
                        <div id="buyersChart">
                            <h3 class="pt-3">Покупатели</h3>
                            <div class="chart-container">
                                <canvas id="buyersChartCanvas"></canvas>
                            </div>
                            <div class="w-100 d-flex justify-content-center">
                                <div class="mt-3 d-flex" style="color: #666666; gap: 15px">
                                    <p>Транзакций: <span class="text-white font-weight-bold">580</span></p>
                                    <p>Выручка: <span class="text-white font-weight-bold">36000</span></p>
                                    <p>Ср. чек: <span class="text-white font-weight-bold">3000</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-none tab" id="buyback_success" role="tabpanel" aria-labelledby="buyers-tab">
                        <div id="buyback_success">
                            <h3 class="pt-3">Выкупы успешные</h3>
                            <div class="chart-container">
                                <canvas id="buyback_successChartCanvas"></canvas>
                            </div>
                            <div class="w-100 d-flex justify-content-center">
                                <div class="mt-3 d-flex" style="color: #666666; gap: 15px">
                                    <p>Транзакций: <span class="text-white font-weight-bold">580</span></p>
                                    <p>Выручка: <span class="text-white font-weight-bold">36000</span></p>
                                    <p>Ср. чек: <span class="text-white font-weight-bold">3000</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-none tab" id="buyback_process" role="tabpanel" aria-labelledby="buyers-tab">
                        <div id="buyback_process">
                            <h3 class="pt-3">Выкупы процесс</h3>
                            <div class="chart-container">
                                <canvas id="buyback_processChartCanvas"></canvas>
                            </div>
                            <div class="w-100 d-flex justify-content-center">
                                <div class="mt-3 d-flex" style="color: #666666; gap: 15px">
                                    <p>Транзакций: <span class="text-white font-weight-bold">580</span></p>
                                    <p>Выручка: <span class="text-white font-weight-bold">36000</span></p>
                                    <p>Ср. чек: <span class="text-white font-weight-bold">3000</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-3 borr-30 h-100">
                <h3>Основные показатели</h3>
                <span>Покупателей онлайн: 1200</span>
                <span>Продавцов онлайн: 35</span>
                <span>Покупателей за 30 дней:</span>
                <span>Продавцов за 30 дней:</span>
                <span>Выручка за 30 дней:</span>
                <span>Выплат за 30 дней:</span>
                <span>Выкупов инициировано (30д):</span>
                <span>Выкупов реализовано (30д):</span>
            </div>
        </div>
    </div>
    <div class="row mt-2 mb-5">
        <div class="col-md-8">
            <div class="card border-0 borr-30 h-100">
                <h3>Финансы</h3>
                <span>Баланс Юмани: 149.930 руб</span>
                <span>Баланс продавцов (доступно): 149.930 руб</span>
                <span>Баланс продавцов (заморожено): 149.930 руб</span>
                <span>Баланс покупателей: 149.930 руб</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-3 borr-30 h-100">
                <h5>Выгрузка статистики в эксель</h5>
                <div class="d-flex">
                    <label for="">
                        <input type="checkbox">
                        Продавцы
                    </label>
                    <label for="" class="ml-3">
                        <input type="checkbox">
                        Покупатели
                    </label>
                </div>
                <p class="fz-14">
                    <span class="underline">Диапазон времени</span>
                    <span class="font-weight-light">21 Мая, 2024 - 26 Мая, 2024</span>
                </p>
                <span class="font-weight-bold underline">Экспортировать в .xls</span>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        #chartTabs a{
            color: #A3ABBF;
            cursor: pointer;
            font-size: 13px;
        }

        #chartTabs a.active{
            color: #ffffff;
            background-color: #282B37;
            border-radius: 30px;
        }

        .tab div{
            background-color: #252732;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            border-radius: 12px
        }

        .tab div h3{
            color: #ffffff;
        }

        .fz-14{
            font-size: 14px;
        }

        .underline{
            text-decoration: underline;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('#chartTabs a').on('click', function (e) {
                e.preventDefault();
                $('#chartTabs a').removeClass('active');
                $(this).addClass('active');

                var tab = $(this).data('tab');
                $('#chartContent .tab').addClass('d-none');
                $('#'+tab).removeClass('d-none');
            });
        });
    </script>

    <script>
        let bgColor = [
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A',
            '#373A4A'
        ];

        let hoverColor = [
            '#FBC300', // Жёлтый цвет при наведении
            '#FBC300',
            '#FBC300',
            '#FBC300',
            '#FBC300',
            '#FBC300',
            '#FBC300',
            '#FBC300',
            '#FBC300',
            '#FBC300'
        ];

        let labels = ['013 Дек', '02', '03', '04', '05', '06', '07', '08', '09', '23 Дек'];

        let data = [2000, 5000, 6000, 2000, 0, 8000, 5000, 6000, 5000, 6000];
        let data2 = [2000, 5000, 6000, 2000, 0, 8000, 5000, 6000, 5000, 6000];
        let data3 = [3000, 4000, 5000, 2000, 1000, 7000, 6000, 5000, 4000, 3000];
        let data4 = [1500, 2500, 3500, 4500, 5500, 6500, 7500, 8500, 9500, 10500];
        let data5 = [500, 1500, 2500, 3500, 4500, 5500, 6500, 7500, 8500, 9500];
        let data6 = [4000, 3000, 2000, 1000, 0, 8000, 9000, 7000, 6000, 5000];

        const ctx = document.getElementById('revenueChartCanvas');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: bgColor,
                    hoverBackgroundColor: hoverColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        const ctxWith = document.getElementById('withdrawChartCanvas');
        new Chart(ctxWith, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data2,
                    backgroundColor: bgColor,
                    hoverBackgroundColor: hoverColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        const ctxSeller = document.getElementById('sellersChartCanvas');
        new Chart(ctxSeller, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data3,
                    backgroundColor: bgColor,
                    hoverBackgroundColor: hoverColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        const ctxBuyers = document.getElementById('buyersChartCanvas');
        new Chart(ctxBuyers, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data4,
                    backgroundColor: bgColor,
                    hoverBackgroundColor: hoverColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        const ctxSuccess = document.getElementById('buyback_successChartCanvas');
        new Chart(ctxSuccess, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data5,
                    backgroundColor: bgColor,
                    hoverBackgroundColor: hoverColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        const ctxProcess = document.getElementById('buyback_processChartCanvas');
        new Chart(ctxProcess, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data6,
                    backgroundColor: bgColor,
                    hoverBackgroundColor: hoverColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
@endsection
