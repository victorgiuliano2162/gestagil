<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicReceiptController extends Controller
{
    public function show(string $code): Response
    {
        $receipt = Receipt::withoutGlobalScopes()
            ->where('code', $code)
            ->firstOrFail();

        return Inertia::render('Public/ReceiptVerification', [
            'receipt' => $receipt,
            'pdfUrl' => url("/r/{$code}/pdf"),
        ]);
    }

    public function pdf(string $code, ReceiptPdfService $pdfService, Request $request): HttpResponse
    {
        $receipt = Receipt::withoutGlobalScopes()
            ->where('code', $code)
            ->firstOrFail();

        $pdf = $pdfService->render($receipt);
        $filename = 'recibo-' . str_replace('/', '-', $receipt->formatted_number) . '.pdf';

        if ($request->input('disposition') === 'attachment') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
