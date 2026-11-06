<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $receipt->formatted_number }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: a4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            line-height: 1.5;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            box-sizing: border-box;
            position: relative;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo {
            width: 90px;
            vertical-align: middle;
        }

        .header-logo img {
            max-width: 80px;
            max-height: 80px;
            border-radius: 6px;
        }

        .header-info {
            vertical-align: middle;
            padding-left: 10px;
        }

        .store-title {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            margin: 0;
            text-transform: uppercase;
        }

        .store-sub {
            font-size: 11px;
            color: #4b5563;
            margin-top: 3px;
        }

        .receipt-badge {
            text-align: right;
            vertical-align: middle;
        }

        .receipt-number {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            margin: 0;
        }

        .receipt-date {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }

        .amount-box {
            background-color: #f3f4f6;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 20px;
            text-align: right;
        }

        .amount-label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
        }

        .amount-value {
            font-size: 24px;
            font-weight: bold;
            color: #047857;
            display: inline-block;
            margin-left: 10px;
        }

        .section {
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 2px;
        }

        .section-content {
            font-size: 13px;
            color: #1f2937;
            margin-top: 4px;
        }

        .amount-words {
            font-style: italic;
            font-weight: bold;
            color: #374151;
            background-color: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            border-left: 3px solid #059669;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .details-table td {
            padding: 6px 0;
            vertical-align: top;
        }

        .details-label {
            width: 25%;
            font-weight: bold;
            color: #4b5563;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }

        .auth-code {
            font-family: monospace;
            font-size: 11px;
            color: #4f46e5;
            font-weight: bold;
        }

        /* Tarja de Cancelado */
        .watermark {
            position: absolute;
            top: 35%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 60px;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.22);
            text-transform: uppercase;
            transform: rotate(-25deg);
            border: 5px dashed rgba(220, 38, 38, 0.35);
            padding: 15px;
            border-radius: 12px;
            letter-spacing: 5px;
            z-index: 10;
        }

        .canceled-banner {
            background-color: #fee2e2;
            border: 1px solid #f87171;
            color: #991b1b;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($receipt->isCanceled())
            <div class="watermark">CANCELADO</div>
            <div class="canceled-banner">
                <strong>ATENÇÃO: ESTE RECIBO FOI CANCELADO.</strong><br>
                <span>Cancelado em: {{ $receipt->canceled_at?->format('d/m/Y H:i') }}</span>
                @if($receipt->cancel_reason)
                    <br><span>Motivo: {{ $receipt->cancel_reason }}</span>
                @endif
            </div>
        @endif

        <div class="header">
            <table class="header-table">
                <tr>
                    @if(!empty($logoBase64))
                        <td class="header-logo">
                            <img src="{{ $logoBase64 }}" alt="Logo">
                        </td>
                    @endif
                    <td class="header-info">
                        <div class="store-title">{{ $receipt->issuer_snapshot['store_name'] ?? $receipt->issuer_snapshot['display_name'] ?? 'Comprovante Comercial' }}</div>
                        <div class="store-sub">
                            @if(!empty($receipt->issuer_snapshot['document_number']))
                                {{ strtoupper($receipt->issuer_snapshot['document_type'] ?? 'DOC') }}: {{ $receipt->issuer_snapshot['document_number'] }} •
                            @endif
                            @if(!empty($receipt->issuer_snapshot['phone']))
                                Tel: {{ $receipt->issuer_snapshot['phone'] }}
                            @endif
                            @if(!empty($receipt->issuer_snapshot['full_address']))
                                <br>{{ $receipt->issuer_snapshot['full_address'] }}
                            @endif
                        </div>
                    </td>
                    <td class="receipt-badge">
                        <div class="receipt-number">RECIBO Nº {{ $receipt->formatted_number }}</div>
                        <div class="receipt-date">Emissão: {{ $receipt->issued_at->format('d/m/Y H:i') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="amount-box">
            <span class="amount-label">Valor Recebido</span>
            <span class="amount-value">{{ $receipt->formatted_amount }}</span>
        </div>

        <div class="section">
            <div class="section-title">Valor por Extenso</div>
            <div class="amount-words">
                ({{ $receipt->amount_in_words }})
            </div>
        </div>

        <div class="section">
            <div class="section-title">Dados do Pagador / Cliente</div>
            <table class="details-table">
                <tr>
                    <td class="details-label">Nome:</td>
                    <td><strong>{{ $receipt->payer_name }}</strong></td>
                </tr>
                @if(!empty($receipt->payer_document))
                <tr>
                    <td class="details-label">CPF / CNPJ:</td>
                    <td>{{ $receipt->payer_document }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="section">
            <div class="section-title">Referente a / Descrição</div>
            <div class="section-content">
                {{ $receipt->description }}
            </div>
        </div>

        <div class="section">
            <div class="section-title">Informações de Pagamento</div>
            <table class="details-table">
                <tr>
                    <td class="details-label">Forma de Pagamento:</td>
                    <td><strong>{{ strtoupper($receipt->payment_method) }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>
                Este documento comprova o recebimento do valor especificado acima para os devidos fins de direito.<br>
                Chave de Autenticidade Digital: <span class="auth-code">{{ $receipt->code }}</span><br>
                Consulte a autenticidade online em: <strong>{{ url('/r/' . $receipt->code) }}</strong>
            </p>
        </div>
    </div>
</body>
</html>
