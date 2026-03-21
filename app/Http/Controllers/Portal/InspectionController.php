<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\KitItem;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function index(KitItem $kitItem): View
    {
        $this->authorize('manage-own-kit', $kitItem);
        $inspections = $kitItem->inspections()->complete()->latest('inspection_date')->get();

        return view('portal.inspections.index', compact('kitItem', 'inspections'));
    }

    public function downloadPdf(Inspection $inspection): Response
    {
        $this->authorize('manage-own-kit', $inspection->kitItem);

        abort_unless($inspection->pdf_path && file_exists(storage_path("app/{$inspection->pdf_path}")), 404, 'Certificate not available.');

        return response()->file(storage_path("app/{$inspection->pdf_path}"), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="inspection-certificate.pdf"',
        ]);
    }
}
