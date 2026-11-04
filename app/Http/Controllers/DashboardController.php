<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $telemetry = [
            'grossSales' => [
                'amount' => '$84,290.45',
                'trend' => '+14.8%',
                'posBreakdown' => '$58.1k',
                'erpBreakdown' => '$26.1k'
            ],
            'transactions' => [
                'total' => '3,412',
                'authRate' => '99.4%',
                'avgBasket' => '$24.70',
                'nfcRate' => '84.2%'
            ],
            'inventory' => [
                'valuation' => '$1,420,890',
                'criticalCount' => 18,
                'healthIndex' => 91.8
            ],
            'terminalFloat' => [
                'amount' => '$14,680.00',
                'variance' => '$0 Variance',
                'verifiedTills' => '12/12 Verified',
                'nextDrop' => '18:00 (3h 12m)'
            ]
        ];

        return Inertia::render('Dashboard', [
            'telemetry' => $telemetry,
            'activeShift' => 'Shift 02 (Active)',
            'terminalsOnline' => 12,
            'systemVersion' => 'v4.19'
        ]);
    }
}
