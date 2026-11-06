<?php

use App\Enums\PaymentMethod;
use App\Mail\ReceiptMail;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('authenticated user can issue a receipt from an income transaction', function () {
    $user = User::factory()->withDefaultCategories()->create();

    BusinessProfile::updateOrCreate(
        ['user_id' => $user->id],
        [
            'store_name' => 'Auto Elétrica Silva',
            'display_name' => 'Carlos Silva',
            'document_type' => 'cnpj',
            'document_number' => '12.345.678/0001-95',
            'phone' => '+5581999998888',
        ]
    );

    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Severino Dias',
        'document_number' => '123.456.789-00',
    ]);

    $transaction = Transaction::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'type' => 'income',
        'amount_cents' => 35000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Troca de bateria Moura 60Ah',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $response = $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $transaction->id,
        'payer_name' => 'Severino Dias',
        'payer_document' => '123.456.789-00',
        'description' => 'Troca de bateria Moura 60Ah e teste de alternador',
        'payment_method' => 'pix',
    ]);

    $receipt = Receipt::where('transaction_id', $transaction->id)->first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->year)->toBe((int) now()->format('Y'))
        ->and($receipt->number)->toBe(1)
        ->and($receipt->formatted_number)->toBe(sprintf('0001/%04d', now()->format('Y')))
        ->and($receipt->amount_cents)->toBe(35000)
        ->and($receipt->payer_name)->toBe('Severino Dias')
        ->and($receipt->issuer_snapshot['store_name'])->toBe('Auto Elétrica Silva');

    $response->assertRedirect(route('receipts.show', $receipt->id));
});

test('sequential numbering increments atomically per year and user', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $year = (int) now()->format('Y');

    $trans1 = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 10000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço 1',
        'payment_method' => PaymentMethod::Cash,
    ]);

    $trans2 = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 20000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço 2',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans1->id,
        'payer_name' => 'Cliente 1',
        'description' => 'Desc 1',
        'payment_method' => 'cash',
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans2->id,
        'payer_name' => 'Cliente 2',
        'description' => 'Desc 2',
        'payment_method' => 'pix',
    ]);

    $receipt1 = Receipt::where('transaction_id', $trans1->id)->first();
    $receipt2 = Receipt::where('transaction_id', $trans2->id)->first();

    expect($receipt1->number)->toBe(1)
        ->and($receipt2->number)->toBe(2);
});

test('cannot issue duplicate active receipt for same transaction', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 15000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Venda de Óleo',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans->id,
        'payer_name' => 'Cliente A',
        'description' => 'Venda de Óleo',
        'payment_method' => 'pix',
    ]);

    // Tentativa de emitir segundo recibo ativo
    $secondResponse = $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans->id,
        'payer_name' => 'Cliente A Duplicado',
        'description' => 'Tentativa de duplicidade',
        'payment_method' => 'pix',
    ]);

    expect(Receipt::where('transaction_id', $trans->id)->count())->toBe(1);
});

test('cannot issue receipt for expense transaction', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $expense = Transaction::create([
        'user_id' => $user->id,
        'type' => 'expense',
        'amount_cents' => 5000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Compra de parafusos',
        'payment_method' => PaymentMethod::Cash,
    ]);

    $response = $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $expense->id,
        'payer_name' => 'Fornecedor',
        'description' => 'Compra',
        'payment_method' => 'cash',
    ]);

    $response->assertSessionHas('error');
    expect(Receipt::where('transaction_id', $expense->id)->count())->toBe(0);
});

