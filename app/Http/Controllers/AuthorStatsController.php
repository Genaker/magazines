<?php

namespace App\Http\Controllers;

use App\Support\AuthorStats;
use Carbon\Carbon;
use Illuminate\View\View;

class AuthorStatsController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        [$year, $month] = $this->resolveSelectedMonth();

        return view('stats.index', [
            'stats' => AuthorStats::forUser($user),
            'monthly' => AuthorStats::forUserMonth($user, $year, $month),
            'monthOptions' => AuthorStats::monthOptions($user),
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }

    /** @return array{0: int, 1: int} */
    private function resolveSelectedMonth(): array
    {
        $now = now('UTC');
        $year = request()->integer('year') ?: $now->year;
        $month = request()->integer('month') ?: $now->month;

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }

        $selected = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC')->startOfMonth();
        if ($selected->gt($now->copy()->startOfMonth())) {
            $year = $now->year;
            $month = $now->month;
        }

        return [$year, $month];
    }
}
