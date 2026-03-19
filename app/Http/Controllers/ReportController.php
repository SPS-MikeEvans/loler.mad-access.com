<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function clientInspections(Client $client, Request $request): Response
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $inspections = $client->kitItems()
            ->with(['kitType', 'inspections' => function ($q) use ($year, $month) {
                $q->with('inspector')
                    ->whereYear('inspection_date', $year)
                    ->whereMonth('inspection_date', $month)
                    ->orderBy('inspection_date');
            }])
            ->get()
            ->flatMap(fn ($item) => $item->inspections->map(fn ($i) => ['item' => $item, 'inspection' => $i]));

        $total = $inspections->sum(fn ($r) => (float) ($r['inspection']->cost ?? 0));

        $pdf = Pdf::loadView('reports.client-inspections', compact('client', 'inspections', 'total', 'year', 'month'));

        return $pdf->download("loler-report-{$client->id}-{$year}-{$month}.pdf");
    }
}
