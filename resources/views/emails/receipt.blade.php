<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f9fafb;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }
        .amount-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
        }
        .amount-title {
            font-size: 12px;
            color: #166534;
            text-transform: uppercase;
            font-weight: bold;
        }
        .amount-value {
            font-size: 24px;
            font-weight: bold;
            color: #15803d;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="store-name">{{ $receipt->issuer_snapshot['store_name'] ?? $receipt->issuer_snapshot['display_name'] ?? 'Comprovante' }}</div>
            <p style="margin: 5px 0 0; font-size: 13px; color: #6b7280;">Recibo Digital Nº {{ $receipt->formatted_number }}</p>
        </div>

        <p>Olá, <strong>{{ $receipt->payer_name }}</strong>!</p>
        <p>Segue em anexo o seu recibo de pagamento emitido em {{ $receipt->issued_at->format('d/m/Y H:i') }}.</p>

        <div class="amount-box">
            <div class="amount-title">Valor Confirmado</div>
            <div class="amount-value">{{ $receipt->formatted_amount }}</div>
            <div style="font-size: 13px; color: #166534; margin-top: 4px;">({{ $receipt->amount_in_words }})</div>
        </div>

        <p><strong>Referente a:</strong> {{ $receipt->description }}</p>
        <p><strong>Forma de Pagamento:</strong> {{ strtoupper($receipt->payment_method) }}</p>

        <p style="margin-top: 25px;">
            Você também pode consultar a autenticidade deste documento a qualquer momento acessando:<br>
            <a href="{{ url('/r/' . $receipt->code) }}" style="color: #4f46e5; word-break: break-all;">
                {{ url('/r/' . $receipt->code) }}
            </a>
        </p>

        <div class="footer">
            <p>Este é um e-mail automático gerado pelo GestÁgil para {{ $receipt->issuer_snapshot['store_name'] ?? 'seu comerciante' }}.</p>
        </div>
    </div>
</body>
</html>
