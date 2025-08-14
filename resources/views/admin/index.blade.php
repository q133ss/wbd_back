@extends('layouts.main')

@section('title','Главная')

@section('content')
    <div class="row">
        <!-- График -->
        <div class="col-lg-8 col-md-12 mb-4">
            <div class="card">

                <!-- Переключатели графиков -->
                <div class="col-12 mb-3">
                    <div class="btn-group" role="group">
                        @foreach($chartData['datasets'] as $index => $dataset)
                            <button type="button" class="btn btn-outline-dark chart-toggle" data-index="{{ $index }}">
                                {{ $dataset['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>


                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Выручка</h5>
                </div>
                <div class="card-body">
                    <canvas id="mainChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Основные показатели -->
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Основные показатели:</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li>Покупателей онлайн: {{ $metrics['online_buyers'] }}</li>
                        <li>Продавцов онлайн: {{ $metrics['online_sellers'] }}</li>
                        <li>Покупателей за 30 дней: {{ $metrics['buyers_last_30_days'] }}</li>
                        <li>Продавцов за 30 дней: {{ $metrics['sellers_last_30_days'] }}</li>
                        <li>Выручка за 30 дней: {{ number_format($metrics['revenue_last_30_days'], 2, ',', ' ') }}</li>
                        <li>Выкупов инициировано (30д): {{ $metrics['initiated_buybacks_last_30_days'] }}</li>
                        <li>Выкупов реализовано (30д): {{ $metrics['completed_buybacks_last_30_days'] }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('mainChart').getContext('2d');
            const datasets = @json($chartData['datasets']);
            const labels = @json($chartData['labels']);

            // Изначально показываем первый график
            let currentDatasetIndex = 0;

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [datasets[currentDatasetIndex]]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // Кнопки переключения графиков
            const buttons = document.querySelectorAll('.chart-toggle');

            function setActiveButton(index) {
                buttons.forEach(btn => btn.classList.remove('active'));
                buttons[index].classList.add('active');
            }

            buttons.forEach((btn, index) => {
                btn.addEventListener('click', function () {
                    currentDatasetIndex = index;
                    chart.data.datasets = [datasets[index]];
                    chart.update();
                    setActiveButton(index);
                });
            });

            // Сразу активируем первую кнопку
            setActiveButton(currentDatasetIndex);
        });

    </script>
@endsection
