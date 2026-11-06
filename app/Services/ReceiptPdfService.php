<?php

namespace App\Services;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\Storage;

class ReceiptPdfService
{
    public function render(Receipt $receipt): DomPdfWrapper
    {
        $logoBase64 = null;
        $logoPath = $receipt->issuer_snapshot['logo_path'] ?? null;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mimeType = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $content = Storage::disk('public')->get($logoPath);
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($content);
        }

        return Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait');
    }
}
