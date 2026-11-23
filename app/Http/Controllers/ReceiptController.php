<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelReceiptRequest;
use App\Http\Requests\IssueReceiptRequest;
use App\Http\Requests\SendReceiptEmailRequest;
use App\Mail\ReceiptMail;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Services\ReceiptPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = $request->input('status');
        $search = $request->input('q');

        $query = Receipt::where('user_id', $user->id)
            ->with(['client:id,name', 'transaction:id,amount_cents,occurred_on'])
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($search, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('payer_name', 'like', "%{$term}%")
                        ->orWhere('number', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->latest('issued_at');

        $receipts = $query->paginate(15)->withQueryString();

        return Inertia::render('Receipts/Index', [
            'receipts' => $receipts,
            'filters' => [
                'status' => $status,
                'q' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $transactionId = $request->integer('transaction_id');

        $transaction = Transaction::where('user_id', $user->id)
            ->with('client')
            ->findOrFail($transactionId);

        if ($transaction->type !== 'income') {
            return redirect()->route('transactions.index')
                ->with('error', 'Recibos só podem ser emitidos para lançamentos de entrada / receita.');
        }

        // Verifica se já existe recibo emitido ativo para esta transação
        $existingReceipt = Receipt::where('transaction_id', $transaction->id)
            ->where('status', 'issued')
            ->first();

        if ($existingReceipt) {
            return redirect()->route('receipts.show', $existingReceipt->id)
                ->with('info', 'Já existe um recibo ativo para este lançamento.');
        }

        $businessProfile = $user->businessProfile()->first();

        if (! $businessProfile || empty($businessProfile->display_name) || empty($businessProfile->document_number)) {
            return redirect()->route('business-profile.edit')
                ->with('error', 'Complete seu Perfil Comercial (Nome/Razão Social e CPF/CNPJ) antes de emitir recibos.');
        }

        return Inertia::render('Receipts/Create', [
            'transaction' => $transaction,
            'client' => $transaction->client,
            'businessProfile' => $businessProfile,
        ]);
    }

    public function store(IssueReceiptRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $transaction = Transaction::where('user_id', $user->id)
            ->with('client')
            ->findOrFail($data['transaction_id']);

        if ($transaction->type !== 'income') {
            return back()->with('error', 'Recibos só podem ser gerados a partir de receitas.');
        }

        $profile = $user->businessProfile()->first();
        if (! $profile || empty($profile->display_name) || empty($profile->document_number)) {
            return redirect()->route('business-profile.edit')
                ->with('error', 'Complete seu Perfil Comercial (Nome/Razão Social e CPF/CNPJ) antes de emitir recibos.');
        }

        $storeName = $profile->store_name ?: $profile->display_name ?: $user->name;

        $fullAddress = null;
        if ($profile->street) {
            $fullAddress = "{$profile->street}, {$profile->number}";
            if ($profile->neighborhood) {
                $fullAddress .= " - {$profile->neighborhood}";
            }
            if ($profile->city && $profile->state) {
                $fullAddress .= " ({$profile->city}/{$profile->state})";
            }
        }

        $issuerSnapshot = [
            'store_name' => $storeName,
            'display_name' => $profile->display_name ?: $user->name,
            'document_type' => $profile->document_type ?: 'cpf',
            'document_number' => $profile->document_number ?: '',
            'phone' => $profile->phone ?: '',
            'full_address' => $fullAddress,
            'logo_path' => $profile->logo_path,
            'receipt_footer' => $profile->receipt_footer,
        ];

        $year = (int) now()->format('Y');

        $result = DB::transaction(function () use ($user, $transaction, $data, $issuerSnapshot, $year) {
            $activeReceipt = Receipt::where('transaction_id', $transaction->id)
                ->where('status', 'issued')
                ->lockForUpdate()
                ->first();

            if ($activeReceipt) {
                return ['receipt' => $activeReceipt, 'already_existed' => true];
            }

            // Sequencial atômico por ano e usuário
            $maxSeq = Receipt::where('user_id', $user->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->max('number') ?? 0;

            $nextNumber = $maxSeq + 1;

            $receipt = Receipt::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'client_id' => $transaction->client_id,
                'code' => Str::uuid()->toString(),
                'year' => $year,
                'number' => $nextNumber,
                'status' => 'issued',
                'issued_at' => now(),
                'amount_cents' => $transaction->amount_cents,
                'payment_method' => $data['payment_method'],
                'description' => $data['description'],
                'payer_name' => $data['payer_name'],
                'payer_document' => $data['payer_document'] ?? null,
                'issuer_snapshot' => $issuerSnapshot,
            ]);

            return ['receipt' => $receipt, 'already_existed' => false];
        });

        if ($result['already_existed']) {
            return redirect()->route('receipts.show', $result['receipt']->id)
                ->with('error', 'Já existe um recibo emitido ativo para esta receita.');
        }

        $receipt = $result['receipt'];

        Log::info('audit.receipt.issued', [
            'user_id' => $user->id,
            'receipt_id' => $receipt->id,
            'transaction_id' => $receipt->transaction_id,
            'number' => $receipt->formatted_number,
            'amount_cents' => $receipt->amount_cents,
        ]);

        return redirect()->route('receipts.show', $receipt->id)
            ->with('success', "Recibo Nº {$receipt->formatted_number} emitido com sucesso!");
    }

    public function show(Receipt $receipt): Response
    {
        $receipt->load(['transaction', 'client']);

        return Inertia::render('Receipts/Show', [
            'receipt' => $receipt,
            'publicUrl' => url('/r/'.$receipt->code),
        ]);
    }

    public function pdf(Receipt $receipt, ReceiptPdfService $pdfService, Request $request): HttpResponse
    {
        $pdf = $pdfService->render($receipt);
        $filename = 'recibo-'.str_replace('/', '-', $receipt->formatted_number).'.pdf';

        if ($request->input('disposition') === 'attachment') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function cancel(CancelReceiptRequest $request, Receipt $receipt): RedirectResponse
    {
        if ($receipt->isCanceled()) {
            return back()->with('error', 'Este recibo já está cancelado.');
        }

        $receipt->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'cancel_reason' => $request->cancel_reason,
        ]);

        Log::info('audit.receipt.canceled', [
            'user_id' => $request->user()->id,
            'receipt_id' => $receipt->id,
            'number' => $receipt->formatted_number,
            'reason' => $request->cancel_reason,
        ]);

        return redirect()->route('receipts.show', $receipt->id)
            ->with('success', 'Recibo cancelado com sucesso.');
    }

    public function email(SendReceiptEmailRequest $request, Receipt $receipt, ReceiptPdfService $pdfService): RedirectResponse
    {
        $pdfContent = $pdfService->render($receipt)->output();

        Mail::to($request->email)->send(new ReceiptMail($receipt, $pdfContent));

        return back()->with('success', "Recibo enviado com sucesso para {$request->email}!");
    }
}
