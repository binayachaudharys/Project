<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SaleRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
{
    public function index(Request $request, SaleRepository $sales): Response
    {
        $from = Carbon::parse($request->query('from', now()->toDateString()));
        $to = Carbon::parse($request->query('to', now()->toDateString()));

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $report = $sales->paidSalesInRange($from, $to);

        return Inertia::render('Admin/Sales/Index', [
            'sales' => $report['sales'],
            'total' => $report['total'],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