test('receipts enforce multi-tenant isolation', function () {
    $userA = User::factory()->withDefaultCategories()->create();
    $userB = User::factory()->withDefaultCategories()->create();

    $transA = Transaction::create([
        'user_id' => $userA->id,
        'type' => 'income',
        'amount_cents' => 20000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço A',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $this->actingAs($userA)->post(route('receipts.store'), [
        'transaction_id' => $transA->id,
        'payer_name' => 'Cliente Secreto A',
        'description' => 'Serviço A',
        'payment_method' => 'pix',
    ]);

    $receiptA = Receipt::where('transaction_id', $transA->id)->first();

    // Usuário B tenta ver os detalhes do recibo de A -> 404
    $viewResponse = $this->actingAs($userB)->get(route('receipts.show', $receiptA->id));
    $viewResponse->assertNotFound();

    // Usuário B tenta cancelar o recibo de A -> 404
    $cancelResponse = $this->actingAs($userB)->post(route('receipts.cancel', $receiptA->id), [
        'cancel_reason' => 'Tentativa indevida',
    ]);
    $cancelResponse->assertNotFound();

    expect($receiptA->fresh()->status)->toBe('issued');
});

test('user can cancel receipt with a reason', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 12000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço para cancelar',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans->id,
        'payer_name' => 'Cliente X',
        'description' => 'Serviço para cancelar',
        'payment_method' => 'pix',
    ]);

    $receipt = Receipt::where('transaction_id', $trans->id)->first();

    $response = $this->actingAs($user)->post(route('receipts.cancel', $receipt->id), [
        'cancel_reason' => 'Cliente solicitou reembolso e cancelamento do serviço',
    ]);

    $response->assertRedirect(route('receipts.show', $receipt->id));

    $receipt->refresh();
    expect($receipt->status)->toBe('canceled')
        ->and($receipt->canceled_at)->not->toBeNull()
        ->and($receipt->cancel_reason)->toBe('Cliente solicitou reembolso e cancelamento do serviço');
});

test('receipt can be downloaded and rendered as PDF', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 8500,
        'occurred_on' => now()->toDateString(),
        'description' => 'Alinhamento e Balanceamento',
        'payment_method' => PaymentMethod::Card,
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans->id,
        'payer_name' => 'Eduardo Ferreira',
        'description' => 'Alinhamento e Balanceamento 4 rodas',
        'payment_method' => 'card',
    ]);

    $receipt = Receipt::where('transaction_id', $trans->id)->first();

    $response = $this->actingAs($user)->get(route('receipts.pdf', $receipt->id));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

test('public receipt verification and download without login', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 45000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Pintura de para-choque',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans->id,
        'payer_name' => 'Juliana Paes',
        'description' => 'Pintura automotiva',
        'payment_method' => 'pix',
    ]);

    $receipt = Receipt::where('transaction_id', $trans->id)->first();

    // Consulta pública deslogado
    $publicResponse = $this->get(route('receipts.public.show', $receipt->code));
    $publicResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/ReceiptVerification')
            ->where('receipt.code', $receipt->code)
            ->where('receipt.payer_name', 'Juliana Paes')
        );

    // Download público do PDF deslogado
    $publicPdfResponse = $this->get(route('receipts.public.pdf', $receipt->code));
    $publicPdfResponse->assertOk();
    expect($publicPdfResponse->headers->get('content-type'))->toBe('application/pdf');
});

test('receipt can be delivered via email with PDF attached', function () {
    Mail::fake();

    $user = User::factory()->withDefaultCategories()->create();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 19900,
        'occurred_on' => now()->toDateString(),
        'description' => 'Instalação de Som Automotivo',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $trans->id,
        'payer_name' => 'Fábio Assunção',
        'description' => 'Instalação de Som Automotivo',
        'payment_method' => 'pix',
    ]);

    $receipt = Receipt::where('transaction_id', $trans->id)->first();

    $response = $this->actingAs($user)->post(route('receipts.email', $receipt->id), [
        'email' => 'cliente.fabio@exemplo.com',
    ]);

    $response->assertSessionHas('success');

    Mail::assertSent(ReceiptMail::class, function (ReceiptMail $mail) use ($receipt) {
        return $mail->hasTo('cliente.fabio@exemplo.com') &&
               $mail->receipt->id === $receipt->id &&
               ! empty($mail->pdfBinary);
    });
});
