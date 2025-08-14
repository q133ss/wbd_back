<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyback;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IndexController extends Controller
{
    public function index()
    {
        // Получаем начало и конец текущего месяца
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Создаем массив всех дней месяца
        $daysInMonth = $startOfMonth->diffInDays($endOfMonth) + 1;
        $labels = [];
        $currentDay = $startOfMonth->copy();

        for ($i = 0; $i < $daysInMonth; $i++) {
            $labels[] = $currentDay->format('Y-m-d');
            $currentDay->addDay();
        }

        // Данные для выручки (сумма покупок тарифов по дням)
        $revenueData = [];
        $revenueQuery = Transaction::where('status', 'completed')
            ->whereNotNull('tariff_id')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->pluck('total', 'date');

        foreach ($labels as $day) {
            $revenueData[] = $revenueQuery[$day] ?? 0;
        }

        // Данные для новых продавцов по дням
        $sellersData = [];
        $sellersQuery = User::where('role_id', function ($query) {
            return $query->select('id')
                ->from('roles')
                ->where('slug', 'seller');
        })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        foreach ($labels as $day) {
            $sellersData[] = $sellersQuery[$day] ?? 0;
        }

        // Данные для новых покупателей по дням
        $buyersData = [];
        $buyersQuery = User::where('role_id', function ($query) {
            return $query->select('id')
                ->from('roles')
                ->where('slug', 'buyer');
        })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        foreach ($labels as $day) {
            $buyersData[] = $buyersQuery[$day] ?? 0;
        }

        // Данные для успешных выкупов по дням
        $completedBuybacksData = [];
        $completedBuybacksQuery = Buyback::whereIn('status', ['completed', 'cashback_received'])
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        foreach ($labels as $day) {
            $completedBuybacksData[] = $completedBuybacksQuery[$day] ?? 0;
        }

        // Данные для выкупов в процессе по дням
        $pendingBuybacksData = [];
        $pendingBuybacksQuery = Buyback::whereIn('status', ['pending', 'awaiting_receipt', 'on_confirmation', 'awaiting_payment_confirmation'])
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        foreach ($labels as $day) {
            $pendingBuybacksData[] = $pendingBuybacksQuery[$day] ?? 0;
        }

        // Формируем данные для графика
        $chartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Выручка',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Новые продавцы',
                    'data' => $sellersData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Новые покупатели',
                    'data' => $buyersData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Успешные выкупы',
                    'data' => $completedBuybacksData,
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Выкупы в процессе',
                    'data' => $pendingBuybacksData,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y1'
                ]
            ]
        ];

        // Покупатели онлайн (is_online = 1)
        $onlineBuyers = 0;

        // Продавцы онлайн
        $onlineSellers = 0;

        // Покупатели за последние 30 дней
        $buyersLast30Days = User::where('role_id', function($query) {
            return $query->select('id')
                ->from('roles')
                ->where('slug', 'buyer');
        })->where('created_at', '>=', now()->subDays(30))->count();

        // Продавцы за последние 30 дней
        $sellersLast30Days = User::where('role_id', function($query) {
            return $query->select('id')
                ->from('roles')
                ->where('slug', 'seller');
        })->where('created_at', '>=', now()->subDays(30))->count();

        // Выручка за последние 30 дней (сумма завершенных транзакций с тарифами)
        $revenueLast30Days = Transaction::where('status', 'completed')
            ->whereNotNull('tariff_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        // Выкупов инициализировано за 30 дней (все созданные выкупы)
        $initiatedBuybacksLast30Days = Buyback::where('created_at', '>=', now()->subDays(30))->count();

        // Выкупов завершено за 30 дней
        $completedBuybacksLast30Days = Buyback::whereIn('status', ['completed', 'cashback_received'])
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Формируем массив с показателями
        $metrics = [
            'online_buyers' => $onlineBuyers,
            'online_sellers' => $onlineSellers,
            'buyers_last_30_days' => $buyersLast30Days,
            'sellers_last_30_days' => $sellersLast30Days,
            'revenue_last_30_days' => $revenueLast30Days,
            'initiated_buybacks_last_30_days' => $initiatedBuybacksLast30Days,
            'completed_buybacks_last_30_days' => $completedBuybacksLast30Days,
        ];

        return view('admin.index', compact('chartData', 'metrics'));
    }
}
